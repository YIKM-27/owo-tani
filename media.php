<?php
$connection = require __DIR__ . '/config/database.php';

$id = $_GET['id'] ?? '';
if (!is_string($id) || !preg_match('/\A[a-f0-9]{32}\z/', $id)) {
    http_response_code(404);
    exit;
}

$statement = $connection->prepare('SELECT image_data FROM app_media WHERE id = ? LIMIT 1');
$statement->execute([$id]);
$imageData = $statement->fetchColumn();
if ($imageData === false) {
    http_response_code(404);
    exit;
}

header('Content-Type: image/jpeg');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=86400');
echo $imageData;
