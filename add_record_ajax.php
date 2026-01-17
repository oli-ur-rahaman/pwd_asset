<?php
require __DIR__ . '/app/lib/bootstrap.php';
require_login();

header('Content-Type: application/json');

if (!is_division_user() || (int)(current_user()['office_type'] ?? 0) !== 4) {
    http_response_code(403);
    echo json_encode(['error' => 'Not allowed']);
    exit;
}
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid CSRF token.']);
    exit;
}

$table = input_str('table');
if (!in_array($table, ['operational', 'development'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid table.']);
    exit;
}

$fy = get_current_fy();
if (!$fy) {
    http_response_code(400);
    echo json_encode(['error' => 'No current fiscal year set.']);
    exit;
}

$ministry_id = input_int('ministry_id');
if ($ministry_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Ministry is required.']);
    exit;
}

$month_val = input_int('month_val', 1);
if (!is_month_allowed($fy['fiscal_years'], $month_val)) {
    http_response_code(400);
    echo json_encode(['error' => 'Selected month is beyond the current fiscal year month.']);
    exit;
}

$user = current_user();
$data = [
    'fy_id' => (int)$fy['id'],
    'division_id' => (int)$user['division_id'],
    'ministry_id' => $ministry_id,
    'month_val' => $month_val,
    'pkg' => input_int('pkg'),
    'est' => input_float('est'),
    'pkg_live' => input_int('pkg_live'),
    'pkg_eval' => input_int('pkg_eval'),
    'pkg_cont' => input_int('pkg_cont'),
    'cont' => input_float('cont'),
    'note' => input_str('note'),
    'created_at' => date('Y-m-d H:i:s'),
];

$record_id = insert_record($table, $data);
add_log((int)$user['id'], $table, $record_id, 'Added new entry.');

$stmt = db()->prepare('SELECT name FROM ministries WHERE id = ?');
$stmt->execute([$ministry_id]);
$ministry_name = $stmt->fetchColumn() ?: '';

echo json_encode([
    'ok' => true,
    'table' => $table,
    'ministry_id' => $ministry_id,
    'ministry_name' => $ministry_name,
    'pkg' => $data['pkg'],
    'est' => $data['est'],
    'pkg_live' => $data['pkg_live'],
    'pkg_eval' => $data['pkg_eval'],
    'pkg_cont' => $data['pkg_cont'],
    'cont' => $data['cont'],
    'created_at' => $data['created_at'],
]);
