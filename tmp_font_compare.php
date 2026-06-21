<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/lib/exports.php';
$text = '31 ????? ???????? ?? ??/??/???? ?????? ????????? ????? ??? ?????? ? ????? ??????? ?? ??? ???? ??????? ????????? ????? ????';
$fonts = ['siyamrupali', 'kalpurush', 'nikosh', 'lohitbengali'];
foreach ($fonts as $font) {
    $html = '<html><head><meta charset="utf-8"><style>'
        . 'body{font-family:' . $font . ', sans-serif; font-size:12px;}'
        . 'table{border-collapse:collapse;width:420px;}'
        . 'th,td{border:1px solid #444;padding:6px;vertical-align:top;text-align:left;}'
        . '.remarks{width:120px;}'
        . '</style></head><body>'
        . '<table><thead><tr><th>SL</th><th>Name</th><th class="remarks">Remarks</th></tr></thead><tbody>'
        . '<tr><td>1</td><td>Mongla Upazila Health Complex</td><td class="remarks">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '</tbody></table></body></html>';
    export_pdf($html, $font . '.pdf', 'portrait', __DIR__ . '/storage/runtime/' . $font . '_narrow_test.pdf');
}
echo 'done';
