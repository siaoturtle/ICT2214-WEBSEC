<?php
session_start();

// Block access if user is not authenticated
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized access"]);
    exit;
}

// Path to the log file
$logFile = __DIR__ . '/../ssrf_attempts.log';

function isDNSRebinding($url) {
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host) return false;

    $ip = gethostbyname($host);
    $internalPattern = '/\b(127\.0\.0\.1|10\.\d{1,3}\.\d{1,3}\.\d{1,3}|192\.168\.\d{1,3}\.\d{1,3}|172\.(1[6-9]|2\d|3[01])\.\d{1,3}\.\d{1,3}|::1|fc00::|fd00::|fe80::|fec0::)\b/';

    return preg_match($internalPattern, $ip);
}

// Function to analyze SSRF likelihood
function analyzeSSRFLikelihood($requestBlock) {
    $score = 0;

    // Regex patterns for SSRF indicators
    $internalIPPattern = '/\b(127\.0\.0\.1|10\.\d{1,3}\.\d{1,3}\.\d{1,3}|192\.168\.\d{1,3}\.\d{1,3}|172\.(1[6-9]|2\d|3[01])\.\d{1,3}\.\d{1,3}|::1|fc00::|fd00::|fe80::|fec0::)\b/';
    $dangerousSchemePattern = '/\b(file|dict|gopher|ftp|php|smb|tftp|ldap|dns|http|https|javascript|data|vbscript|chrome|about):\/\//i';
    $suspiciousHostnamePattern = '/(localhost|\.local|metadata\.google\.internal)/i';
    $requestBlockDecoded = urldecode($requestBlock);

    if (preg_match($internalIPPattern, $requestBlock) || preg_match($internalIPPattern, $requestBlockDecoded)) {
        $score++;
    }
    if (preg_match($dangerousSchemePattern, $requestBlock) || preg_match($dangerousSchemePattern, $requestBlockDecoded)) {
        $score++;
    }
    if (preg_match($suspiciousHostnamePattern, $requestBlock) || preg_match($suspiciousHostnamePattern, $requestBlockDecoded)) {
        $score++;
    }
   if (isDNSRebinding($requestBlock)) {
    $score += 2; // DNS rebinding is critical
}
if (isDNSRebinding($requestBlock)) {
    $score += 2; // DNS rebinding is critical
}


    return $score;
}

// Read log file and group log entries by timestamp
$logs = [];
if (file_exists($logFile) && is_readable($logFile)) {
    $rawLogs = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); // Read lines
    $groupedLogs = [];
    $currentTimestamp = "";
    $currentRequest = "";

    foreach ($rawLogs as $line) {
        // Detect timestamp (assumes format: [YYYY-MM-DD HH:MM:SS])
        if (preg_match('/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $line)) {
            if (!empty($currentRequest)) {
                $groupedLogs[] = [
                    "timestamp" => $currentTimestamp,
                    "request" => $currentRequest,
                    "ssrf_likelihood" => analyzeSSRFLikelihood($currentRequest) . " out of 3"
                ];
            }
            $currentTimestamp = $line;
            $currentRequest = $line . "\n";  // Start new request block
        } else {
            $currentRequest .= $line . "\n";  // Append log lines to the current request block
        }
    }

    // Add last request block
    if (!empty($currentRequest)) {
        $groupedLogs[] = [
            "timestamp" => $currentTimestamp,
            "request" => $currentRequest,
            "ssrf_likelihood" => analyzeSSRFLikelihood($currentRequest) . " out of 3"
        ];
    }

    header('Content-Type: application/json');
    echo json_encode(["logs" => $groupedLogs], JSON_PRETTY_PRINT);
} else {
    http_response_code(403);
    echo json_encode(["error" => "Log file is not found or unreadable"]);
    exit;
}

// Automatically scan logs marked with SSRF likelihood >= 2
foreach ($logs as $log) {
    if ($log['ssrf_likelihood'] >= 2) {
        file_get_contents("http://localhost:5000/scan", false, stream_context_create([
            'http' => ['method' => 'POST', 'header' => 'Content-Type: application/json', 'content' => json_encode(['url' => $log['request']])]
        ]));
    }
}