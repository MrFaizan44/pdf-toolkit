<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/bootstrap.php';

$storageDirectory = ensure_private_storage();
$removedCount = cleanup_expired_uploads($storageDirectory);
fwrite(STDOUT, sprintf("Removed %d expired PDF Toolkit upload(s).%s", $removedCount, PHP_EOL));
