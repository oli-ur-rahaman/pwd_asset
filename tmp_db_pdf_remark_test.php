<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/lib/bootstrap.php';
$cfg = require __DIR__ . '/app/config.php';
$dsn = 'mysql:host=' . $cfg['db']['host'] . ';dbname=' . $cfg['db']['name'] . ';charset=' . $cfg['db']['charset'];
$pdo = new PDO($dsn, $cfg['db']['user'], $cfg['db']['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$text = $pdo->query("SELECT av.value_text FROM asset_values av JOIN asset_fields af ON af.id = av.field_id WHERE af.label LIKE 'Remarks%' AND av.value_text IS NOT NULL AND av.value_text <> '' AND av.value_text REGEXP '[^ -~]' LIMIT 1")->fetchColumn();
$html = '<html><head><meta charset="utf-8"><style>'
    . '@page{size:A4 landscape;margin:20px 18px 28px 18px;}'
    . 'body{font-family:"Kalpurush",DejaVu Sans,Arial,sans-serif;font-size:9px;color:#111;}'
    . 'table{width:100%;border-collapse:collapse;table-layout:fixed;margin-bottom:12px;}'
    . 'th,td{border:1px solid #444;padding:4px;vertical-align:top;text-align:left;word-wrap:break-word;word-break:break-word;white-space:pre-wrap;overflow-wrap:anywhere;}'
    . 'th{background:#eef4fb;font-weight:700;}'
    . '</style></head><body>'
    . '<table class="tight"><thead><tr><th style="width:50px">Remarks (Additional Information)</th></tr></thead><tbody><tr><td>' . e((string)$text) . '</td></tr></tbody></table>'
    . '</body></html>';
export_pdf($html, 'db_bangla_remark_test.pdf', 'landscape', __DIR__ . '/storage/runtime/db_bangla_remark_test.pdf');
echo $text, PHP_EOL;
