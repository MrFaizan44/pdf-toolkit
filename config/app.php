<?php
declare(strict_types=1);

define('APP_NAME', 'PDF Toolkit');
define('MAX_UPLOAD_BYTES', 25 * 1024 * 1024);
define('UPLOAD_RETENTION_SECONDS', 24 * 60 * 60);

$configuredStorage = getenv('PDF_TOOLKIT_PRIVATE_DIR');
define(
    'PRIVATE_STORAGE_DIR',
    $configuredStorage !== false && $configuredStorage !== ''
        ? rtrim($configuredStorage, DIRECTORY_SEPARATOR)
        : dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'pdf-toolkit-private'
);
