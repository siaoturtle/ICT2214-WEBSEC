<?php
    require_once __DIR__ . '/logger.php';
    logRequest('api');

// Return fake JSON data to simulate 
header('Content-Type: application/json');
echo json_encode([
    "status" => "success",
    "data"   => [
        "message" => "Hello from the internal API!",
        "version" => "v1.2.3"
    ]
]);
