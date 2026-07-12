<?php
require __DIR__ . '/app/lib/bootstrap.php';
$pdo = db();
$segId = (int)$pdo->query("SELECT id FROM segments WHERE segment_name='admin_common_2'")->fetchColumn();
if ($segId <= 0) {
    echo json_encode(['segment_found' => false]);
    exit;
}
$user = $pdo->query("SELECT * FROM users WHERE email_id='ee_syl@pwd.gov.bd' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$rows = asset_template_prefill_rows($segId, $user);
$assetIds = array_map(static fn($row) => (int)$row['asset_id'], $rows);
$assets = [];
if ($assetIds) {
    $assets = $pdo->query('SELECT id,is_user_added_row FROM assets WHERE id IN (' . implode(',', $assetIds) . ')')->fetchAll(PDO::FETCH_KEY_PAIR);
}
$userAddedCount = 0;
foreach ($assetIds as $id) {
    if ((int)($assets[$id] ?? 0) === 1) {
        $userAddedCount++;
    }
}
echo json_encode(['segment_found' => true, 'row_count' => count($rows), 'user_added_in_template' => $userAddedCount, 'sample_asset_ids' => array_slice($assetIds, 0, 12)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
