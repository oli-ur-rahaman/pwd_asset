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
  'default_font' => 'siyamrupali',
  'autoScriptToLang' => true,
  'autoLangToFont' => true,
  'fontDir' => array_merge($cfg['fontDir'], [$fontDir]),
  'fontdata' => $fontCfg['fontdata'] + [
    'siyamrupali' => ['R' => 'siyamrupali.ttf', 'useOTL' => 0xFF],
    'kalpurush' => ['R' => 'kalpurush.ttf', 'useOTL' => 0xFF],
    'lohitbengali' => ['R' => 'Lohit-Bengali.ttf', 'useOTL' => 0xFF],
  ],
]);
$text = html_entity_decode('&#2476;&#2494;&#2434;&#2482;&#2494; &#2474;&#2480;&#2496;&#2453;&#2509;&#2487;&#2494; - &#2489;&#2494;&#2488;&#2474;&#2494;&#2468;&#2494;&#2482; &#2468;&#2469;&#2509;&#2479;', ENT_QUOTES, 'UTF-8');
$html = '<html lang="bn"><body style="font-family:siyamrupali, sans-serif; font-size:18px;">' . $text . '</body></html>';
$mpdf->WriteHTML($html);
$path = __DIR__ . '/storage/runtime/mpdf_test_entities.pdf';
$mpdf->Output($path, \Mpdf\Output\Destination::FILE);
echo $path, PHP_EOL;
