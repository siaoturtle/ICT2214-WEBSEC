<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Only start session if not already started
}

// Absolute path to the log file
$LOG_FILE = __DIR__ . '/ssrf_attempts.log';

function logRequest($endpoint) {
    global $LOG_FILE;

    // Set timezone to GMT+8 (Singapore)
    date_default_timezone_set('Asia/Singapore');

    // Basic info
    $timestamp   = date('Y-m-d H:i:s');
    $clientIP    = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $method      = $_SERVER['REQUEST_METHOD'] ?? 'N/A';
    $userAgent   = $_SERVER['HTTP_USER_AGENT'] ?? 'N/A';

    // Headers
    $headers = json_encode(getallheaders(), JSON_UNESCAPED_SLASHES);

    // Query params / POST fields
    $params = print_r($_REQUEST, true); // for quick debugging, or you can just store JSON

    // Raw request body (captures POST data, JSON, XML, anything)
    $rawBody = file_get_contents('php://input');

    // Construct the log entry
    $logEntry = sprintf(
        "[%s] %s Endpoint\nIP: %s | Method: %s | User-Agent: %s\nHeaders: %s\nParams: %s\nRaw Body:\n%s\n\n",
        $timestamp,
        strtoupper($endpoint),
        $clientIP,
        $method,
        $userAgent,
        $headers,
        $params,
        $rawBody
    );

 // Ensure we can write to the directory
    if (is_writable(dirname($LOG_FILE))) {
        file_put_contents($LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
    } else {
        error_log("Unable to write to log file at $LOG_FILE");
    }

    // Send Telegram Alert AFTER saving to log
    sendTelegramAlert($logEntry);
}

// Function to send Tele alerts
function sendTelegramAlert($message) {
    $telegramToken = "7984166782:AAEt69YVZ6sQ8fVQEjyP5ej4n694cmghMa4"; // Replace with your bot token
    $chatID = "-1002310447627";
//    $chatID = "774938366";
//    $apiURL = "https://api.telegram.org/bot$telegramToken/sendMessage";
    $apiURL = "https://api.telegram.org/bot7984166782:AAEt69YVZ6sQ8fVQEjyP5ej4n694cmghMa4/sendMessage";

    // Use cURL to send Telegram message
    $postData = [
        'chat_id' => $chatID,
        'text' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
        'parse_mode' => 'HTML'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiURL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification if needed
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $curlError = curl_error($ch);

    // Debugging
    file_put_contents("/tmp/telegram_debug.log", "HTTP Code: $httpCode\nResponse: $response\nError: $curlError\n", FILE_APPEND);

    // Check for errors
    if ($httpCode != 200) {
        error_log("Telegram Notification Failed! Response: " . $response);
    }
}
?>
