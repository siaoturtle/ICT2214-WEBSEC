<?php
session_start();

// Block access if user is not authenticated
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized access"]);
    exit;
}

//SSRF Likelihood count
$ssrfCounts = [
    "High" => 0,
    "Medium" => 0,
    "Low" => 0,
    "Non-Vulnerable" => 0
];


// Path to log file
$logFile = __DIR__ . '/../ssrf_attempts.log';
$logs = file_exists($logFile) ? array_reverse(file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) : [];

// Initialize attack counters for each severity level per day - for attack trend graph
// Get the current timestamp (start of today)
$now = strtotime(date('Y-m-d 23:59:59'));
$daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];


// Initialize attack counters with weekday + date (past 7 days)
$attackCounts = [];

for ($i = 6; $i >= 0; $i--) {  // Get past 7 days
    $date = strtotime("-$i days"); // Get the timestamp for each day
    $formattedDate = date('j/n', $date); // Format: "11/3" (day/month)
    $dayOfWeek = date('l', $date); // "Monday", "Tuesday", etc.

    // Store combined label (e.g., "Monday (11/3)")
    $label = "$dayOfWeek ($formattedDate)";
    $attackCounts[$label] = ['Non-Vulnerable' => 0, 'Low' => 0, 'Medium' => 0, 'High' => 0];
}



function getSeverity($score) {
    if ($score >= 3) return 'High';
    if ($score == 2) return 'Medium';
    if ($score == 1) return 'Low';
    return 'Non-Vulnerable';
}
function analyzeSSRFLikelihood($requestBlock) {
    $score = 0;
    $requestBlockDecoded = urldecode($requestBlock);

    // Detect internal/private IPs (exclude public IPs)
    $internalIPPattern = '/\b(127\.0\.0\.1|10\.\d{1,3}\.\d{`1,3}\.\d{1,3}|192\.168\.\d{1,3}\.\d{1,3}|172\.(1[6-9]|2\d|3[01])\.\d{1,3}\.\d{1,3}|::1|fc00::|fd00::|fe80::|fec0::)\b/';

    // Detect **ONLY dangerous** URL schemes (exclude http/https)
    $dangerousSchemePattern = '/\b(file|dict|gopher|smb|tftp|ldap|dns|javascript|data|vbscript|chrome|about):\/\//i';

    // Detect only **internal** suspicious hostnames
    $suspiciousHostnamePattern = '/\b(localhost|\.local|metadata\.google\.internal|169\.254\.169\.254|aws\.amazonaws\.com|kubernetes.default|consul|etcd|vault|internal)\b/i';

    // Detect high-risk ports **(exclude common ones)**
    $portScanPattern = '/:(22|23|53|110|143|389|4433|3306|6379|8081|8443|9200|27017|5000|8000|8888|9090|9092|50051)/';

    // Detect hexadecimal/obfuscated IPs
    $hexIPPattern = '/(0x[0-9a-fA-F]+)/';

    // Detect wildcard SSRF fuzzing (*.internal, *.corp)
    $wildcardPattern = '/\*\.[a-zA-Z0-9\-]+/';

    // Detect **non-public** domains
    $publicDomainPattern = '/\b(example\.com|google\.com|github\.com|amazon\.com|microsoft\.com|wikipedia\.org)\b/';

    // **Apply rules with refinements**
    if (preg_match($internalIPPattern, $requestBlock) || preg_match($internalIPPattern, $requestBlockDecoded)) {
        $score += 2; // High risk (internal IPs)
    }
    if (preg_match($dangerousSchemePattern, $requestBlock) || preg_match($dangerousSchemePattern, $requestBlockDecoded)) {
        $score += 2; // High risk (bad schemes)
    }
    if (preg_match($suspiciousHostnamePattern, $requestBlock) || preg_match($suspiciousHostnamePattern, $requestBlockDecoded)) {
        $score += 2; // High risk (internal hostnames)
    }
    if (preg_match($portScanPattern, $requestBlock) || preg_match($portScanPattern, $requestBlockDecoded)) {
        $score += 1; // Medium risk (scanning uncommon ports)
    }
    if (preg_match($hexIPPattern, $requestBlock) || preg_match($hexIPPattern, $requestBlockDecoded)) {
        $score += 1; // Medium risk (obfuscated addresses)
    }
    if (preg_match($wildcardPattern, $requestBlock) || preg_match($wildcardPattern, $requestBlockDecoded)) {
        $score += 1; // Low risk (fuzzing but not necessarily dangerous)
    }

    // **Subtract points if it's a known safe domain**
    if (preg_match($publicDomainPattern, $requestBlock) || preg_match($publicDomainPattern, $requestBlockDecoded)) {
        $score -= 1; // Reduce false positives
    }

    return max(0, $score); // Ensure score doesn't go below 0
}

// Read log file and group logs
$logsPerPage = 30;
$groupedLogs = [];
$currentTimestamp = "";
$currentRequest = [];



//Process logs for attack trend graph
foreach ($logs as $log) {
    if (preg_match('/\[(.*?)\]/', $log, $match)) {
        $timestamp = strtotime($match[1]);
        if ($timestamp && $timestamp <= $now) {  // Only include logs up to today
            $formattedDate = date('j/n', $timestamp); // "11/3"
            $dayOfWeek = date('l', $timestamp); // "Monday"
            $label = "$dayOfWeek ($formattedDate)"; // e.g., "Monday (11/3)"

            $severity = getSeverity(analyzeSSRFLikelihood($log));

            // Increment severity count if the label exists in $attackCounts
            if (isset($attackCounts[$label])) {
                $attackCounts[$label][$severity]++;
            }
        }
    }
}


//Process logs for statistics graph
if (file_exists($logFile) && is_readable($logFile)) {
    $rawLogs = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($rawLogs as $line) {
        $line = trim($line);

        // Detect timestamp (format: [YYYY-MM-DD HH:MM:SS])
        if (preg_match('/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $line)) {
            if (!empty($currentRequest)) {
                $score = analyzeSSRFLikelihood(implode("\n", $currentRequest));
                $severity = getSeverity($score);
                $ssrfCounts[$severity]++;

                // Convert score to severity categories
                if ($score >= 3) {
                    $severity = 'High';

                } elseif ($score == 2) {
                    $severity = 'Medium';

                } elseif ($score == 1) {
                    $severity = 'Low';

                } else {
                    $severity = 'Non-Vulnerable';

                }
                $groupedLogs[] = [
                    "timestamp" => $currentTimestamp,
                    "request" => implode("\n", $currentRequest),
                    "ssrf_likelihood" => analyzeSSRFLikelihood(implode("\n", $currentRequest))
                ];
            }
            $currentTimestamp = $line;
            $currentRequest = [$line];  // Start new request block
        }
        else {
            $currentRequest[] = $line; // Append log lines to array
        }
    }

    // Add last request block
    if (!empty($currentRequest)) {
        $score = analyzeSSRFLikelihood(implode("\n", $currentRequest));
        $severity = getSeverity($score);
        $ssrfCounts[$severity]++;


        $groupedLogs[] = [
            "timestamp" => $currentTimestamp,
            "request" => implode("\n", $currentRequest),
            "ssrf_likelihood" => $score
        ];
    }

    // Filter logs: Only include logs up to the current day
    $filteredLogs = [];
    foreach ($groupedLogs as $logEntry) {
        if (isset($logEntry['timestamp'])) {
            $logTimestamp = strtotime($logEntry['timestamp']);
            if ($logTimestamp && $logTimestamp <= $now) {  // Ensure it's not from the future
                $filteredLogs[] = $logEntry;
            }
        }
    }


    // Reverse logs so latest logs appear first
    $groupedLogs = array_reverse($groupedLogs);

    // Implement Pagination (Slice after reversing)
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $totalLogs = count($groupedLogs);
    $totalPages = max(1, ceil($totalLogs / $logsPerPage));
    $offset = ($page - 1) * $logsPerPage;
    $paginatedLogs = array_slice($groupedLogs, $offset, $logsPerPage);

    // Return paginated logs
    echo json_encode([
        "logs" => $paginatedLogs,
        "totalLogs" => $totalLogs,
        "currentPage" => $page,
        "totalPages" => $totalPages,
        "ssrfCounts" => $ssrfCounts, // Includes count of each risk level
        "attackCounts" => $attackCounts // Per-day attack counts by severity
    ], JSON_PRETTY_PRINT);
}
else {
    http_response_code(403);
    echo json_encode(["error" => "Log file is not found or unreadable"]);
    exit;
}