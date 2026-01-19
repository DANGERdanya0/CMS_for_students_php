<?php
header('Content-Type: application/json');

$filePath = __DIR__ . '/../../data/sites.json';

if (file_exists($filePath)) {
    $data = file_get_contents($filePath);
    echo $data;
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Data not found.']);
}
?>
