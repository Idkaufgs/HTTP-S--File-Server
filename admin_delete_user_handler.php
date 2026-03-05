<?php
require 'config.php';
session_start();
if (!($_SESSION['logged_in'] ?? false) || ($_SESSION['permission'] ?? '') !== 'admin') {
    header("Location: dashboard.php"); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$user_id = $_POST['user_id'] ?? '';
if (!$user_id) { header("Location: admin_users.php"); exit; }

$ch = curl_init(FASTAPI_URL . "/admin/delete_user/" . urlencode($user_id));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST  => 'DELETE',
    CURLOPT_TIMEOUT        => 10, // longer timeout — deleting files can take a moment
]);

$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

header("Location: admin_users.php");
exit;
