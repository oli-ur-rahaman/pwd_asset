<?php
require __DIR__ . '/app/lib/bootstrap.php';
$sql = "SELECT v.value_text FROM asset_values v JOIN asset_fields f ON f.id = v.field_id WHERE f.field_key = 'remarks_additional_information' AND v.value_text REGEXP '[^ -~]' ORDER BY v.id DESC LIMIT 1";
$text = (string) db()->query($sql)->fetchColumn();
file_put_contents(__DIR__ . '/storage/runtime/remarks_sample_utf8.txt', $text);
$fonts = ['siyamrupali', 'kalpurush', 'nikosh', 'lohitbengali'];
foreach ($fonts as $font) {
    $html = '<html><head><meta charset="utf-8"><style>'
        . 'body{font-family:' . $font . ', sans-serif; font-size:12px;}'
        . 'table{border-collapse:collapse;width:420px;}'
        . 'th,td{border:1px solid #444;padding:6px;vertical-align:top;text-align:left;}'
        . '.remarks{width:120px;}'
        . '</style></head><body>'
        . '<table><thead><tr><th>SL</th><th>Name</th><th class="remarks">Remarks</th></tr></thead><tbody>'
        . '<tr><td>1</td><td>Sample</td><td class="remarks">' . e($text) . '</td></tr>'
        . '</tbody></table></body></html>';
    export_pdf($html, $font . '.pdf', 'portrait', __DIR__ . '/storage/runtime/' . $font . '_remarks_test.pdf');
}
echo 'done';
