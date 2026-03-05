<?php
require 'config.php';
session_start();

if (!($_SESSION['logged_in'] ?? false)) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$old_pwd         = $_POST['old_pwd']         ?? '';
$new_pwd         = $_POST['new_pwd']         ?? '';
$new_pwd_confirm = $_POST['new_pwd_confirm'] ?? '';

if (!$old_pwd || !$new_pwd || !$new_pwd_confirm) {
    header("Location: change_password.php?error=missing");
    exit;
}

if (strlen($new_pwd) < 8) {
    header("Location: change_password.php?error=short");
    exit;
}

if ($new_pwd !== $new_pwd_confirm) {
    header("Location: change_password.php?error=mismatch");
    exit;
}

$payload = json_encode([
    'user_id'      => $_SESSION['user_id'],
    'old_password' => $old_pwd,
    'new_password' => $new_pwd,
]);

$ch = curl_init(FASTAPI_URL . "/auth/change_password");
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
    header("Location: change_password.php?error=server");
    exit;
}

$data = json_decode($response, true);

if (!($data['success'] ?? false)) {
    $detail = $data['detail'] ?? '';
    $error  = str_contains($detail, 'incorrect') ? 'incorrect'
            : (str_contains($detail, 'differ')   ? 'same' : 'server');
    header("Location: change_password.php?error={$error}");
    exit;
}

// Clear the forced change flag from session
unset($_SESSION['force_password_change']);

header("Location: dashboard.php");
exit;
