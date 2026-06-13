<?php

// Display errors for debugging (remove in production)
ini_set('display_errors', 1);

// Create or open SQLite database
$db = new SQLite3(__DIR__ . '/databases/'.date('Y-m-d').'.db');

// Create table if not exists
$db->exec('CREATE TABLE IF NOT EXISTS requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    datetime TEXT NOT NULL,
    data TEXT NOT NULL
)');

// Collect HTTP Headers
$headers = [];
foreach (getallheaders() as $name => $value) {
    $headers[$name] = $value;
}

// Collect HTTP Body
$body = file_get_contents('php://input');

// Collect additional info (request method, uri, etc.)
$request_info = [
    'method'     => $_SERVER['REQUEST_METHOD'] ?? '',
    'uri'        => $_SERVER['REQUEST_URI'] ?? '',
    'protocol'   => $_SERVER['SERVER_PROTOCOL'] ?? '',
    'remote_addr'=> $_SERVER['REMOTE_ADDR'] ?? '',
    'headers'    => $headers,
    'body'       => $body,
    'get'        => $_GET,
    'post'       => $_POST,
    'files'      => $_FILES
];

// Build JSON object
$json_data = json_encode($request_info, JSON_UNESCAPED_UNICODE);

// Save to database
$stmt = $db->prepare('INSERT INTO requests (datetime, data) VALUES (:datetime, :data)');
$stmt->bindValue(':datetime', date('c'), SQLITE3_TEXT);
$stmt->bindValue(':data', $json_data, SQLITE3_TEXT);
$stmt->execute();