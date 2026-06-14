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
    echo "Gateway is accessible: {$hostURL}<br>";
} else {
    die("Gateway is not accessible: {$hostURL}<br>");
}

// Check if server private and public keys exist; if not, generate them
if (!file_exists($clientPrivateKeyFile) || !file_exists($clientPublicKeyFile)) {
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
    if (file_put_contents($clientPrivateKeyFile, $privateKeyOut) === false) {
        die("Failed to write private key file.");
    }

    // Save the public key to file
    if (file_put_contents($clientPublicKeyFile, $publicKeyOut) === false) {
        die("Failed to write public key file.");
    }

    die("Client key pair generated successfully. Please manually copy the client's public key (public.pem) to the host's keys directory, rename it to client.pem, then refresh this page.");
}