<?php

// Display errors for debugging (remove in production)
ini_set('display_errors', 1);

// Test signature content
$testSignature = 'test_signature';

// Ensure keys directory exists
if (!is_dir(__DIR__ . '/keys')) {
    mkdir(__DIR__ . '/keys', 0700, true);
}

// Define file paths for required keys
$clientPublicKeyFile = __DIR__ . '/keys/client.pem';
$serverPrivateKeyFile = __DIR__ . '/keys/private.pem';
$serverPublicKeyFile = __DIR__ . '/keys/public.pem';

// Configure private key length (Bits)
$RSAPrivateKeyBits = 4096;
