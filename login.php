<?php
require 'config.php';
session_start();

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// --- 1. Get & sanitize input ---
$username = filter_input(INPUT_POST, 'uname', FILTER_SANITIZE_SPECIAL_CHARS);
$password = $_POST['pwd'] ?? null; 

if (!$username || !$password) {
    header("Location: login.html?error=missing");
    exit;
}

// --- 2. Prepare request to FastAPI ---
$payload = json_encode([
    "username" => $username,
    "password" => $password
]);

$ch = curl_init("http://127.0.0.1:8000/auth/login");

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Content-Length: " . strlen($payload)
    ],
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_TIMEOUT => 5
]);

$response = curl_exec($ch);

if ($response === false) {
    header("Location: login.html?error=server");
    exit;
}

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// --- 3. Parse response ---
$data = json_decode($response, true);

if ($http_code !== 200 || !isset($data['success']) || !$data['success']) {
    header("Location: login.html?error=invalid");
    exit;
}

// --- 4. Auth success → set session ---
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = $data['user_id'];
$_SESSION['username'] = $username;

// Optional security
session_regenerate_id(true);

// --- 5. Redirect ---
header("Location: dashboard.php");
exit;
