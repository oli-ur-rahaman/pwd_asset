<?php
session_start();
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/lib/db.php';
require __DIR__ . '/app/lib/helpers.php';
require __DIR__ . '/app/lib/auth.php';
require __DIR__ . '/app/lib/asset.php';
$config = require __DIR__ . '/app/config.php';
$dbConfig = $config['db'];
$dsn = 'mysql:host=' . $dbConfig['host'] . ';dbname=' . $dbConfig['name'] . ';charset=' . $dbConfig['charset'];
$GLOBALS['__db'] = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$user = db()->query("SELECT * FROM users WHERE email_id = 'superadmin@pwd.gov.bd' LIMIT 1")->fetch();
$_SESSION['user'] = $user;
$request = json_decode(file_get_contents(__DIR__ . '/storage/runtime/job17_payload.json'), true);
$context = asset_download_runtime_context($request, $user);
$groups = asset_download_dataset($request, $user, $context);
echo 'groups=' . count($groups) . PHP_EOL;
$first = array_slice($groups, 0, 5, true);
foreach ($first as $groupValue => $group) {
    echo 'GROUP: ' . $groupValue . PHP_EOL;
    foreach (($group['segments'] ?? []) as $segmentId => $segmentData) {
        echo '  SEGMENT ' . $segmentId . ' rows=' . count($segmentData['assets'] ?? []) . PHP_EOL;
    }
}
$target = 'Division - Bagerhat PWD Division';
if (isset($groups[$target])) {
    echo 'FOUND TARGET' . PHP_EOL;
    foreach (($groups[$target]['segments'] ?? []) as $segmentId => $segmentData) {
        echo '  SEGMENT ' . $segmentId . ' rows=' . count($segmentData['assets'] ?? []) . PHP_EOL;
    }
}
