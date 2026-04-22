<?php
require 'config.php';
session_start();
if (!($_SESSION['logged_in'] ?? false) || ($_SESSION['permission'] ?? '') !== 'admin') {
    header("Location: dashboard.php"); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$user_id = $_POST['user_id'] ?? '';
if (!$user_id) { header("Location: admin_users.php"); exit; }

$payload = json_encode(['user_id' => $user_id]);

$ch = curl_init(FASTAPI_URL . "/admin/force_password_change/" . urlencode($user_id));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 5,
]);

$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$data      = json_decode($response, true);

if ($http_code === 200 && ($data['success'] ?? false)) {
    $tmp = urlencode($data['temp_password']);
    header("Location: admin_user.php?user_id=" . urlencode($user_id) . "&notice=reset_ok&tmp={$tmp}");
} else {
    header("Location: admin_user.php?user_id=" . urlencode($user_id) . "&notice=error");
}
exit;
