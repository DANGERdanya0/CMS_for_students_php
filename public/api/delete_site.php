<?php
header('Content-Type: application/json');

$PASSWORD = 'pass';

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

$site_to_delete = null;
$filteredSites = [];
foreach($sites as $site) {
    if ($site['id'] == $id) {
        $site_to_delete = $site;
    } else {
        $filteredSites[] = $site;
    }
}


if ($site_to_delete) {
    if (isset($site_to_delete['site_path'])) {
        $site_dir = __DIR__ . '/..' . $site_to_delete['site_path'];
        if (is_dir($site_dir)) {
            // Function to recursively delete a directory
            function deleteDir($dirPath) {
                if (!is_dir($dirPath)) {
                    return;
                }
                if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
                    $dirPath .= '/';
                }
                $files = glob($dirPath . '*', GLOB_MARK);
                foreach ($files as $file) {
                    if (is_dir($file)) {
                        deleteDir($file);
                    } else {
                        unlink($file);
                    }
                }
                rmdir($dirPath);
            }

            deleteDir($site_dir);
        }
    }
}


// Re-index the array is not needed here as we build a new array
// $filteredSites = array_values($filteredSites);

if (file_put_contents($filePath, json_encode($filteredSites, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    http_response_code(200);
    echo json_encode(['message' => 'Site deleted successfully.']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save data.']);
}
?>