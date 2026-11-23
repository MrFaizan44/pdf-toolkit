<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function json_response(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function require_csrf_token(): void
{
    $submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        json_response(403, ['error' => 'Your session expired. Refresh the page and try again.']);
    }
}

function ensure_private_storage(): string
{
    if (!is_dir(PRIVATE_STORAGE_DIR) && !mkdir(PRIVATE_STORAGE_DIR, 0700, true) && !is_dir(PRIVATE_STORAGE_DIR)) {
        json_response(500, ['error' => 'Private storage is unavailable. Check the server folder permissions.']);
    }

    $realPath = realpath(PRIVATE_STORAGE_DIR);
    if ($realPath === false || !is_writable($realPath)) {
        json_response(500, ['error' => 'Private storage is not writable. Check the server folder permissions.']);
    }

    return $realPath;
}

function cleanup_expired_uploads(string $storageDirectory): int
{
    $cutoff = time() - UPLOAD_RETENTION_SECONDS;
    $files = glob($storageDirectory . DIRECTORY_SEPARATOR . '*');
    if ($files === false) {
        return 0;
    }

    $removed = 0;
    foreach ($files as $file) {
        $name = basename($file);
        if (
            preg_match('/\A[a-f0-9]{32}\.(pdf|doc|docx|ppt|pptx|xls|xlsx|jpg|jpeg|png)\z/', $name)
            && is_file($file)
            && filemtime($file) !== false
            && filemtime($file) < $cutoff
        ) {
            if (!unlink($file)) {
                error_log('PDF Toolkit could not remove an expired private upload.');
            } else {
                $removed++;
            }
        }
    }

    return $removed;
}
