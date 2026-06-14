<?php

// Require configuration file
require_once __DIR__ . '/config.php';

global $hostURL, $testSignature;

// Validate the configured host URL before attempting the request
if (!filter_var($hostURL, FILTER_VALIDATE_URL)) {
    echo "URL is invalid: {$hostURL}\n";
    return;
}

// Initialize cURL session for the target gateway URL
$ch = curl_init($hostURL);

// Configure cURL options for a POST request with a sample signature payload
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => [
        'h2g_signature' => $testSignature,
    ],
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

// Execute the request and capture response status
curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$ok = curl_errno($ch) === 0 && $code >= 200 && $code < 400;
curl_close($ch);

// Report the gateway accessibility result
if ($ok) {
    echo "Gateway is accessible: {$hostURL}\n";
} else {
    die("Gateway is not accessible: {$hostURL}\n");
}

