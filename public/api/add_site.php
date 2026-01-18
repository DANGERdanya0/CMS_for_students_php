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

if (!isset($data['name']) || !isset($data['site_link']) || !isset($data['figma_link'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters.']);
    exit;
}

$filePath = __DIR__ . '/../../data/sites.json';

if (!file_exists($filePath)) {
    http_response_code(404);
    echo json_encode(['error' => 'Data file not found.']);
    exit;
}

$sites = json_decode(file_get_contents($filePath), true);

$newSite = [
    'id' => round(microtime(true) * 1000),
    'name' => $data['name'],
    'site_link' => $data['site_link'],
    'figma_link' => $data['figma_link']
];

$sites[] = $newSite;

if (file_put_contents($filePath, json_encode($sites, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    http_response_code(201);
    echo json_encode($newSite);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save data.']);
}
?>
