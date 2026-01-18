<?php
require __DIR__ . '/app/lib/bootstrap.php';
require_login();

$table = request_str('table');
$metric = request_str('metric');
$fy_id = (int)request_str('fy_id');
$division_raw = request_str('division_id');
$division_id = $division_raw === 'all' ? 0 : (int)$division_raw;
$ministry_raw = request_str('ministry_id');
$ministry_id = $ministry_raw === 'all' || $ministry_raw === '' ? 0 : (int)$ministry_raw;

$allowed_tables = ['operational', 'development', 'opr_repair', 'opr_other', 'dev_pw', 'opr_other_min', 'dev_other_min'];
$allowed_metrics = ['pkg', 'est', 'pkg_live', 'pkg_eval', 'pkg_cont', 'cont'];
if (!in_array($table, $allowed_tables, true) || !in_array($metric, $allowed_metrics, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

$user = current_user();
$allowed_divisions = array_column(get_divisions_for_user($user), 'id');
if ($division_id > 0 && !in_array($division_id, $allowed_divisions, true)) {
    http_response_code(403);
    echo json_encode(['error' => 'Not allowed']);
    exit;
}

$stmt = db()->prepare('SELECT fiscal_years FROM fy WHERE id = ?');
$stmt->execute([$fy_id]);
$fy_label = $stmt->fetchColumn();
if (!$fy_label) {
    http_response_code(404);
    echo json_encode(['error' => 'Fiscal year not found']);
    exit;
}

if ($division_id === 0) {
    if (is_division_user()) {
        http_response_code(403);
        echo json_encode(['error' => 'Not allowed']);
        exit;
    }
    if (in_array($table, ['operational', 'development'], true) && $ministry_id > 0) {
        $data = get_monthly_series_all_ministry($table, $fy_id, $allowed_divisions, $ministry_id, $metric, $fy_label);
    } else {
        $data = get_monthly_series_all($table, $fy_id, $allowed_divisions, $metric, $fy_label);
    }
} else {
    if (in_array($table, ['operational', 'development'], true) && $ministry_id > 0) {
        $data = get_monthly_series_ministry($table, $fy_id, $division_id, $ministry_id, $metric, $fy_label);
    } else {
        $data = get_monthly_series($table, $fy_id, $division_id, $metric, $fy_label);
    }
}

header('Content-Type: application/json');
echo json_encode($data);
