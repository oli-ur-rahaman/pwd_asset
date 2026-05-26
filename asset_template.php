<?php
require __DIR__ . '/app/lib/bootstrap.php';
require_login();

$mode = request_str('mode', '');
output_asset_template_download($mode !== 'auto');
