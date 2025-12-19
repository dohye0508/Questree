<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$logDir = '../data/logs';

// Ensure logs directory exists
if (!file_exists($logDir)) { mkdir($logDir, 0777, true); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Switch to $_POST
    $filename = $_POST['filename'] ?? '';
    $content = $_POST['content'] ?? '';

    if (!$filename || !$content) {
        echo json_encode(['error' => 'Missing fields']);
        exit;
    }

    // Sanitize filename
    $filename = basename($filename);
    $filePath = $logDir . '/' . $filename;

    // Add BOM for Excel Korean support
    $bom = "\xEF\xBB\xBF";
    if (strpos($content, $bom) !== 0) {
        $content = $bom . $content;
    }

    file_put_contents($filePath, $content, LOCK_EX);
    echo json_encode(['success' => true]);
}
?>
