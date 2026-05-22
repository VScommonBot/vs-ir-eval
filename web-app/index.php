<?php
// Single-file PHP endpoint for the lightweight IR evaluation demo app.
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
        echo json_encode(['error' => 'Request origin is not allowed.']);
        exit;
    }

    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input ?: '', true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'Request body must be valid JSON.']);
        exit;
    }

    $idea = trim((string)($input['idea'] ?? ''));
    $mode = (string)($input['mode'] ?? 'coaching');
    $language = (string)($input['language'] ?? 'en');
    $consent = $input['consentToAiProcessing'] ?? false;
    $allowed_modes = ['coaching', 'screening', 'full'];
    if (!in_array($mode, $allowed_modes, true)) {
        $mode = 'coaching';
    }
    $allowed_languages = ['en', 'ko'];
    if (!in_array($language, $allowed_languages, true)) {
        $language = 'en';
    }

    if (empty($idea)) {
        http_response_code(400);
        echo json_encode(['error' => 'Please enter a business idea or pitch.']);
        exit;
    }

    $idea_length = function_exists('mb_strlen') ? mb_strlen($idea, 'UTF-8') : strlen($idea);
    if ($idea_length > 12000) {
        http_response_code(413);
        echo json_encode(['error' => 'Please keep the input under 12,000 characters.']);
        exit;
    }

    if ($consent !== true) {
        http_response_code(400);
        echo json_encode(['error' => 'You must consent to AI analysis through the authenticated VentureSquare model gateway.']);
        exit;
    }

    $model = getenv('OPENCLAW_MODEL') ?: 'openai/gpt-5.4-mini';

    $system_prompt = <<<PROMPT
# VS IR Evaluation Framework
You are acting as an initial-stage startup review and mentoring analyst using a public VentureSquare-style framework. Do not present the output as an actual investment decision, investment recommendation, or confidential investment committee process.
## Public VentureSquare-Style Review Philosophy
1. **Team & CEO**
2. **Market Size & Growth**
3. **Product & Moat**
4. **Scalability & Exit**
5. **TIPS / LIPS Eligibility**
6. **Fatal Flaws and Red Flags**
7. **YC Requests for Startups fit**: state whether the startup is a direct match, adjacent match, no clear match, or not checked against Y Combinator's Requests for Startups page. Cite: https://www.ycombinator.com/rfs.

If the applicant is a foreign founder or overseas entity, state that Korea's startup support programs TIPS and LIPS do not apply to foreigners/overseas entities and are outside the review scope. Do not score TIPS/LIPS fit in that case; keep business viability and investment-readiness review separate.

Output strictly in the Markdown format requested, including the QuickChart radar image URL.
Keep the radar chart URL compact and do not add spaces inside the chart data array.
PROMPT;

    $prompt_file = $language === 'ko' ? 'VS_IR_EVAL.ko.prompt.md' : 'VS_IR_EVAL.prompt.md';
    $system_prompt_full = file_get_contents(__DIR__ . '/' . $prompt_file);
    // If the file exists, use it. Otherwise use the fallback string.
    if ($system_prompt_full !== false && trim($system_prompt_full) !== '') {
        $system_prompt = $system_prompt_full;
    }
    $runtime_note = 'Runtime capability note: this demo web app does not provide live web search or browsing tools. Do not claim that external verification was performed unless the user supplied sources or URLs. Mark unverifiable facts as Not performed (no browsing tool) or Unverified, and turn market, valuation, and VCS sections into search guidance/checklists when live verification is unavailable.';
    $language_note = $language === 'ko'
        ? 'Output language: Korean. Use Korean headings, labels, grades, caveats, and action items.'
        : 'Output language: English. Use English headings, labels, grades, caveats, and action items.';

    $prompt = $system_prompt . "\n\n" . $runtime_note . "\n\n" . $language_note
        . "\n\nOutput mode: " . $mode
        . "\nOutput language: " . $language
        . "\n\nEvaluate this pitch or idea:\n\n" . $idea;
    $bridge = '/Users/mupeng/.openclaw/workspace/scripts/openclaw_gateway_generate.py';
    if (!is_file($bridge)) {
        http_response_code(500);
        echo json_encode(['error' => 'Server configuration error: model gateway bridge is missing.']);
        exit;
    }
    $cmd = '/usr/bin/python3 ' . escapeshellarg($bridge)
        . ' --model ' . escapeshellarg($model)
        . ' --prompt-b64 ' . escapeshellarg(base64_encode($prompt));
    $response = shell_exec($cmd . ' 2>&1');
    $res_json = json_decode((string)$response, true);
    if (!is_array($res_json)) {
        http_response_code(502);
        echo json_encode(['error' => 'Model gateway response was not valid JSON.']);
        exit;
    }
    if (empty($res_json['ok'])) {
        error_log('Model gateway error: ' . ($res_json['error'] ?? 'unknown'));
        http_response_code(502);
        echo json_encode(['error' => 'Model gateway request failed. Please try again later.']);
        exit;
    }
    $markdown = $res_json['raw'] ?? '';
    echo json_encode(['markdown' => $markdown], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VentureSquare IR Evaluation Demo</title>
    <!-- Markdown parser and sanitizer, pinned to explicit versions. -->
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
        select {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            box-sizing: border-box;
            font-family: inherit;
            background: #fff;
            margin-bottom: 12px;
        }
        select:focus {
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
        /* VentureSquare-style Markdown rendering. */
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
            <p>Lightweight AI self-review system for public startup mentoring</p>
        </div>
        
        <p style="font-weight: bold;">Enter a public business idea or elevator pitch.</p>
        <div class="notice">
            Your input will be sent to the OpenAI API to generate the review. This demo app does not intentionally store submissions, but the server operator and API provider may process them. Do not submit confidential IR materials, personal information, contracts, source financials, term sheets, cap tables, shareholder lists, or trade secrets. The result is a mentoring reference, not investment advice or a pass/fail decision.
        </div>
        <select id="modeInput" aria-label="Output mode">
            <option value="coaching" selected>Coaching mode - mentoring without score-first framing</option>
            <option value="screening">Screening mode - one-page pre-review memo</option>
            <option value="full">Full report mode - complete diagnostic report</option>
        </select>
        <select id="languageInput" aria-label="Output language">
            <option value="en" selected>English output</option>
            <option value="ko">한국어 출력</option>
        </select>
        <textarea id="ideaInput" placeholder="Example: We are building an AI inventory-management chatbot for small merchants. Unlike traditional ERP tools, it lets owners place orders through a familiar messaging workflow..."></textarea>
        <label class="consent">
            <input id="consentInput" type="checkbox">
            <span>I consent to sending my input to an external API for AI analysis.</span>
        </label>
        
        <button id="submitBtn" class="btn">Run AI Mentoring Review</button>
        <div id="loading" class="loading">Analyzing the business through a VentureSquare-style lens... (about 15 seconds)</div>
        
        <div id="result"></div>
    </div>

    <script nonce="<?php echo htmlspecialchars($script_nonce, ENT_QUOTES, 'UTF-8'); ?>">
        document.getElementById('submitBtn').addEventListener('click', async () => {
            const idea = document.getElementById('ideaInput').value.trim();
            const mode = document.getElementById('modeInput').value;
            const language = document.getElementById('languageInput').value;
            const consentToAiProcessing = document.getElementById('consentInput').checked;
            if (!idea) {
                alert('Please enter a business idea or pitch.');
                return;
            }
            if (!consentToAiProcessing) {
                alert('Please consent to external API processing for AI analysis.');
                return;
            }
            if (idea.length > 12000) {
                alert('Please keep the input under 12,000 characters.');
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
                    body: JSON.stringify({ idea, mode, language, consentToAiProcessing })
                });

                const data = await response.json();
                
                if (data.error) {
                    alert('Error: ' + data.error);
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
                alert('A server communication error occurred.');
            } finally {
                btn.disabled = false;
                loading.style.display = 'none';
            }
        });
    </script>
</body>
</html>
