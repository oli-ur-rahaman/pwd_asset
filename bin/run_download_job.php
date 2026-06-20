<?php
require dirname(__DIR__) . '/app/lib/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

$jobToken = trim((string)($argv[1] ?? ''));
if ($jobToken === '') {
    fwrite(STDERR, "Missing job token.\n");
    exit(1);
}

asset_download_process_job($jobToken);
exit(0);
