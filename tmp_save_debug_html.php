<?php
session_start();
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/lib/db.php';
require __DIR__ . '/app/lib/helpers.php';
require __DIR__ . '/app/lib/auth.php';
require __DIR__ . '/app/lib/exports.php';
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
$html = '<html><head><meta charset="utf-8"><style>'
    . '@page{size:A4 landscape;margin:20px 18px 28px 18px;}'
    . 'body{font-family:' . export_pdf_font_family() . ',DejaVu Sans,Arial,sans-serif;font-size:9px;color:#111;}'
    . 'h2,h3{margin:0 0 8px;}'
    . '.group{page-break-after:always;}'
    . '.group:last-child{page-break-after:auto;}'
    . '.group-head{margin-bottom:10px;padding-bottom:6px;border-bottom:2px solid #1f4f82;}'
    . '.segment-block{margin-bottom:16px;}'
    . 'table{width:100%;border-collapse:collapse;table-layout:fixed;margin-bottom:12px;}'
    . 'table.compact{font-size:8px;}'
    . 'table.tight{font-size:7px;}'
    . 'thead{display:table-header-group;}'
    . 'tr{page-break-inside:avoid;}'
    . 'th,td{border:1px solid #444;padding:4px;vertical-align:top;text-align:left;word-wrap:break-word;word-break:break-word;white-space:pre-wrap;overflow-wrap:anywhere;}'
    . 'th{background:#eef4fb;font-weight:700;}'
    . '.muted{color:#666;}'
    . '</style></head><body>';
foreach ($groups as $groupValue => $group) {
    $html .= '<section class="group"><h2>' . e((string)$request['level1_label']) . ': ' . e((string)$groupValue) . '</h2>';
    foreach ($request['segments'] as $segmentId => $segmentConfig) {
        $segmentData = $group['segments'][$segmentId] ?? null;
        if (!$segmentData) continue;
        $headers = asset_download_table_headers($segmentConfig['selected_field_keys'], (int)$segmentId, (array)($request['common_columns'] ?? []), (string)$request['level1_label'], false, (array)($segmentConfig['fields'] ?? []));
        $rows = asset_download_table_rows($segmentData['assets'], $segmentConfig['selected_field_keys'], (int)$segmentId, (array)($request['common_columns'] ?? []), (string)$request['level1_label'], false, (array)($segmentConfig['fields'] ?? []));
        $headerCount = count($headers);
        $tableClass = $headerCount >= 14 ? 'tight' : ($headerCount >= 10 ? 'compact' : '');
        $html .= '<div class="segment-block"><h3>' . e((string)$segmentConfig['segment']['segment_name']) . '</h3>';
        $html .= '<table' . ($tableClass !== '' ? ' class="' . $tableClass . '"' : '') . '><thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . e((string)$header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach (array_keys($headers) as $key) {
                $html .= '<td>' . e((string)($row[$key] ?? '')) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';
    }
    $html .= '</section>';
}
$html .= '</body></html>';
file_put_contents(__DIR__ . '/public/debug_bagerhat_report.html', $html);
echo 'saved';
