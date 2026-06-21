<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/lib/exports.php';
$html = file_get_contents(__DIR__ . '/public/debug_current_report.html');
$tests = [
 'no_group' => [
   '.group{page-break-after:always;}' => '',
   '.group:last-child{page-break-after:auto;}' => '',
 ],
 'no_atpage' => [
   '@page{size:A4 landscape;margin:20px 18px 28px 18px;}' => '',
 ],
 'no_group_no_atpage' => [
   '.group{page-break-after:always;}' => '',
   '.group:last-child{page-break-after:auto;}' => '',
   '@page{size:A4 landscape;margin:20px 18px 28px 18px;}' => '',
 ],
];
foreach ($tests as $name => $replacements) {
    $variant = $html;
    foreach ($replacements as $search => $replace) {
        $variant = str_replace($search, $replace, $variant);
    }
    export_pdf($variant, $name . '.pdf', 'landscape', __DIR__ . '/storage/runtime/' . $name . '.pdf');
}
echo 'done';
