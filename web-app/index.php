<?php
// Backend API Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $idea = $input['idea'] ?? '';

    if (empty($idea)) {
        echo json_encode(['error' => '아이디어를 입력해주세요.']);
        exit;
    }

    // 보안: 실제 배포 시 .env 등에서 읽어오도록 수정하세요.
    $api_key = 'sk-proj-**********************************'; // 서버에 배포할 때 실제 키로 교체 필요

    $system_prompt = <<<PROMPT
# VS IR Evaluation Framework
You are acting as an initial stage startup investment analyst trained in the VentureSquare investment review style. Your task is to evaluate startup business plans, pitch decks, or idea summaries using the VentureSquare investment philosophy and criteria outlined below.
## The VentureSquare Investment Philosophy
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
    if ($system_prompt_full) {
        $system_prompt = $system_prompt_full;
    }

    $data = [
        "model" => "gpt-4o",
        "messages" => [
            ["role" => "system", "content" => $system_prompt],
            ["role" => "user", "content" => "Evaluate this pitch or idea:\\n\\n" . $idea]
        ],
        "temperature" => 0.7
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);

    $response = curl_exec($ch);
    if(curl_errno($ch)){
        echo json_encode(['error' => curl_error($ch)]);
    } else {
        $res_json = json_decode($response, true);
        $markdown = $res_json['choices'][0]['message']['content'] ?? '';
        echo json_encode(['markdown' => $markdown]);
    }
    curl_close($ch);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>벤처스퀘어 간이 AI 셀프 평가</title>
    <!-- 마크다운 파서 및 CSS -->
    <script src="https://cdn.jsdelivr.net/npm/marked/markdown.min.js"></script>
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
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>VentureSquare IR Evaluation</h1>
            <p>벤처스퀘어 간이 AI 셀프 평가 시스템</p>
        </div>
        
        <p style="font-weight: bold;">당신의 사업 아이디어나 엘리베이터 피치를 자유롭게 적어주세요.</p>
        <textarea id="ideaInput" placeholder="예: 소상공인을 위한 AI 기반 재고관리 챗봇 서비스입니다. 기존 ERP와 달리 카카오톡으로 발주가 가능하며..."></textarea>
        
        <button id="submitBtn" class="btn">[간이 AI 셀프 평가] 진행하기</button>
        <div id="loading" class="loading">벤처스퀘어 뷰로 사업을 분석 중입니다... (약 15초 소요)</div>
        
        <div id="result"></div>
    </div>

    <script>
        document.getElementById('submitBtn').addEventListener('click', async () => {
            const idea = document.getElementById('ideaInput').value.trim();
            if (!idea) {
                alert('사업 아이디어를 입력해주세요.');
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
                    body: JSON.stringify({ idea })
                });

                const data = await response.json();
                
                if (data.error) {
                    alert('오류가 발생했습니다: ' + data.error);
                } else {
                    // 정규식으로 이미지 태그 수정 (URL 인코딩 등 안전장치)
                    let markdown = data.markdown;
                    resultDiv.innerHTML = marked.parse(markdown);
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
