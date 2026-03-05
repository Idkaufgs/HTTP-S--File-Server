<?php
// admin_set_limit_handler.php
require 'config.php';
session_start();
if (!($_SESSION['logged_in'] ?? false) || ($_SESSION['permission'] ?? '') !== 'admin') {
    header("Location: dashboard.php"); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$user_id  = $_POST['user_id'] ?? '';
$limit_gb = floatval($_POST['storage_limit_gb'] ?? 1);
$limit_bytes = (int)($limit_gb * 1073741824);

if (!$user_id) { header("Location: admin_users.php"); exit; }

$payload = json_encode(['storage_limit' => $limit_bytes]);

$ch = curl_init(FASTAPI_URL . "/admin/set_storage_limit/" . urlencode($user_id));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 5,
]);

$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

$notice = ($http_code === 200) ? 'limit_ok' : 'limit_err';
header("Location: admin_user.php?user_id=" . urlencode($user_id) . "&notice={$notice}");
exit;
