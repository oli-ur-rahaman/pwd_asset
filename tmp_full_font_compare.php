<?php
require __DIR__ . '/vendor/autoload.php';
$html = file_get_contents(__DIR__ . '/public/debug_current_report.html');
$fontDir = __DIR__ . '/public/assets/fonts';
$tempDir = __DIR__ . '/storage/runtime/mpdf';
if (!is_dir($tempDir)) { @mkdir($tempDir, 0777, true); }
$fonts = [
    'siyamrupali' => 'siyamrupali.ttf',
    'kalpurush' => 'kalpurush.ttf',
    'nikosh' => 'Nikosh.ttf',
    'lohitbengali' => 'Lohit-Bengali.ttf',
];
foreach ($fonts as $key => $file) {
    try {
        $config = [
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'tempDir' => $tempDir,
            'default_font' => $key,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'fontDir' => array_merge((new \Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'], [$fontDir]),
            'fontdata' => (new \Mpdf\Config\FontVariables())->getDefaults()['fontdata'] + [
                $key => ['R' => $file, 'useOTL' => 0xFF, 'useKashida' => 75],
            ],
        ];
        $mpdf = new \Mpdf\Mpdf($config);
        $styled = '<meta charset="utf-8"><style>*{font-family:"' . $key . '", sans-serif !important;} html,body,table,thead,tbody,tr,td,th,div,span,p{font-family:"' . $key . '", sans-serif !important;}</style>' . $html;
        $out = __DIR__ . '/storage/runtime/full_' . $key . '.pdf';
        $mpdf->WriteHTML($styled);
        $mpdf->Output($out, \Mpdf\Output\Destination::FILE);
        echo 'ok ' . $out . PHP_EOL;
    } catch (Throwable $e) {
        echo 'fail ' . $key . ' ' . $e->getMessage() . PHP_EOL;
    }
}
?>
