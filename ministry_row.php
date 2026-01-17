<?php
require __DIR__ . '/app/lib/bootstrap.php';
require_login();

if (!is_division_user()) {
    http_response_code(403);
    echo json_encode(['error' => 'Not allowed']);
    exit;
}

$table = request_str('table');
$ministry_id = input_int('ministry_id');
$fy_id = input_int('fy_id');

if (!in_array($table, ['operational', 'development'], true) || $ministry_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

$fy = null;
if ($fy_id > 0) {
    $stmt = db()->prepare('SELECT * FROM fy WHERE id = ?');
    $stmt->execute([$fy_id]);
    $fy = $stmt->fetch();
}
if (!$fy) {
    $fy = get_current_fy();
}
if (!$fy) {
    http_response_code(400);
    echo json_encode(['error' => 'No current fiscal year set.']);
    exit;
}

$stmt = db()->prepare('SELECT name FROM ministries WHERE id = ? LIMIT 1');
$stmt->execute([$ministry_id]);
$ministry_name = $stmt->fetchColumn() ?: '';

$user = current_user();
$row = get_latest_record_for_division_ministry($table, (int)$fy['id'], (int)$user['division_id'], $ministry_id);
if (!$row) {
    $row = [
        'month_val' => null,
        'pkg' => 0,
        'est' => 0,
        'pkg_live' => 0,
        'pkg_eval' => 0,
        'pkg_cont' => 0,
        'cont' => 0,
        'note' => '',
    ];
}

header('Content-Type: application/json');
echo json_encode([
    'ministry_name' => $ministry_name,
    'ministry_id' => $ministry_id,
    'month_val' => $row['month_val'] ?? null,
    'pkg' => (int)($row['pkg'] ?? 0),
    'est' => (float)($row['est'] ?? 0),
    'pkg_live' => (int)($row['pkg_live'] ?? 0),
    'pkg_eval' => (int)($row['pkg_eval'] ?? 0),
    'pkg_cont' => (int)($row['pkg_cont'] ?? 0),
    'cont' => (float)($row['cont'] ?? 0),
    'note' => (string)($row['note'] ?? ''),
]);
