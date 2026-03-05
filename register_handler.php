<?php
require 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// ---------------------------------------------------------------------------
// PHP-side rate limiting — file based, per IP
// ---------------------------------------------------------------------------
$rate_limit_file = sys_get_temp_dir() . '/fs_reg_rl_' . md5($_SERVER['REMOTE_ADDR']);
$max_attempts    = 5;
$window_seconds  = 60;
$now             = time();

$attempts = [];
if (file_exists($rate_limit_file)) {
    $attempts = array_filter(
        json_decode(file_get_contents($rate_limit_file), true) ?? [],
        fn($t) => ($now - $t) < $window_seconds
    );
}

if (count($attempts) >= $max_attempts) {
    header("Location: register.php?error=limited");
    exit;
}

$attempts[] = $now;
file_put_contents($rate_limit_file, json_encode(array_values($attempts)));

// ---------------------------------------------------------------------------
// Input validation
// ---------------------------------------------------------------------------
$username    = trim(filter_input(INPUT_POST, 'uname',       FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$password    = $_POST['pwd']         ?? '';
$pwd_confirm = $_POST['pwd_confirm'] ?? '';

if (!$username || !$password || !$pwd_confirm) {
    header("Location: register.php?error=missing&uname=" . urlencode($username));
    exit;
}

if (strlen($password) < 8) {
    header("Location: register.php?error=short&uname=" . urlencode($username));
    exit;
}

if ($password !== $pwd_confirm) {
    header("Location: register.php?error=mismatch&uname=" . urlencode($username));
    exit;
}

// ---------------------------------------------------------------------------
// Forward to FastAPI
// ---------------------------------------------------------------------------
$payload = json_encode(['uname' => $username, 'pwd' => $password]);

$ch = curl_init(FASTAPI_URL . "/auth/create_account");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 5,
]);

$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false) {
    header("Location: register.php?error=server&uname=" . urlencode($username));
    exit;
}

if ($http_code === 429) {
    header("Location: register.php?error=limited");
    exit;
}

$data = json_decode($response, true);

if (!($data['success'] ?? false)) {
    $error = isset($data['detail']) && str_contains($data['detail'], 'taken') ? 'taken' : 'server';
    header("Location: register.php?error={$error}&uname=" . urlencode($username));
    exit;
}

// Registration successful — log them straight in
session_regenerate_id(true);
$_SESSION['logged_in']     = true;
$_SESSION['user_id']       = $data['user_id'];
$_SESSION['username']      = $username;
$_SESSION['last_activity'] = time();

header("Location: dashboard.php");
exit;
