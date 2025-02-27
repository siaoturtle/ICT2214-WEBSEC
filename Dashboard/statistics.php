v<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: login.php");
    exit;
}

// Read log file
$logFile = __DIR__ . "/../ssrf_attempts.log";
$logs = file_exists($logFile) ? file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

$totalAttacks = count($logs);
$highRisk = 0;
$endpoints = [];
$userAgents = [];

foreach ($logs as $log) {
    if (preg_match('/\[(.*?)\].*?(GET|POST) (.*?) HTTP/', $log, $match)) {
        $endpoints[] = $match[3];
    }
    if (preg_match('/User-Agent:\s(.*?)"/', $log, $match)) {
        $userAgents[] = $match[1];
    }
    if (preg_match('/(3 out of 3)/', $log)) {
        $highRisk++;
    }
}

// Get most targeted endpoint and user agent
$commonEndpoint = array_count_values($endpoints);
arsort($commonEndpoint);
$topEndpoint = key($commonEndpoint);

$commonUserAgent = array_count_values($userAgents);
arsort($commonUserAgent);
$topUserAgent = key($commonUserAgent);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SSRF Attack Statistics</title>
</head>
<body>
    <h2>SSRF Attack Statistics</h2>
    <ul>
        <li><strong>Total SSRF Attempts:</strong> <?php echo $totalAttacks; ?></li>
        <li><strong>High-Risk Attacks (3/3 Likelihood):</strong> <?php echo $highRisk; ?></li>
        <li><strong>Most Targeted Endpoint:</strong> <?php echo $topEndpoint . " (" . $commonEndpoint[$topEndpoint] . " times)"; ?></li>
        <li><strong>Most Used User-Agent:</strong> <?php echo $topUserAgent . " (" . $commonUserAgent[$topUserAgent] . " times)"; ?></li>
    </ul>
</body>
</html>
