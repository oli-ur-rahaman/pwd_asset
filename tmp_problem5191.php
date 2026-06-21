<?php
require __DIR__ . '/app/lib/bootstrap.php';
$text = (string) db()->query("SELECT value_text FROM asset_values WHERE id = 5191")->fetchColumn();
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
    export_pdf($html, $font . '.pdf', 'portrait', __DIR__ . '/storage/runtime/' . $font . '_problem5191_test.pdf');
}
echo $text, PHP_EOL;
