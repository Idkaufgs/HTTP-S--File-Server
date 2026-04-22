<?php
require 'config.php';

$file_id = isset($_POST['file_id']) ? (string)$_POST['file_id'] : null;

if (!$file_id) {
    http_response_code(400);
    exit("Missing file ID");
}

$curl = curl_init(FASTAPI_URL . "/files/delete/" . $file_id);
curl_setopt_array($curl, [
    CURLOPT_CUSTOMREQUEST  => "DELETE",
    CURLOPT_VERBOSE        => 1,
    CURLOPT_RETURNTRANSFER => true,
]);

$response  = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

$result = json_decode($response, true);

if ($http_code === 200) {
    header("Location: dashboard.php");  // redirect back to file list on success
} else {
    http_response_code($http_code);
    exit($result['error'] ?? "Delete failed");
}
exit();