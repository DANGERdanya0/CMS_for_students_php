<?php
header('Content-Type: application/json');

if (!isset($_GET['type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Type parameter is missing.']);
    exit;
}

$type = $_GET['type'];
// Basic security check to prevent directory traversal
if (strpos($type, '..') !== false || strpos($type, '/') !== false) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid type parameter.']);
    exit;
}

$filePath = __DIR__ . '/../../data/' . $type . '_works.json';

if (file_exists($filePath)) {
    $data = file_get_contents($filePath);
    echo $data;
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Data not found.']);
}
?>
