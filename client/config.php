<?php

// Display errors for debugging (remove in production)
ini_set('display_errors', 1);

// Host's server.php URL
$hostURL = "http://localhost/host2gateway/host/server.php";

// Test signature content
$testSignature = 'test_signature';

// Define file paths for required keys
$serverPublicKeyFile = __DIR__ . '/keys/server.pem';
$clientPrivateKeyFile = __DIR__ . '/keys/private.pem';
$clientPublicKeyFile = __DIR__ . '/keys/public.pem';