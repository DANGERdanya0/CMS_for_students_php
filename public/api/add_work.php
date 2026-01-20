<?php
header('Content-Type: application/json');

$PASSWORD = 'Pass';

if (!isset($_POST['password']) || $_POST['password'] !== $PASSWORD) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_POST['type']) || !isset($_POST['name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters.']);
    exit;
}

$type = $_POST['type'];
$name = $_POST['name'];
$filePath = __DIR__ . '/../../data/' . $type . '_works.json';
$uploadsDir = __DIR__ . '/../uploads/';

if ($type === 'kursovie') {
    $docFileLink = '';
    $zipFileLink = '';

    if (isset($_FILES['doc_file'])) {
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }
        $originalName = basename($_FILES['doc_file']['name']);
        $newFileName = round(microtime(true) * 1000) . '-' . $originalName;
        $targetFile = $uploadsDir . $newFileName;

        if (move_uploaded_file($_FILES['doc_file']['tmp_name'], $targetFile)) {
            $docFileLink = 'uploads/' . $newFileName;
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to upload documentation file.']);
            exit;
        }
    }

    if (isset($_FILES['zip_file'])) {
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }
        $originalName = basename($_FILES['zip_file']['name']);
        $newFileName = round(microtime(true) * 1000) . '-' . $originalName;
        $targetFile = $uploadsDir . $newFileName;

        if (move_uploaded_file($_FILES['zip_file']['tmp_name'], $targetFile)) {
            $zipFileLink = 'uploads/' . $newFileName;
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to upload zip file.']);
            exit;
        }
    }

    $newWork = [
        'id' => round(microtime(true) * 1000),
        'name' => $name,
        'date' => date('d.m.Y'),
        'doc_file_link' => $docFileLink,
        'zip_file_link' => $zipFileLink
    ];
} else {
    // --- File Upload ---
    $fileLink = '';
    if (isset($_FILES['file'])) {
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }
        $originalName = basename($_FILES['file']['name']);
        $newFileName = round(microtime(true) * 1000) . '-' . $originalName;
        $targetFile = $uploadsDir . $newFileName;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
            $fileLink = 'uploads/' . $newFileName;
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to upload file.']);
            exit;
        }
    }
    $newWork = [
        'id' => round(microtime(true) * 1000), // PHP equivalent of Date.now()
        'name' => $name,
        'date' => date('d.m.Y'),
        'file_link' => $fileLink
    ];
}

// --- Read, Update, Write JSON ---
if (!file_exists($filePath)) {
    http_response_code(404);
    echo json_encode(['error' => 'Data file not found.']);
    exit;
}

$works = json_decode(file_get_contents($filePath), true);

$works[] = $newWork;

if (file_put_contents($filePath, json_encode($works, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    http_response_code(201);
    echo json_encode($newWork);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save data.']);
}
?>
