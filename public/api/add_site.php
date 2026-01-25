<?php
header('Content-Type: application/json');

$PASSWORD = 'pass';

if (!isset($_POST['password']) || $_POST['password'] !== $PASSWORD) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Basic validation
if (!isset($_POST['name']) || !isset($_FILES['zip_file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters. Name and zip_file are required.']);
    exit;
}

$name = $_POST['name'];
$description = $_POST['description'] ?? '';
$figma_link = $_POST['figma_link'] ?? '';

// --- ZIP File Handling ---
$zip_file = $_FILES['zip_file'];

if ($zip_file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Failed to upload ZIP file.']);
    exit;
}

// Sanitize name to create a safe directory name
$site_dir_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', str_replace(' ', '_', $name));
$unique_id = time() . '_' . uniqid();
$site_upload_dir = __DIR__ . '/../uploads/sites/' . $site_dir_name . '_' . $unique_id;
$relative_site_path = '/uploads/sites/' . $site_dir_name . '_' . $unique_id;


if (!mkdir($site_upload_dir, 0777, true)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create site directory.']);
    exit;
}

$zip_path = $site_upload_dir . '/' . basename($zip_file['name']);
if (!move_uploaded_file($zip_file['tmp_name'], $zip_path)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to move uploaded ZIP file.']);
    exit;
}

// Unzip the file
$zip = new ZipArchive;
if ($zip->open($zip_path) === TRUE) {
    // Check if there is a single root folder in the zip
    $first_entry = $zip->getNameIndex(0);
    if ($zip->numFiles > 1) {
        $is_single_root_folder = true;
        $root_folder = dirname($first_entry) === '.' ? strtok($first_entry, '/') : dirname($first_entry);

        for ($i = 1; $i < $zip->numFiles; $i++) {
            $entry_name = $zip->getNameIndex($i);
             if (strpos($entry_name, $root_folder) !== 0) {
                $is_single_root_folder = false;
                break;
            }
        }
        
        if($is_single_root_folder) {
             // Extract to temp subfolder and move contents up
            $temp_extract_path = $site_upload_dir . '/temp_extract';
            mkdir($temp_extract_path, 0777, true);
            $zip->extractTo($temp_extract_path);
            $zip->close();
            
            // Move files from the single root folder to the parent directory
            $extracted_root = $temp_extract_path . '/' . $root_folder;
            $items = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($extracted_root, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($items as $item) {
                $target = $site_upload_dir . '/' . $items->getSubPathName();
                if ($item->isDir()) {
                    if(!is_dir($target)) mkdir($target, 0777, true);
                } else {
                    rename($item, $target);
                }
            }
             // Cleanup temp folders
            rmdir($extracted_root);
            rmdir($temp_extract_path);

        } else {
             $zip->extractTo($site_upload_dir);
             $zip->close();
        }
    } else {
        $zip->extractTo($site_upload_dir);
        $zip->close();
    }
   
    unlink($zip_path); // Delete the zip file after extraction
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to unzip the file.']);
    // Clean up created directory
    rmdir($site_upload_dir);
    exit;
}

// --- Screenshots Handling ---
$screenshot_paths = [];
if (isset($_FILES['screenshots'])) {
    $screenshots_upload_dir = $site_upload_dir . '/screenshots';
    $relative_screenshots_path = $relative_site_path . '/screenshots';
    if (!mkdir($screenshots_upload_dir, 0777, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create screenshots directory.']);
        exit;
    }

    foreach ($_FILES['screenshots']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['screenshots']['error'][$key] === UPLOAD_ERR_OK) {
            $screenshot_name = basename($_FILES['screenshots']['name'][$key]);
            $screenshot_path = $screenshots_upload_dir . '/' . $screenshot_name;
            $relative_screenshot_path = $relative_screenshots_path . '/' . $screenshot_name;
            if (move_uploaded_file($tmp_name, $screenshot_path)) {
                $screenshot_paths[] = $relative_screenshot_path;
            }
        }
    }
}

// --- Save to JSON ---
$filePath = __DIR__ . '/../../data/sites.json';

$sites = [];
if (file_exists($filePath)) {
    $sites = json_decode(file_get_contents($filePath), true);
}

$newSite = [
    'id' => round(microtime(true) * 1000),
    'name' => $name,
    'description' => $description,
    'figma_link' => $figma_link,
    'site_path' => $relative_site_path, // Path to the unzipped folder
    'screenshots' => $screenshot_paths
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