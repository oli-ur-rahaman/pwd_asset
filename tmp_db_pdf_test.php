<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/lib/bootstrap.php';
$cfg = require __DIR__ . '/app/config.php';
$dsn = 'mysql:host=' . $cfg['db']['host'] . ';dbname=' . $cfg['db']['name'] . ';charset=' . $cfg['db']['charset'];
$pdo = new PDO($dsn, $cfg['db']['user'], $cfg['db']['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$row = $pdo->query("SELECT av.value_text FROM asset_values av JOIN asset_fields af ON af.id = av.field_id WHERE af.label = 'Name of the Hospital' AND av.value_text IS NOT NULL AND av.value_text <> '' AND av.value_text REGEXP '[^ -~]' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$text = (string)($row['value_text'] ?? '');
$html = '<html><head><meta charset="utf-8"></head><body><table><tr><th>Name</th></tr><tr><td>' . e($text) . '</td></tr></table></body></html>';
export_pdf($html, 'db_bangla_test.pdf', 'portrait', __DIR__ . '/storage/runtime/db_bangla_test.pdf');
echo $text, PHP_EOL;
