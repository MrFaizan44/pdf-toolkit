<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    json_response(405, ['error' => 'Use GET to open a private PDF.']);
}

$uploadId = $_GET['id'] ?? '';
if (!is_string($uploadId) || !preg_match('/\A[a-f0-9]{32}\z/', $uploadId)) {
    json_response(400, ['error' => 'The private PDF reference is invalid.']);
}

$uploads = $_SESSION['uploads'] ?? [];
$record = is_array($uploads) ? ($uploads[$uploadId] ?? null) : null;
$storedName = is_array($record) ? ($record['stored_name'] ?? '') : '';
$createdAt = is_array($record) ? ($record['created_at'] ?? null) : null;
if (
    !is_string($storedName)
    || $storedName !== $uploadId . '.pdf'
    || !is_numeric($createdAt)
    || (int) $createdAt < time() - UPLOAD_RETENTION_SECONDS
) {
    json_response(404, ['error' => 'This private PDF is no longer available. Upload it again to continue.']);
}

$storagePath = realpath(PRIVATE_STORAGE_DIR);
$filePath = realpath(PRIVATE_STORAGE_DIR . DIRECTORY_SEPARATOR . $storedName);
if (
    $storagePath === false
    || $filePath === false
    || dirname($filePath) !== $storagePath
    || !is_file($filePath)
    || !is_readable($filePath)
) {
    json_response(404, ['error' => 'This private PDF is no longer available. Upload it again to continue.']);
}

$fileSize = filesize($filePath);
if ($fileSize === false) {
    json_response(500, ['error' => 'The private PDF could not be read.']);
}

session_write_close();
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="pdf-toolkit-source.pdf"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
if (readfile($filePath) === false) {
    error_log('PDF Toolkit could not stream a private PDF source.');
}
exit;
