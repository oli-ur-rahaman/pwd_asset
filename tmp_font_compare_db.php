<?php
require __DIR__ . '/app/lib/bootstrap.php';
$stmt = db()->query("SELECT field_value FROM asset_data WHERE field_key = 'remarks_additional_information' AND field_value REGEXP '[^ -~]' LIMIT 1");
$text = (string)$stmt->fetchColumn();
$fonts = ['siyamrupali', 'kalpurush', 'nikosh', 'lohitbengali'];
foreach ($fonts as $font) {
    $html = '<html><head><meta charset="utf-8"><style>'
        . 'body{font-family:' . $font . ', sans-serif; font-size:12px;}'
        . 'table{border-collapse:collapse;width:420px;}'
        . 'th,td{border:1px solid #444;padding:6px;vertical-align:top;text-align:left;}'
        . '.remarks{width:120px;}'
        . '</style></head><body>'
        . '<table><thead><tr><th>SL</th><th>Name</th><th class="remarks">Remarks</th></tr></thead><tbody>'
        . '<tr><td>1</td><td>Mongla Upazila Health Complex</td><td class="remarks">' . e($text) . '</td></tr>'
        . '</tbody></table></body></html>';
    export_pdf($html, $font . '.pdf', 'portrait', __DIR__ . '/storage/runtime/' . $font . '_db_narrow_test.pdf');
}
echo 'done';
