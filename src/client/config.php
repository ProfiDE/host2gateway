<?php

// Display errors for debugging (remove in production)
ini_set('display_errors', 1);

// Host's server.php URL
$hostURL = "http://localhost/host2gateway/src/host/server.php";

// Test signature content
$testSignature = 'test_signature';

// Ensure keys directory exists
if (!is_dir(__DIR__ . '/keys')) {
    mkdir(__DIR__ . '/keys', 0700, true);
}

// Define file paths for required keys
$serverPublicKeyFile = __DIR__ . '/keys/server.pem';
$clientPrivateKeyFile = __DIR__ . '/keys/private.pem';
$clientPublicKeyFile = __DIR__ . '/keys/public.pem';