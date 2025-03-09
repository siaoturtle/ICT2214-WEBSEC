<?php
require_once __DIR__ . '/logger.php';

logRequest('metadata');

// Respond with fake metadata to simulate
header('Content-Type: application/json');
echo json_encode([
    "instance_id" => "i-1234567890abcdef0",
    "region"      => "us-east-1",
    "ami_id"      => "ami-12345",
    "metadata"    => "This is fake metadata from the SSRF honeypot!"
]);
