<?php
require __DIR__ . '/app/lib/bootstrap.php';
$job = asset_download_job_fetch_by_token('518b91ed155e03df9c439eb1ad2ff1ad8f00774e');
$request = json_decode((string)$job['request_payload'], true);
$user = asset_download_job_user((int)$job['user_id']);
$groups = [];
$context = null;
foreach ($request['segments'] as $segmentId => $segmentConfig) {
    $segmentRowsByGroup = asset_download_grouped_assets_for_segment((int)$segmentId, $request, $user, $context);
    foreach ($segmentRowsByGroup as $groupValue => $segmentAssets) {
        if (!isset($groups[$groupValue])) {
            $groups[$groupValue] = ['segments' => []];
        }
        $groups[$groupValue]['segments'][(int)$segmentId] = ['assets' => $segmentAssets];
    }
}
$html = '<html><head><meta charset="utf-8"><style>'
    . '@page{size:A4 landscape;margin:20px 18px 28px 18px;}'
    . 'body{font-family:' . export_pdf_font_family() . ',DejaVu Sans,Arial,sans-serif;font-size:10px;color:#111;}'
    . 'h2,h3{margin:0 0 8px;}'
    . '.group{page-break-after:always;}'
    . '.group:last-child{page-break-after:auto;}'
    . '.segment-block{margin-bottom:16px;}'
    . 'table{width:100%;border-collapse:collapse;margin-bottom:12px;}'
    . 'th,td{border:1px solid #444;padding:4px;vertical-align:top;text-align:left;}'
    . 'th{background:#eef4fb;font-weight:700;}'
    . '.muted{color:#666;}'
    . '</style></head><body>';
foreach ($groups as $groupValue => $group) {
    $html .= '<section class="group"><h2>' . e((string)$request['level1_label']) . ': ' . e((string)$groupValue) . '</h2>';
    foreach ($request['segments'] as $segmentId => $segmentConfig) {
        $segmentData = $group['segments'][$segmentId] ?? null;
        if (!$segmentData) {
            continue;
        }
        $headers = asset_download_table_headers(
            $segmentConfig['selected_field_keys'],
            (int)$segmentId,
            (array)($request['common_columns'] ?? []),
            (string)$request['level1_label'],
            false,
            (array)($segmentConfig['fields'] ?? [])
        );
        $rows = asset_download_table_rows(
            $segmentData['assets'],
            $segmentConfig['selected_field_keys'],
            (int)$segmentId,
            (array)($request['common_columns'] ?? []),
            (string)$request['level1_label'],
            false,
            (array)($segmentConfig['fields'] ?? [])
        );
        $html .= '<div class="segment-block"><h3>' . e((string)$segmentConfig['segment']['segment_name']) . '</h3>';
        $html .= '<table><thead><tr>';
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
        if (!$rows) {
            $html .= '<tr><td colspan="' . count($headers) . '" class="muted">No rows found.</td></tr>';
        }
        $html .= '</tbody></table></div>';
    }
    $html .= '</section>';
}
$html .= '</body></html>';
file_put_contents(__DIR__ . '/public/debug_current_report.html', $html);
echo strlen($html), PHP_EOL;
