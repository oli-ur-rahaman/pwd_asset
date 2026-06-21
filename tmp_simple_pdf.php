<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/lib/exports.php';
$html = file_get_contents(__DIR__ . '/public/debug_bagerhat_report.html');
$html = preg_replace('#<style>.*?</style>#s', '<style>body{font-family:' . export_pdf_font_family() . ', sans-serif; font-size:10px;} h2,h3{margin:0 0 8px;} table{width:100%;border-collapse:collapse;margin-bottom:12px;} th,td{border:1px solid #444;padding:4px;vertical-align:top;text-align:left;} th{background:#eef4fb;font-weight:700;}</style>', $html, 1);
export_pdf($html, 'debug_simple.pdf', 'landscape', __DIR__ . '/storage/runtime/debug_simple.pdf');
echo 'done';
