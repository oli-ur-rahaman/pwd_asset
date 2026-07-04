<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/lib/bootstrap.php';

$jobToken = $argv[1] ?? '';
$jobToken = trim((string)$jobToken);
if ($jobToken === '') {
    fwrite(STDERR, "Missing job token.\n");
    exit(1);
}

asset_project_backup_process_job($jobToken);
exit(0);
