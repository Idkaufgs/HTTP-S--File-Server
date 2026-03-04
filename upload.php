<?php
require 'config.php';

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    exit("No file uploaded or upload error");
}

$curl = curl_init(FASTAPI_URL . "/files/upload");
curl_setopt_array($curl, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTREDIR      => CURL_REDIR_POST_ALL,
    CURLOPT_VERBOSE        => 1,
    CURLOPT_POSTFIELDS     => [
        'file_'     => new CURLFile(
                          $_FILES['file']['tmp_name'],
                          mime_content_type($_FILES['file']['tmp_name']),
                          $_FILES['file']['name']
                      ),
        'owner_id' => $_POST['owner_id']
    ]
]);

$response  = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

if ($http_code === 200) {
    header("Location: dashboard.php");
} else {
    echo "Upload failed (HTTP $http_code):<br>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    exit();
}
exit();