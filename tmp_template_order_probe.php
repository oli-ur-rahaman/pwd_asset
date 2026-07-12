<?php
require __DIR__ . '/app/lib/bootstrap.php';
$pdo = db();
$segId = (int)$pdo->query("SELECT id FROM segments WHERE segment_name='admin_common'")->fetchColumn();
$user = $pdo->query("SELECT * FROM users WHERE email_id='ee_syl@pwd.gov.bd' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$rows = asset_template_prefill_rows($segId, $user);
$sample = array_map(static function(array $row): array {
    return [
        'asset_id' => $row['asset_id'],
        'layer_1' => $row['values']['layer_1'] ?? null,
        'is_locked_layer_1' => in_array('layer_1', $row['locked_keys'] ?? [], true),
    ];
}, array_slice($rows, 0, 12));
echo json_encode(['count' => count($rows), 'sample' => $sample], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
