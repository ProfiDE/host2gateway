<?php

// Display errors for debugging (remove in production)
ini_set('display_errors', 1);

// Define file paths for required keys
$clientPublicKeyFile = __DIR__ . '/keys/client.pem';
$serverPrivateKeyFile = __DIR__ . '/keys/private.pem';
$serverPublicKeyFile = __DIR__ . '/keys/public.pem';

// Ensure keys directory exists
if (!is_dir(__DIR__ . '/keys')) {
    mkdir(__DIR__ . '/keys', 0700, true);
}

// Check if client's public key exists (must be provided manually)
if (!file_exists($clientPublicKeyFile)) {
    die('Client public key file (client_public.pem) should be transferred to this directory manually to continue.');
}

// Check if server private and public keys exist; if not, generate them
if (!file_exists($serverPrivateKeyFile) || !file_exists($serverPublicKeyFile)) {
    // Configuration for RSA 4096 bit key pair
    $config = [
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
        "private_key_bits" => 4096,
    ];

    // Generate the key pair
    $res = openssl_pkey_new($config);

    if ($res === false) {
        die("Failed to generate key pair: " . openssl_error_string());
    }

    // Export private key from the pair
    if (!openssl_pkey_export($res, $privateKeyOut)) {
        die("Failed to export private key: " . openssl_error_string());
    }

    // Obtain the public key from the pair
    $details = openssl_pkey_get_details($res);
    if ($details === false) {
        die("Failed to get key details: " . openssl_error_string());
    }
    $publicKeyOut = $details['key'];

    // Save the private key to file
    if (file_put_contents($serverPrivateKeyFile, $privateKeyOut) === false) {
        die("Failed to write private key file.");
    }

    // Save the public key to file
    if (file_put_contents($serverPublicKeyFile, $publicKeyOut) === false) {
        die("Failed to write public key file.");
    }

    // Return public key (base64 encoded) as JSON for client consumption
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'pubkey_transfer',
        'pubkey' => base64_encode($publicKeyOut)
    ]);
    exit;
}

// Check for the POST signature in requests; exit if missing
if (!isset($_POST['h2g_signature'])) {
    header('HTTP/1.1 404 Not Found');
    exit(0);
}

if ($_POST['h2g_signature'] === 'test_signature'){
    die(json_encode([
        'status' => 'ok',
        'message' => 'Test signature received successfully.'
    ]));
}