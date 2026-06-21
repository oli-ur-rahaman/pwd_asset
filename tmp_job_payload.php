<?php
$config = require __DIR__ . '/app/config.php';
$dsn = 'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['name'] . ';charset=' . $config['db']['charset'];
$pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$row = $pdo->query('SELECT id, request_payload FROM asset_download_jobs WHERE id = 17')->fetch(PDO::FETCH_ASSOC);
file_put_contents(__DIR__ . '/storage/runtime/job17_payload.json', (string)$row['request_payload']);
echo $row['request_payload'];
