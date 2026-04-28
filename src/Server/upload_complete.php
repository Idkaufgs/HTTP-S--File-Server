<?php
require 'config.php';

$curl = curl_init(FASTAPI_URL . "/files/upload/complete");
curl_setopt_array($curl, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS     => [
        'upload_id'    => $_POST['upload_id'],
        'file_name'    => $_POST['file_name'],
        'total_chunks' => $_POST['total_chunks'],
        'owner_id'     => $_POST['owner_id']
    ]
]);

$response  = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

http_response_code($http_code);
echo $response;