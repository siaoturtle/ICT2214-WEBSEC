<?php
// metadata.php: Simulated cloud metadata endpoint
require_once __DIR__ . '/logger.php';

// Log the request, marking it as the 'metadata' endpoint
logRequest('metadata');

// Respond with fake metadata
header('Content-Type: application/json');
echo json_encode([
    "instance_id" => "i-1234567890abcdef0",
    "region"      => "us-east-1",
    "ami_id"      => "ami-12345",
    "metadata"    => "This is fake metadata from the SSRF honeypot!"
]);