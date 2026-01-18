<?php
header('Content-Type: application/json');

$PASSWORD = 'M1r0shk1na';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['password']) || $data['password'] !== $PASSWORD) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing id parameter.']);
    exit;
}

$id = $_GET['id'];
$filePath = __DIR__ . '/../../data/sites.json';

if (!file_exists($filePath)) {
    http_response_code(404);
    echo json_encode(['error' => 'Data file not found.']);
    exit;
}

$sites = json_decode(file_get_contents($filePath), true);

$filteredSites = array_filter($sites, function($site) use ($id) {
    return $site['id'] != $id;
});

// Re-index the array
$filteredSites = array_values($filteredSites);

if (file_put_contents($filePath, json_encode($filteredSites, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    http_response_code(200);
    echo json_encode(['message' => 'Site deleted successfully.']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save data.']);
}
?>
