<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/lib/exports.php';
$html = '<html lang="bn"><body style="font-family:' . export_pdf_font_family() . '; font-size:18px;">????? ??????? - ???????? ????</body></html>';
export_pdf($html, 'mpdf_test_bangla.pdf', 'portrait', __DIR__ . '/public/mpdf_test_bangla.pdf');
echo 'done', PHP_EOL;
