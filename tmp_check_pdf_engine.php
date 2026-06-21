<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/lib/exports.php';
$rf = new ReflectionFunction('export_pdf');
echo 'export_pdf file: ', $rf->getFileName(), ':', $rf->getStartLine(), PHP_EOL;
echo 'mpdf loaded: ', class_exists('Mpdf\\Mpdf') ? 'yes' : 'no', PHP_EOL;
echo 'dompdf loaded: ', class_exists('Dompdf\\Dompdf') ? 'yes' : 'no', PHP_EOL;
