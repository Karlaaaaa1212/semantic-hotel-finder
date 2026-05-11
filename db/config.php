<?php
// ─────────────────────────────────────────────
//  Database & API Configuration
// ─────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'hotel_db');
define('DB_USER', 'root');
define('DB_PASS', '');
// Load GEMINI_API_KEY from .env (never commit the .env file)
(function () {
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(trim($line), '#')) continue;
            [$name, $value] = array_map('trim', explode('=', $line, 2));
            if (!array_key_exists($name, $_ENV)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
            }
        }
    }
})();
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');

// ─────────────────────────────────────────────
//  PDO Connection (singleton-style)
// ─────────────────────────────────────────────
function getPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

// ─────────────────────────────────────────────
//  Gemini gemini-embedding-001  →  768-dim array
// ─────────────────────────────────────────────
function callGeminiEmbedding(string $text): array {
    $url  = 'https://generativelanguage.googleapis.com/v1beta/models/'
          . 'gemini-embedding-001:embedContent?key=' . GEMINI_API_KEY;
    $body = json_encode([
        'model'              => 'models/gemini-embedding-001',
        'content'            => ['parts' => [['text' => $text]]],
        'outputDimensionality' => 3072,
    ]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $raw = curl_exec($ch);
    $res = json_decode($raw, true);
    curl_close($ch);
    return $res['embedding']['values'] ?? [];
}

// ─────────────────────────────────────────────
//  Gemini 1.5 Flash  →  summary string
// ─────────────────────────────────────────────
function callGeminiFlash(string $prompt): string {
    $url  = 'https://generativelanguage.googleapis.com/v1beta/models/'
          . 'gemini-flash-lite-latest:generateContent?key=' . GEMINI_API_KEY;
    $body = json_encode([
        'contents' => [['parts' => [['text' => $prompt]]]],
    ]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $res['candidates'][0]['content']['parts'][0]['text'] ?? '摘要生成失敗，請稍後再試。';
}

// ─────────────────────────────────────────────
//  Gemini Flash (batch) — single API call for all hotels
//  $items: array of ['query'=>..., 'hotel'=>hotel_row, 'fac'=>string]
//  returns: array of summary strings (same order)
// ─────────────────────────────────────────────
function callGeminiFlashBatch(array $items): array {
    $count = count($items);
    $lines = "使用者查詢：{$items[0]['query']}\n\n"
           . "請為以下 {$count} 間飯店各寫 2 句推薦語，說明符合查詢需求的優點，語氣親切，不要列點。\n"
           . "請以 JSON 陣列格式回傳，例如：[\"摘要1\",\"摘要2\",...]\n\n";
    foreach ($items as $i => $it) {
        $h      = $it['hotel'];
        $lines .= "飯店" . ($i + 1) . "：{$h['name']}（{$h['region']}，{$h['stars']}星，NT\${$h['price_per_night']}/晚）\n"
               .  "設施：{$it['fac']}\n描述：{$h['description']}\n\n";
    }

    $url  = 'https://generativelanguage.googleapis.com/v1beta/models/'
          . 'gemini-flash-lite-latest:generateContent?key=' . GEMINI_API_KEY;
    $body = json_encode([
        'contents'         => [['parts' => [['text' => $lines]]]],
        'generationConfig' => ['responseMimeType' => 'application/json'],
    ]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $res  = json_decode(curl_exec($ch), true);
    curl_close($ch);

    $text      = $res['candidates'][0]['content']['parts'][0]['text'] ?? '[]';
    $summaries = json_decode($text, true);
    if (!is_array($summaries) || count($summaries) !== $count) {
        return array_fill(0, $count, '摘要生成失敗，請稍後再試。');
    }
    return $summaries;
}

// ─────────────────────────────────────────────
//  Cosine Similarity
// ─────────────────────────────────────────────
function cosineSim(array $a, array $b): float {
    $dot = 0.0; $na = 0.0; $nb = 0.0;
    $len = count($a);
    for ($i = 0; $i < $len; $i++) {
        $dot += $a[$i] * $b[$i];
        $na  += $a[$i] * $a[$i];
        $nb  += $b[$i] * $b[$i];
    }
    if ($na === 0.0 || $nb === 0.0) return 0.0;
    return $dot / (sqrt($na) * sqrt($nb));
}

// ─────────────────────────────────────────────
//  Helpers
// ─────────────────────────────────────────────
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
