<?php
require __DIR__ . '/app/lib/bootstrap.php';
require_login();

$table = request_str('table');
$mode = request_str('mode', 'latest');
$format = request_str('format', 'pdf');
$fy_id = (int)request_str('fy_id');
$division_raw = request_str('division_id', 'all');
$division_id = $division_raw === 'all' ? 0 : (int)$division_raw;

$allowed_tables = ['revenue', 'development'];
$allowed_formats = ['pdf', 'excel'];
$allowed_modes = ['latest', 'monthly'];
if (!in_array($table, $allowed_tables, true) || !in_array($format, $allowed_formats, true) || !in_array($mode, $allowed_modes, true)) {
    http_response_code(400);
    exit('Invalid parameters.');
}

$user = current_user();
$allowed_divisions = array_column(get_divisions_for_user($user), 'id');
if ($division_id > 0 && !in_array($division_id, $allowed_divisions, true)) {
    http_response_code(403);
    exit('Not allowed.');
}

$stmt = db()->prepare('SELECT fiscal_years FROM fy WHERE id = ?');
$stmt->execute([$fy_id]);
$fy_label = $stmt->fetchColumn();
if (!$fy_label) {
    http_response_code(404);
    exit('Fiscal year not found.');
}

$headers = [
    'office_name' => 'Division',
    'pkg' => 'Total Packages',
    'est' => 'Total Value (Lakh Tk.)',
    'pkg_live' => 'Live Tender',
    'pkg_eval' => 'Evaluation',
    'pkg_cont' => 'Contract Awarded',
    'cont' => 'Contract Value',
    'note' => 'Note',
];

if ($mode === 'latest') {
    if ($division_id > 0) {
        $division = db()->prepare('SELECT office_name FROM divisions WHERE id = ?');
        $division->execute([$division_id]);
        $name = $division->fetchColumn();
        $latest = get_latest_record_for_division($table, $fy_id, $division_id);
        $rows = $latest ? [[
            'office_name' => $name,
            'pkg' => $latest['pkg'],
            'est' => $latest['est'],
            'pkg_live' => $latest['pkg_live'],
            'pkg_eval' => $latest['pkg_eval'],
            'pkg_cont' => $latest['pkg_cont'],
            'cont' => $latest['cont'],
            'note' => $latest['note'],
        ]] : [];
    } else {
        $rows = get_latest_records($table, $fy_id, $allowed_divisions);
    }
} else {
    if ($division_id <= 0) {
        http_response_code(400);
        exit('Monthly export requires a specific division.');
    }
    $rows = get_monthly_rows($table, $fy_id, $division_id, $fy_label);
    $headers = [
        'month' => 'Month',
        'pkg' => 'Total Packages',
        'est' => 'Total Value (Lakh Tk.)',
        'pkg_live' => 'Live Tender',
        'pkg_eval' => 'Evaluation',
        'pkg_cont' => 'Contract Awarded',
        'cont' => 'Contract Value',
        'note' => 'Note',
    ];
}

$title = ucfirst($table) . ' - ' . ucfirst($mode) . ' - FY ' . $fy_label;
$filename = strtolower($table . '_' . $mode . '_' . $fy_label . '.' . ($format === 'pdf' ? 'pdf' : 'xlsx'));

if ($format === 'excel') {
    export_excel($rows, $headers, $filename);
}

$html = render_table_html($title, $headers, $rows);
export_pdf($html, $filename);
