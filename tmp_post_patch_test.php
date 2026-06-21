<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/lib/exports.php';
$text = html_entity_decode('&#2476;&#2494;&#2434;&#2482;&#2494; &#2474;&#2480;&#2496;&#2453;&#2509;&#2487;&#2494; - &#2489;&#2494;&#2488;&#2474;&#2494;&#2468;&#2494;&#2482; &#2468;&#2469;&#2509;&#2479;', ENT_QUOTES, 'UTF-8');
$html = '<html><head><meta charset="utf-8"></head><body><table><tr><th>Remarks</th></tr><tr><td>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</td></tr></table></body></html>';
export_pdf($html, 'post_patch_test.pdf', 'portrait', __DIR__ . '/storage/runtime/post_patch_test.pdf');
echo 'done', PHP_EOL;
