<?php
require __DIR__ . '/app/lib/bootstrap.php';
require_login();

$mode = request_str('mode', '');
if ($mode === 'auto') {
    output_asset_template_download('auto', (int)request_str('segment_id', '0'));
} elseif ($mode === 'uploaded') {
    output_asset_template_download('uploaded', (int)request_str('segment_id', '0'));
} else {
    output_asset_template_download('selected', (int)request_str('segment_id', '0'));
}
