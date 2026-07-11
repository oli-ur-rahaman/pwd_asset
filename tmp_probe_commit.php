<?php
require __DIR__ . '/app/lib/bootstrap.php';
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM users WHERE email_id = ? LIMIT 1');
$stmt->execute(['ee_syl@pwd.gov.bd']);
$u = $stmt->fetch(PDO::FETCH_ASSOC);
$rows = json_decode(file_get_contents(__DIR__ . '/tmp_review_rows.json'), true);
$_SESSION['asset_import_review'] = [
    'segment_id' => 13,
    'filename' => '6admin_common_autogen.xlsx',
    'rows' => $rows,
];
$restaged = restage_asset_import_rows($rows);
$result = commit_asset_import_review($u);
echo json_encode([
    'user_id' => $u['id'] ?? null,
    'restaged_count' => count($restaged),
    'result' => $result,
    'remaining_sample' => array_slice($_SESSION['asset_import_review']['rows'] ?? [], 0, 2),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
