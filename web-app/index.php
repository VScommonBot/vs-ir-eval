<?php
// 간이 IR 평가 웹앱의 PHP 단일 파일 엔드포인트
$script_nonce = bin2hex(random_bytes(16));
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-" . $script_nonce . "' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline'; img-src 'self' https://quickchart.io data:; connect-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($origin !== '' && $host !== '' && parse_url($origin, PHP_URL_HOST) !== $host) {
        http_response_code(403);
        echo json_encode(['error' => '허용되지 않은 요청 출처입니다.']);
        exit;
    }

    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input ?: '', true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['error' => '요청 본문이 올바른 JSON 형식이 아닙니다.']);
        exit;
    }

    $idea = trim((string)($input['idea'] ?? ''));
    $consent = $input['consentToAiProcessing'] ?? false;

    if (empty($idea)) {
        http_response_code(400);
        echo json_encode(['error' => '아이디어를 입력해주세요.']);
        exit;
    }

    $idea_length = function_exists('mb_strlen') ? mb_strlen($idea, 'UTF-8') : strlen($idea);
    if ($idea_length > 12000) {
        http_response_code(413);
        echo json_encode(['error' => '입력은 12,000자 이내로 줄여주세요.']);
        exit;
    }

    if ($consent !== true) {
        http_response_code(400);
        echo json_encode(['error' => 'AI 분석을 위해 입력 자료가 외부 API로 전송되는 것에 동의해야 합니다.']);
        exit;
    }

    $api_key = getenv('OPENAI_API_KEY') ?: '';
    if ($api_key === '') {
        http_response_code(500);
        echo json_encode(['error' => '서버 설정 오류: OPENAI_API_KEY 환경변수가 필요합니다.']);
        exit;
    }
    $model = getenv('OPENAI_MODEL') ?: 'gpt-4o';

    $system_prompt = <<<PROMPT
# VS IR Evaluation Framework
You are acting as an initial-stage startup review and mentoring analyst using a public VentureSquare-style framework. Do not present the output as an actual investment decision, investment recommendation, or confidential investment committee process.
## Public VentureSquare-Style Review Philosophy
1. **Team & CEO (팀과 기업가 역량)**
2. **Market Size & Growth (시장 매력도)**
3. **Product & Moat (제품/기술 경쟁력)**
4. **Scalability & EXIT (사업 확장 및 회수 가능성)**
5. **TIPS / LIPS Eligibility (정부지원사업 적합성)**
6. **Fatal Flaws (치명적 실패 요인 - Red Flags)**

Output strictly in the Markdown format requested, including the QuickChart radar image URL.
(참고: 레이더 차트의 URL은 띄어쓰기 없이 작성할 것)
PROMPT;

    $system_prompt_full = file_get_contents(__DIR__ . '/VS_IR_EVAL.prompt.md');
    // If the file exists, use it. Otherwise use the fallback string.
    if ($system_prompt_full !== false && trim($system_prompt_full) !== '') {
        $system_prompt = $system_prompt_full;
    }

    $data = [
        "model" => $model,
        "messages" => [
            ["role" => "system", "content" => $system_prompt],
            ["role" => "user", "content" => "Evaluate this pitch or idea:\\n\\n" . $idea]
        ],
        "temperature" => 0.7
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    if ($ch === false) {
        http_response_code(500);
        echo json_encode(['error' => '서버 설정 오류: cURL을 초기화할 수 없습니다.']);
        exit;
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 90);
    $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($payload === false) {
        http_response_code(400);
        echo json_encode(['error' => '입력 내용을 API 요청 형식으로 변환하지 못했습니다.']);
        exit;
    }
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);

    $response = curl_exec($ch);
    if(curl_errno($ch)){
        http_response_code(502);
        echo json_encode(['error' => 'OpenAI API 호출 중 네트워크 오류가 발생했습니다.']);
    } else {
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $res_json = json_decode($response, true);
        if (!is_array($res_json)) {
            http_response_code(502);
            echo json_encode(['error' => 'OpenAI API 응답이 올바른 JSON 형식이 아닙니다.']);
            curl_close($ch);
            exit;
        }
        if ($http_status < 200 || $http_status >= 300) {
            error_log('OpenAI API error (' . $http_status . '): ' . ($res_json['error']['message'] ?? 'unknown'));
            http_response_code(502);
            echo json_encode(['error' => 'OpenAI API 요청이 실패했습니다. 잠시 후 다시 시도해주세요.']);
            curl_close($ch);
            exit;
        }
        if (!isset($res_json['choices'][0]['message']['content'])) {
            error_log('OpenAI API unexpected response: ' . substr((string)$response, 0, 500));
            http_response_code(502);
            echo json_encode(['error' => 'OpenAI API 응답을 해석하지 못했습니다.']);
            curl_close($ch);
            exit;
        }
        $markdown = $res_json['choices'][0]['message']['content'] ?? '';
        echo json_encode(['markdown' => $markdown], JSON_UNESCAPED_UNICODE);
    }
    curl_close($ch);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>벤처스퀘어 간이 AI 셀프 평가</title>
    <!-- 마크다운 파서 및 CSS (버전 고정) -->
    <script src="https://cdn.jsdelivr.net/npm/marked@12.0.2/marked.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdn.jsdelivr.net/npm/dompurify@3.1.6/dist/purify.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <style>
        :root {
            --main-blue: #0032CD;
            --deep-navy: #001E96;
            --brand-black: #212121;
            --mint-accent: #55FFF0;
            --bg-gray: #f8f9fa;
        }
        body {
            font-family: 'Pretendard', sans-serif;
            background-color: var(--bg-gray);
            color: var(--brand-black);
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid var(--main-blue);
            padding-bottom: 20px;
        }
        .header h1 {
            color: var(--main-blue);
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        .header p {
            color: #666;
            margin: 0;
            font-size: 15px;
        }
        textarea {
            width: 100%;
            height: 200px;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            resize: vertical;
            box-sizing: border-box;
            font-family: inherit;
        }
        textarea:focus {
            outline: none;
            border-color: var(--main-blue);
        }
        .notice {
            background: #f0f4ff;
            border-left: 5px solid var(--main-blue);
            border-radius: 4px;
            padding: 14px 16px;
            margin: 18px 0;
            font-size: 14px;
            color: #333;
        }
        .consent {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin-top: 14px;
            font-size: 14px;
            color: #333;
        }
        .consent input {
            margin-top: 4px;
        }
        .btn {
            display: block;
            width: 100%;
            background-color: var(--main-blue);
            color: #fff;
            border: none;
            padding: 16px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 8px;
            margin-top: 20px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover {
            background-color: var(--deep-navy);
        }
        .btn:disabled {
            background-color: #aaa;
            cursor: not-allowed;
        }
        #result {
            margin-top: 40px;
            display: none;
            border-top: 1px solid #ddd;
            padding-top: 30px;
        }
        /* VS 디자인 마크다운 렌더링 스타일 */
        #result h1 { color: var(--main-blue); border-bottom: 2px solid var(--main-blue); padding-bottom: 8px; font-size: 24px; }
        #result h2 { color: var(--brand-black); background-color: #f0f4ff; border-left: 5px solid var(--main-blue); padding: 6px 10px; font-size: 18px; margin-top: 24px; }
        #result h3 { color: var(--deep-navy); font-size: 16px; margin-top: 16px; }
        #result blockquote { background-color: var(--brand-black); color: var(--mint-accent); padding: 15px; margin: 20px 0; font-style: italic; font-weight: bold; border-radius: 4px; text-align: center; }
        #result img { max-width: 100%; display: block; margin: 20px auto; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .loading { text-align: center; display: none; margin-top: 20px; font-weight: bold; color: var(--main-blue); }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>VentureSquare IR Evaluation</h1>
            <p>벤처스퀘어 간이 AI 셀프 평가 시스템</p>
        </div>
        
        <p style="font-weight: bold;">당신의 사업 아이디어나 엘리베이터 피치를 자유롭게 적어주세요.</p>
        <div class="notice">
            입력한 내용은 평가 생성을 위해 OpenAI API로 전송됩니다. 이 예제 앱은 입력 내용을 별도로 저장하지 않지만, 서버 운영자와 API 제공자가 처리할 수 있습니다. 비공개 IR, 개인정보, 계약서, 재무자료 원문 등 민감한 자료는 넣지 마세요.
        </div>
        <textarea id="ideaInput" placeholder="예: 소상공인을 위한 AI 기반 재고관리 챗봇 서비스입니다. 기존 ERP와 달리 카카오톡으로 발주가 가능하며..."></textarea>
        <label class="consent">
            <input id="consentInput" type="checkbox">
            <span>입력 자료가 AI 분석을 위해 외부 API로 전송되는 것에 동의합니다.</span>
        </label>
        
        <button id="submitBtn" class="btn">[간이 AI 셀프 평가] 진행하기</button>
        <div id="loading" class="loading">벤처스퀘어 뷰로 사업을 분석 중입니다... (약 15초 소요)</div>
        
        <div id="result"></div>
    </div>

    <script nonce="<?php echo htmlspecialchars($script_nonce, ENT_QUOTES, 'UTF-8'); ?>">
        document.getElementById('submitBtn').addEventListener('click', async () => {
            const idea = document.getElementById('ideaInput').value.trim();
            const consentToAiProcessing = document.getElementById('consentInput').checked;
            if (!idea) {
                alert('사업 아이디어를 입력해주세요.');
                return;
            }
            if (!consentToAiProcessing) {
                alert('AI 분석을 위한 외부 API 전송에 동의해주세요.');
                return;
            }
            if (idea.length > 12000) {
                alert('입력은 12,000자 이내로 줄여주세요.');
                return;
            }

            const btn = document.getElementById('submitBtn');
            const loading = document.getElementById('loading');
            const resultDiv = document.getElementById('result');

            btn.disabled = true;
            loading.style.display = 'block';
            resultDiv.style.display = 'none';

            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ idea, consentToAiProcessing })
                });

                const data = await response.json();
                
                if (data.error) {
                    alert('오류가 발생했습니다: ' + data.error);
                } else {
                    const markdown = String(data.markdown || '');
                    const unsafeHtml = marked.parse(markdown);
                    resultDiv.innerHTML = DOMPurify.sanitize(unsafeHtml, {
                        ALLOWED_TAGS: ['a', 'blockquote', 'br', 'code', 'div', 'em', 'h1', 'h2', 'h3', 'h4', 'hr', 'img', 'li', 'ol', 'p', 'pre', 'strong', 'table', 'tbody', 'td', 'th', 'thead', 'tr', 'ul'],
                        ALLOWED_ATTR: ['align', 'alt', 'href', 'src', 'title', 'width'],
                        ALLOWED_URI_REGEXP: /^(?:(?:https?|mailto):|[^a-z]|[a-z+.-]+(?:[^a-z+.\-:]|$))/i
                    });
                    resultDiv.querySelectorAll('img').forEach((img) => {
                        try {
                            const src = new URL(img.getAttribute('src'), window.location.href);
                            if (src.hostname !== 'quickchart.io') {
                                img.remove();
                            }
                        } catch (err) {
                            img.remove();
                        }
                    });
                    resultDiv.querySelectorAll('a').forEach((link) => {
                        link.setAttribute('rel', 'noopener noreferrer');
                        link.setAttribute('target', '_blank');
                    });
                    resultDiv.style.display = 'block';
                }
            } catch (err) {
                alert('서버 통신 중 오류가 발생했습니다.');
            } finally {
                btn.disabled = false;
                loading.style.display = 'none';
            }
        });
    </script>
</body>
</html>
