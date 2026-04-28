<?php
require 'config.php';

$file_id = isset($_GET['file_id']) ? $_GET['file_id'] : null;

if (!$file_id) {
    http_response_code(400);
    exit("Missing file ID");
}

$url  = FASTAPI_URL . "/files/download/" . $file_id;
$curl = curl_init($url);
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => true,  
]);

$response    = curl_exec($curl);
$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
$headers     = substr($response, 0, $header_size);
$body        = substr($response, $header_size);
$http_code   = curl_getinfo($curl, CURLINFO_HTTP_CODE);

if ($http_code !== 200) {
    http_response_code($http_code);
    echo "(HTTP $http_code):<br>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    echo "file_id: $file_id";
    exit("\nFile not found or unavailable");
}

// Forward the filename from FastAPI's Content-Disposition header
if (preg_match('/filename="([^"]+)"/', $headers, $match)) {
    header('Content-Disposition: attachment; filename="' . $match[1] . '"');
}

header('Content-Type: application/octet-stream');
echo $body;
exit();