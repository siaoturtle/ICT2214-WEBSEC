<?php
session_start();

// Block access if user is not authenticated
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized access"]);
    exit;
}

// Path to log file
$logFile = __DIR__ . '/../ssrf_attempts.log';

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

    return $score;
}

// Read log file and group logs
$logsPerPage = 30;
$groupedLogs = [];
$currentTimestamp = "";
$currentRequest = [];

if (file_exists($logFile) && is_readable($logFile)) {
    $rawLogs = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($rawLogs as $line) {
        $line = trim($line);

        // Detect timestamp (format: [YYYY-MM-DD HH:MM:SS])
        if (preg_match('/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $line)) {
            if (!empty($currentRequest)) {
                $groupedLogs[] = [
                    "timestamp" => $currentTimestamp,
                    "request" => implode("\n", $currentRequest),
                    "ssrf_likelihood" => analyzeSSRFLikelihood(implode("\n", $currentRequest))
                ];
            }
            $currentTimestamp = $line;
            $currentRequest = [$line];  // Start new request block
        } else {
            $currentRequest[] = $line; // Append log lines to array
        }
    }

    // Add last request block
    if (!empty($currentRequest)) {
        $groupedLogs[] = [
            "timestamp" => $currentTimestamp,
            "request" => implode("\n", $currentRequest),
            "ssrf_likelihood" => analyzeSSRFLikelihood(implode("\n", $currentRequest))
        ];
    }

    // ✅ Reverse logs so latest logs appear first
    $groupedLogs = array_reverse($groupedLogs);

    // ✅ Implement Pagination (Slice after reversing)
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $totalLogs = count($groupedLogs);
    $totalPages = max(1, ceil($totalLogs / $logsPerPage));
    $offset = ($page - 1) * $logsPerPage;
    $paginatedLogs = array_slice($groupedLogs, $offset, $logsPerPage);

    // ✅ Return paginated logs
    echo json_encode([
        "logs" => $paginatedLogs,
        "totalLogs" => $totalLogs,
        "currentPage" => $page,
        "totalPages" => $totalPages
    ], JSON_PRETTY_PRINT);
} else {
    http_response_code(403);
    echo json_encode(["error" => "Log file is not found or unreadable"]);
    exit;
}