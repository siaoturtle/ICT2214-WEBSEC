<?php 
session_start();
// Block access if user is not authenticated
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized access"]);
    exit;
}
// Path to the log file
$logFile = __DIR__ . '/ssrf_attempts.log';

// Read the log file and return all lines as a JSON response
if (file_exists($logFile) && is_readable($logFile)) {
    $logs = [];
    $logs = file($logFile); // Read all lines into an array
    header('Content-Type: application/json');
    echo json_encode($logs); // Send the logs as a JSON response
}
else {
    if (!file_exists($logFile)){
        http_response_code(403);
        echo json_encode(["error" => "Log file is not found"]);
        exit;
    }
    else if (!is_readable($logFile)) {
        http_response_code(403);
        echo json_encode(["error" => "Log file is not readable"]);
        exit;
    }
}