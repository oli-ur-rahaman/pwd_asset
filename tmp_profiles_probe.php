<?php
require __DIR__ . '/app/lib/bootstrap.php';
$pdo = db();
$segId = (int)$pdo->query("SELECT id FROM segments WHERE segment_name='admin_common'")->fetchColumn();
$profiles = get_asset_common_profiles_for_segment($segId, true);
echo json_encode($profiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
