<?php
header('Content-Type: application/json');

$PASSWORD = 'Pass';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['password']) || $data['password'] !== $PASSWORD) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['type']) || !isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters.']);
    exit;
}

$type = $_GET['type'];
$id = $_GET['id'];
$filePath = __DIR__ . '/../../data/' . $type . '_works.json';

if (!file_exists($filePath)) {
    http_response_code(404);
    echo json_encode(['error' => 'Data file not found.']);
    exit;
}

$works = json_decode(file_get_contents($filePath), true);

// Find the work to be deleted
$workToDelete = null;
foreach ($works as $work) {
    if ($work['id'] == $id) {
        $workToDelete = $work;
        break;
    }
}

// If the work is found, delete the associated file(s)
if ($workToDelete) {
    if ($type === 'kursovie') {
        if (isset($workToDelete['doc_file_link'])) {
            $fileToDelete = __DIR__ . '/../' . $workToDelete['doc_file_link'];
            if (file_exists($fileToDelete)) {
                unlink($fileToDelete);
            }
        }
        if (isset($workToDelete['zip_file_link'])) {
            $fileToDelete = __DIR__ . '/../' . $workToDelete['zip_file_link'];
            if (file_exists($fileToDelete)) {
                unlink($fileToDelete);
            }
        }
    } else {
        if (isset($workToDelete['file_link'])) {
            $fileToDelete = __DIR__ . '/../' . $workToDelete['file_link'];
            if (file_exists($fileToDelete)) {
                unlink($fileToDelete);
            }
        }
    }
}

$filteredWorks = array_filter($works, function($work) use ($id) {
    return $work['id'] != $id;
});

// Re-index the array to prevent it from becoming an object if keys are not sequential
$filteredWorks = array_values($filteredWorks);

if (file_put_contents($filePath, json_encode($filteredWorks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    http_response_code(200);
    echo json_encode(['message' => 'Work deleted successfully.']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save data.']);
}
?>
