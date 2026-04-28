<?php
require 'config.php';

$curl = curl_init(FASTAPI_URL . "/files/upload/chunk");
curl_setopt_array($curl, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS     => [
        'chunk'       => new CURLFile(
                             $_FILES['chunk']['tmp_name'],
                             'application/octet-stream',
                             $_FILES['chunk']['name']
                         ),
        'upload_id'   => $_POST['upload_id'],
        'chunk_index' => $_POST['chunk_index'],
        'owner_id'    => $_POST['owner_id']
    ]
]);

$response  = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

http_response_code($http_code);
echo $response;