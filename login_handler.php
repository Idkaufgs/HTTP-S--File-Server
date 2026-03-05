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
$rate_limit_file = sys_get_temp_dir() . '/fs_login_rl_' . md5($_SERVER['REMOTE_ADDR']);
$max_attempts    = 10;
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
    header("Location: login.php?error=limited");
    exit;
}

$attempts[] = $now;
file_put_contents($rate_limit_file, json_encode(array_values($attempts)));

// ---------------------------------------------------------------------------
// Input validation
// ---------------------------------------------------------------------------
$username = filter_input(INPUT_POST, 'uname', FILTER_SANITIZE_SPECIAL_CHARS);
$password = $_POST['pwd'] ?? null;

if (!$username || !$password) {
    header("Location: login.php?error=missing");
    exit;
}

// ---------------------------------------------------------------------------
// Forward to FastAPI
// ---------------------------------------------------------------------------
$payload = json_encode(['username' => $username, 'password' => $password]);

$ch = curl_init(FASTAPI_URL . "/auth/login");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ],
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 5,
]);

$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false) {
    header("Location: login.php?error=server");
    exit;
}

if ($http_code === 429) {
    header("Location: login.php?error=limited");
    exit;
}

$data = json_decode($response, true);

if ($http_code !== 200 || !($data['success'] ?? false)) {
    header("Location: login.php?error=invalid");
    exit;
}

// ---------------------------------------------------------------------------
// Set session
// ---------------------------------------------------------------------------
session_regenerate_id(true);
$_SESSION['logged_in']     = true;
$_SESSION['user_id']       = $data['user_id'];
$_SESSION['username']      = $username;
$_SESSION['permission']    = $data['permission'] ?? 'user';
$_SESSION['last_activity'] = time();

if ($data['force_password_change'] ?? false) {
    $_SESSION['force_password_change'] = true;
    header("Location: change_password.php");
    exit;
}

header("Location: dashboard.php");
exit;