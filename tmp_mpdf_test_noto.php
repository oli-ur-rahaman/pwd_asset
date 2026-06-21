<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/lib/exports.php';
$html = '<html lang="bn"><body style="font-family:' . export_pdf_font_family() . '; font-size:16px;">????? ??????? - ???????? ????</body></html>';
export_pdf($html, 'mpdf_test_bangla_noto.pdf', 'portrait', __DIR__ . '/storage/runtime/mpdf_test_bangla_noto.pdf');
echo 'done', PHP_EOL;
