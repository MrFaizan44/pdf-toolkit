<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    json_response(405, ['error' => 'Use POST to upload a document.']);
}
require_csrf_token();

if (!isset($_FILES['document']) || !is_array($_FILES['document'])) {
    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    $status = $contentLength > MAX_UPLOAD_BYTES ? 413 : 400;
    json_response($status, ['error' => $status === 413 ? 'This file is larger than the 25 MB limit.' : 'Choose a document before uploading.']);
}

$upload = $_FILES['document'];
if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => 'The file exceeds the server upload limit. Ask the administrator to adjust PHP upload limits.',
        UPLOAD_ERR_FORM_SIZE => 'This file is larger than the 25 MB limit.',
        UPLOAD_ERR_PARTIAL => 'The upload was interrupted. Please try again.',
        UPLOAD_ERR_NO_FILE => 'Choose a document before uploading.',
        UPLOAD_ERR_NO_TMP_DIR => 'The server is missing its temporary upload folder.',
        UPLOAD_ERR_CANT_WRITE => 'The server could not save this upload.',
        UPLOAD_ERR_EXTENSION => 'A server extension stopped this upload.',
    ];
    $uploadError = (int) $upload['error'];
    json_response($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE ? 413 : 400, [
        'error' => $uploadErrors[$uploadError] ?? 'The upload could not be completed.',
    ]);
}

$size = (int) ($upload['size'] ?? 0);
$temporaryPath = (string) ($upload['tmp_name'] ?? '');
if ($size < 1 || $size > MAX_UPLOAD_BYTES || !is_uploaded_file($temporaryPath)) {
    json_response($size > MAX_UPLOAD_BYTES ? 413 : 400, ['error' => 'The file is empty, too large, or could not be verified.']);
}

$clientName = (string) ($upload['name'] ?? '');
$normalizedName = basename(str_replace('\\', '/', $clientName));
$normalizedName = preg_replace('/[\x00-\x1F\x7F]/u', '', $normalizedName) ?? '';
if ($normalizedName === '' || strlen($normalizedName) > 180) {
    json_response(400, ['error' => 'The file name is invalid or too long.']);
}

$extension = strtolower(pathinfo($normalizedName, PATHINFO_EXTENSION));
$allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
if (!in_array($extension, $allowedExtensions, true)) {
    json_response(415, ['error' => 'This file format is not supported. Choose a PDF, Office document, JPG, or PNG.']);
}

$sessionUploads = $_SESSION['uploads'] ?? [];
if (!is_array($sessionUploads)) {
    $sessionUploads = [];
}
$sessionCutoff = time() - UPLOAD_RETENTION_SECONDS;
$sessionUploads = array_filter($sessionUploads, static function ($record) use ($sessionCutoff): bool {
    return is_array($record)
        && isset($record['created_at'])
        && is_numeric($record['created_at'])
        && (int) $record['created_at'] >= $sessionCutoff;
});
if (count($sessionUploads) >= 5) {
    json_response(429, ['error' => 'Remove an earlier private upload before adding more documents.']);
}
$_SESSION['uploads'] = $sessionUploads;

if (!class_exists('finfo')) {
    json_response(500, ['error' => 'Server file-type validation is unavailable. Enable the PHP fileinfo extension.']);
}
$fileInfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $fileInfo->file($temporaryPath);
if (!is_string($mimeType)) {
    json_response(400, ['error' => 'The server could not identify this file type.']);
}

$officeMimeTypes = [
    'doc' => ['application/msword', 'application/x-ole-storage', 'application/vnd.ms-office'],
    'ppt' => ['application/vnd.ms-powerpoint', 'application/x-ole-storage', 'application/vnd.ms-office'],
    'xls' => ['application/vnd.ms-excel', 'application/x-ole-storage', 'application/vnd.ms-office'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
    'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
];
$simpleMimeTypes = [
    'pdf' => ['application/pdf'],
    'jpg' => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'png' => ['image/png'],
];

if (isset($simpleMimeTypes[$extension]) && !in_array($mimeType, $simpleMimeTypes[$extension], true)) {
    json_response(415, ['error' => 'The file contents do not match the selected file format.']);
}

if (isset($officeMimeTypes[$extension]) && !in_array($mimeType, $officeMimeTypes[$extension], true)) {
    json_response(415, ['error' => 'The file contents do not match the selected Office format.']);
}

if (in_array($extension, ['doc', 'ppt', 'xls'], true)) {
    $signature = file_get_contents($temporaryPath, false, null, 0, 8);
    if ($signature !== hex2bin('D0CF11E0A1B11AE1')) {
        json_response(415, ['error' => 'This legacy Office file could not be verified.']);
    }
}

if (in_array($extension, ['docx', 'pptx', 'xlsx'], true)) {
    if (!class_exists('ZipArchive')) {
        json_response(500, ['error' => 'Office document validation requires PHP ZipArchive. Enable the zip extension in XAMPP.']);
    }
    $requiredEntry = [
        'docx' => 'word/document.xml',
        'pptx' => 'ppt/presentation.xml',
        'xlsx' => 'xl/workbook.xml',
    ][$extension];
    $archive = new ZipArchive();
    if ($archive->open($temporaryPath, ZipArchive::CHECKCONS) !== true) {
        json_response(415, ['error' => 'This Office document is not a valid document archive.']);
    }
    $isExpectedOfficeDocument = $archive->locateName('[Content_Types].xml') !== false
        && $archive->locateName($requiredEntry) !== false;
    $archive->close();
    if (!$isExpectedOfficeDocument) {
        json_response(415, ['error' => 'The file contents do not match the selected Office format.']);
    }
}

$storageDirectory = ensure_private_storage();
cleanup_expired_uploads($storageDirectory);
$uploadId = bin2hex(random_bytes(16));
$storedName = $uploadId . '.' . $extension;
$destination = $storageDirectory . DIRECTORY_SEPARATOR . $storedName;
if (!move_uploaded_file($temporaryPath, $destination)) {
    json_response(500, ['error' => 'The server could not save this upload. Please try again.']);
}

$_SESSION['uploads'][$uploadId] = [
    'name' => $normalizedName,
    'stored_name' => $storedName,
    'created_at' => time(),
];

json_response(201, [
    'id' => $uploadId,
    'name' => $normalizedName,
    'size' => $size,
    'can_edit' => $extension === 'pdf',
    'message' => 'Your document is stored in private storage.',
]);
