<?php
// admin_create_user_handler.php
require 'config.php';
session_start();
if (!($_SESSION['logged_in'] ?? false) || ($_SESSION['permission'] ?? '') !== 'admin') {
    header("Location: dashboard.php"); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$uname      = trim($_POST['uname'] ?? '');
$permission = $_POST['permission'] ?? 'user';
$limit_gb   = floatval($_POST['storage_limit_gb'] ?? 1);
$limit_bytes = (int)($limit_gb * 1073741824);

if (!$uname) { header("Location: admin_create_user.php?error=missing"); exit; }

$payload = json_encode([
    'uname'         => $uname,
    'permission'    => $permission,
    'storage_limit' => $limit_bytes,
]);

$ch = curl_init(FASTAPI_URL . "/admin/create_account");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 5,
]);

$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (!$response || $http_code !== 200) {
    header("Location: admin_create_user.php?error=server&uname=" . urlencode($uname)); exit;
}

$data = json_decode($response, true);
if (!($data['success'] ?? false)) {
    $error = str_contains($data['detail'] ?? '', 'taken') ? 'taken' : 'server';
    header("Location: admin_create_user.php?error={$error}&uname=" . urlencode($uname)); exit;
}

// Show temp password on success
$tmp = urlencode($data['temp_password']);
$uid = urlencode($data['user_id']);
header("Location: admin_user.php?user_id={$uid}&notice=reset_ok&tmp={$tmp}");
exit;
