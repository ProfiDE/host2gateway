<?php

require_once __DIR__ . '/config.php';
require __DIR__ . '/vendor/autoload.php';

use phpseclib3\Crypt\RSA;

RSA::forceEngine('PHP');

// Check if client's public key exists (must be provided manually)
if (!file_exists($clientPublicKeyFile)) {
    header('HTTP/1.1 403 Forbidden');
    die('Client public key file (client.pem) should be transferred to this directory manually to continue.');
}

// Check if server private and public keys exist; if not, generate them
if (!file_exists($serverPrivateKeyFile) || !file_exists($serverPublicKeyFile)) {
    // Generate the key pair
    try {
        $serverPrivateKey = RSA::createKey($RSAPrivateKeyBits);
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        die("Failed to generate key pair: " . $e->getMessage());
    }

    // Obtain the public key from the pair
    try {
        $serverPublicKey = $serverPrivateKey->getPublicKey();
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        die("Failed to obtain key: " . $e->getMessage());
    }
    
    // Save the private key to file
    if (file_put_contents($serverPrivateKeyFile, $serverPrivateKey->toString('PKCS8')) === false) {
        header('HTTP/1.1 500 Internal Server Error');
        die("Failed to write private key file.");
    }

    // Save the public key to file
    if (file_put_contents($serverPublicKeyFile, $serverPublicKey->toString('PKCS8')) === false) {
        header('HTTP/1.1 500 Internal Server Error');
        die("Failed to write public key file.");
    }

    // Read client's public key
    try{
        $clientPublicKey = RSA::load(file_get_contents($clientPublicKeyFile));
    } catch(Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        die("Failed to read client public key file: " . $e->getMessage());
    }

    // Encrypt server's public key sha256 hash using client's public key
    try{
        $encryptedServerPublicKeyHash = $clientPublicKey->encrypt(hash('sha256', $serverPublicKey->toString('PKCS8')));
    } catch(Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        die("Failed to encrypt public key: " . $e->getMessage() . "<br>Please remove existing server keys and retransfer client public key and try again.");
    }

    // Sign server's public key
    try{
        $signatureServerPublicKey = $serverPrivateKey->sign($serverPublicKey->toString('PKCS8'));
    } catch(Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        die("Failed to sign server's public key: " . $e->getMessage() . "<br>Please remove existing server keys and retransfer client public key and try again.");
    }
    // Return encrypted public key (base64 encoded) as JSON for client consumption
    header('Content-Type: application/json');
    
    echo json_encode([
        'status' => 'pubkey_transfer',
        'pubkey' => base64_encode($serverPublicKey->toString('PKCS8')),
        'hash' => base64_encode($encryptedServerPublicKeyHash),
        'sign' => base64_encode($signatureServerPublicKey),
    ]);
    exit;
}

// Check for the POST signature in requests; exit if missing
if (!isset($_POST['h2g_signature'])) {
    header('HTTP/1.1 404 Not Found');
    exit(0);
}

// Check if the received signature matches the test signature
if ($_POST['h2g_signature'] === $testSignature){
    die(json_encode([
        'status' => 'ok',
        'message' => 'Test signature received successfully.'
    ]));
} else {
    header('HTTP/1.1 403 Forbidden');
    die(json_encode([
        'status' => 'invalid_signature',
        'message' => 'Received signature is invalid.'
    ]));
}