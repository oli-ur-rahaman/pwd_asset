<?php
require __DIR__ . '/app/lib/bootstrap.php';
require_login();

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Invalid CSRF token.');
}

$image = $_POST['image_data'] ?? '';
if (strpos($image, 'data:image/') !== 0) {
    http_response_code(400);
    exit('Invalid image data.');
}

$html = '<html><head><style>body{margin:0;padding:0;}</style></head><body>'
    . '<img src="' . e($image) . '" style="width:100%;">'
    . '</body></html>';

export_pdf($html, 'chart.pdf');
