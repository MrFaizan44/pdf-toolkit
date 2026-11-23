<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    json_response(405, ['error' => 'Use POST to remove an upload.']);
}
require_csrf_token();

$request = json_decode((string) file_get_contents('php://input'), true);
$uploadId = is_array($request) ? ($request['id'] ?? '') : '';
if (!is_string($uploadId) || !preg_match('/\A[a-f0-9]{32}\z/', $uploadId)) {
    json_response(400, ['error' => 'The upload reference is invalid.']);
}

$uploads = $_SESSION['uploads'] ?? [];
$uploadRecord = $uploads[$uploadId] ?? null;
if (!is_array($uploadRecord) || !isset($uploadRecord['stored_name'])) {
    json_response(404, ['error' => 'This upload is no longer available.']);
}

$storedName = $uploadRecord['stored_name'];
if (!is_string($storedName) || !preg_match('/\A[a-f0-9]{32}\.(pdf|doc|docx|ppt|pptx|xls|xlsx|jpg|jpeg|png)\z/', $storedName)) {
    json_response(500, ['error' => 'The upload record could not be verified.']);
}
$filePath = PRIVATE_STORAGE_DIR . DIRECTORY_SEPARATOR . $storedName;
if (is_file($filePath) && !unlink($filePath)) {
    json_response(500, ['error' => 'The server could not remove this upload.']);
}
unset($_SESSION['uploads'][$uploadId]);

json_response(200, ['message' => 'The private upload was removed.']);
