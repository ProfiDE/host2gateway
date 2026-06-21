<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use phpseclib3\Crypt\RSA;

// Validate the configured host URL before attempting the request
if (!filter_var($hostURL, FILTER_VALIDATE_URL)) {
    echo "Host's URL is invalid: {$hostURL}\n";
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

// Execute the request and capture response
$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$ok = curl_errno($ch) === 0 && $code >= 200 && $code < 400;

// If the response is JSON, decode it to an array and store in $responseData
$responseData = null;
if (is_string($response)) {
    $decoded = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $responseData = $decoded;
        echo "Received JSON response: '" . json_encode($responseData) . "'<br>";
    }
}

curl_close($ch);

// Report the gateway accessibility result
if ($ok) {
    echo "Gateway is accessible: {$hostURL}<br>";
} else {
    echo("Gateway is not accessible: {$hostURL}<br>");
}

// Check if client private and public keys exist; if not, generate them
if (!file_exists($clientPrivateKeyFile) || !file_exists($clientPublicKeyFile)) {
    // Generate the key pair
    try {
        $clientPrivateKey = RSA::createKey($RSAPrivateKeyBits);
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Client Error');
        die("Failed to generate key pair: " . $e->getMessage());
    }

    // Obtain the public key from the pair
    try {
        $clientPublicKey = $clientPrivateKey->getPublicKey();
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Client Error');
        die("Failed to obtain key: " . $e->getMessage());
    }

    // Save the private key to file
    if (file_put_contents($clientPrivateKeyFile, $clientPrivateKey->toString('PKCS8')) === false) {
        header('HTTP/1.1 500 Internal Client Error');
        die("Failed to write private key file.");
    }

    // Save the public key to file
    if (file_put_contents($clientPublicKeyFile, $clientPublicKey->toString('PKCS8')) === false) {
        header('HTTP/1.1 500 Internal Client Error');
        die("Failed to write public key file.");
    }

    die("Client key pair generated successfully. Please manually copy the client's public key (public.pem) to the host's keys directory, rename it to client.pem, then refresh this page.");
}

// Check if the response contains a server public key transfer instruction and handle it
if (isset($responseData['status']) && $responseData['status'] === 'pubkey_transfer' && isset($responseData['pubkey']) && isset($responseData['sign'])) {
    // Decode the base64-encoded public key from the response
    $serverPublicKeyString = base64_decode($responseData['pubkey']);
    if ($serverPublicKeyString === false) {
        die("Failed to decode server's public key from response.");
    }

    echo "Server public key received successfully. Checking signature...<br>";

    // Decode the base64-encoded signature from the response
    $signature = base64_decode($responseData['sign']);
    if ($signature === false) {
        die("Failed to decode signature from response.");
    }
    
    // Try to load the server's public key
    try {
        $serverPublicKey = RSA::load($serverPublicKeyString);
    } catch (Exception $e) {
        die("Failed to load server's public key: " . $e->getMessage());
    }

    // Verify the signature of the server's public key
    try {
        $isValid = $serverPublicKey->withPadding(RSA::SIGNATURE_PSS)->verify($serverPublicKeyString, $signature);
        if ($isValid) {
            echo "Signature is valid!<br>";
        }else {
            die("Signature is invalid. It seems the server's public key has been tampered with. Please be cautious and do not proceed.");
        }
    } catch (Exception $e) {
        die("Failed to verify the signature of server's public key: " . $e->getMessage());
    }

    // Save the server's public key to file for future use
    if (file_put_contents($serverPublicKeyFile, $serverPublicKeyString)) {
        die("Server's public key saved successfully. Now everything is ready! Use cron to run this file every second. You can use this client's key pair on other host2gateway clients at the same time.");
    }else{
        die("Failed to write server public key file.");
    }
}

