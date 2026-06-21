<?php
require __DIR__ . '/vendor/autoload.php';
$fontDir = __DIR__ . '/public/assets/fonts';
$tempDir = __DIR__ . '/storage/runtime/mpdf_test';
if (!is_dir($tempDir)) { mkdir($tempDir, 0777, true); }
$cfg = (new \Mpdf\Config\ConfigVariables())->getDefaults();
$fontCfg = (new \Mpdf\Config\FontVariables())->getDefaults();
$mpdf = new \Mpdf\Mpdf([
  'mode' => 'utf-8',
  'tempDir' => $tempDir,
  'default_font' => 'lohitbengali',
  'autoScriptToLang' => true,
  'autoLangToFont' => true,
  'fontDir' => array_merge($cfg['fontDir'], [$fontDir]),
  'fontdata' => $fontCfg['fontdata'] + [
    'lohitbengali' => ['R' => 'Lohit-Bengali.ttf', 'useOTL' => 0xFF],
  ],
]);
$html = '<html lang="bn"><body style="font-family:lohitbengali, sans-serif; font-size:18px;">????? ??????? - ???????? ????</body></html>';
$mpdf->WriteHTML($html);
$path = __DIR__ . '/storage/runtime/mpdf_test_lohit.pdf';
$mpdf->Output($path, \Mpdf\Output\Destination::FILE);
echo $path, PHP_EOL;
