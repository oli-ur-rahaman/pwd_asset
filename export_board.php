<?php
require __DIR__ . '/app/lib/bootstrap.php';
require_login();

$table = request_str('table');
$scope = request_str('scope', 'latest');
$format = request_str('format', 'pdf');

$allowed_tables = ['opr_repair', 'opr_other', 'dev_pw', 'opr_other_min', 'dev_other_min'];
$allowed_formats = ['pdf', 'excel'];
$allowed_scopes = ['latest', 'full'];
if (!in_array($table, $allowed_tables, true) || !in_array($format, $allowed_formats, true) || !in_array($scope, $allowed_scopes, true)) {
    http_response_code(400);
    exit('Invalid parameters.');
}

$user = current_user();
$fy = get_current_fy();
if (!$fy) {
    http_response_code(400);
    exit('No current fiscal year set.');
}

$division_list = get_divisions_for_user($user);
$division_ids = array_column($division_list, 'id');
$office_name = get_office_name_for_user($user);
$date_label = date('Y-m-d');
$budget_map = [
    'opr_repair' => 'Operational Budget (Repair Works)',
    'opr_other' => 'Operational Budget (Other than Repair)',
    'dev_pw' => 'Development Budget (MoHPW)',
    'opr_other_min' => 'Operational Budget (Other Ministry)',
    'dev_other_min' => 'Development Budget (Other Ministry)',
];
$budget_label = $budget_map[$table] ?? 'Budget';

$include_division = !is_division_user();
$month_map = function (string $fy_label, int $month_val): string {
    if ($month_val < 1 || $month_val > 12) {
        return '';
    }
    $months = fy_months($fy_label);
    return $months[$month_val - 1]['label'] ?? '';
};
$headers = [];
if ($include_division) {
    $headers['office_name'] = 'Division';
}
$headers += [
    'pkg' => 'Total no. of packages',
    'est' => 'Total Value of packages in Lakh Tk.',
    'pkg_live' => 'In live (No.)',
    'pkg_eval' => 'Evaluation/Appr.(No.)',
    'pkg_cont' => 'Contract Awarded (No.)',
    'cont' => 'Value of awarded contracts in Lakh Tk.',
    'prog_pkg' => 'Progress (contract pkgs / total pkgs) %',
    'prog_amt' => 'Progress (Total Cont Amount / Total Pkg Amount) %',
    'created_at' => 'Date',
];

$rows = [];
if ($scope === 'latest') {
    if (is_division_user()) {
        $latest = get_latest_record_for_division($table, (int)$fy['id'], (int)$user['division_id']);
        if ($latest) {
            $row = [
                'pkg' => $latest['pkg'],
                'est' => $latest['est'],
                'pkg_live' => $latest['pkg_live'],
                'pkg_eval' => $latest['pkg_eval'],
                'pkg_cont' => $latest['pkg_cont'],
                'cont' => $latest['cont'],
                'prog_pkg' => $latest['pkg'] > 0 ? number_format(($latest['pkg_cont'] / $latest['pkg']) * 100, 2) : '0.00',
                'prog_amt' => $latest['est'] > 0 ? number_format(($latest['cont'] / $latest['est']) * 100, 2) : '0.00',
                'created_at' => $latest['created_at'] ? date('d-m-Y', strtotime($latest['created_at'])) : '',
            ];
            if ($include_division) {
                $row = ['office_name' => $office_name] + $row;
            }
            $rows[] = $row;
        }
    } else {
        $latest_rows = get_latest_records($table, (int)$fy['id'], $division_ids);
        foreach ($latest_rows as $row) {
            $rows[] = [
                'office_name' => $row['office_name'],
                'pkg' => $row['pkg'],
                'est' => $row['est'],
                'pkg_live' => $row['pkg_live'],
                'pkg_eval' => $row['pkg_eval'],
                'pkg_cont' => $row['pkg_cont'],
                'cont' => $row['cont'],
                'prog_pkg' => $row['pkg'] > 0 ? number_format(($row['pkg_cont'] / $row['pkg']) * 100, 2) : '0.00',
                'prog_amt' => $row['est'] > 0 ? number_format(($row['cont'] / $row['est']) * 100, 2) : '0.00',
                'created_at' => $row['created_at'] ? date('d-m-Y', strtotime($row['created_at'])) : '',
            ];
        }
    }
} else {
    $headers = [];
    if ($include_division) {
        $headers['office_name'] = 'Division';
    }
    $headers += [
        'fiscal_years' => 'FY',
        'month_name' => 'Month',
        'pkg' => 'Total no. of packages',
        'est' => 'Total Value of packages in Lakh Tk.',
        'pkg_live' => 'In live (No.)',
        'pkg_eval' => 'Evaluation/Appr.(No.)',
        'pkg_cont' => 'Contract Awarded (No.)',
        'cont' => 'Value of awarded contracts in Lakh Tk.',
        'prog_pkg' => 'Progress (contract pkgs / total pkgs) %',
        'prog_amt' => 'Progress (Total Cont Amount / Total Pkg Amount) %',
        'created_at' => 'Date',
    ];

    $params = [];
    $sql = "SELECT d.office_name, f.fiscal_years, r.* FROM {$table} r JOIN divisions d ON d.id = r.division_id JOIN fy f ON f.id = r.fy_id WHERE 1=1";
    if ($division_ids) {
        $in = implode(',', array_fill(0, count($division_ids), '?'));
        $sql .= " AND r.division_id IN ({$in})";
        $params = array_merge($params, $division_ids);
    }
    $sql .= ' ORDER BY d.office_name, r.created_at DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $all_rows = $stmt->fetchAll();
    foreach ($all_rows as $row) {
        $entry = [
            'fiscal_years' => $row['fiscal_years'],
            'month_name' => $month_map($row['fiscal_years'], (int)($row['month_val'] ?? 0)),
            'pkg' => $row['pkg'],
            'est' => $row['est'],
            'pkg_live' => $row['pkg_live'],
            'pkg_eval' => $row['pkg_eval'],
            'pkg_cont' => $row['pkg_cont'],
            'cont' => $row['cont'],
            'prog_pkg' => $row['pkg'] > 0 ? number_format(($row['pkg_cont'] / $row['pkg']) * 100, 2) : '0.00',
            'prog_amt' => $row['est'] > 0 ? number_format(($row['cont'] / $row['est']) * 100, 2) : '0.00',
            'created_at' => $row['created_at'] ? date('d-m-Y', strtotime($row['created_at'])) : '',
        ];
        if ($include_division) {
            $entry = ['office_name' => $row['office_name']] + $entry;
        }
        $rows[] = $entry;
    }
}

if ($format === 'excel') {
    $sheet_title = $office_name . ' ' . $date_label;
    $filename = strtolower(str_replace(' ', '_', $budget_label . '_' . $scope . '_' . $date_label . '.xlsx'));
    export_excel($rows, $headers, $filename, $sheet_title);
}

$title = $budget_label;
$meta = '<div><strong>Office:</strong> ' . e($office_name) . '</div>'
    . '<div><strong>Email:</strong> ' . e($user['email_id'] ?? '') . '</div>'
    . '<div><strong>Date:</strong> ' . e($date_label) . '</div>'
    . '<div><strong>Fiscal Year:</strong> ' . e($fy['fiscal_years'] ?? 'Not set') . '</div>'
    . '<div><strong>Scope:</strong> ' . e($scope === 'latest' ? 'Latest Only' : 'Full Data') . '</div>';

$thead = '';
foreach ($headers as $header) {
    $thead .= '<th>' . e($header) . '</th>';
}
$tbody = '';
foreach ($rows as $row) {
    $tbody .= '<tr>';
    foreach (array_keys($headers) as $key) {
        $tbody .= '<td>' . e((string)($row[$key] ?? '')) . '</td>';
    }
    $tbody .= '</tr>';
}

$html = '<html><head><style>
    body{font-family:Arial, sans-serif;font-size:12px;}
    h2{text-align:center;margin-bottom:6px;}
    .meta{margin-bottom:10px;}
    table{width:100%;border-collapse:collapse;}
    th,td{border:1px solid #444;padding:6px;text-align:center;}
</style></head><body>'
    . '<h2>' . e($title) . '</h2>'
    . '<div class="meta">' . $meta . '</div>'
    . '<table><thead><tr>' . $thead . '</tr></thead><tbody>' . $tbody . '</tbody></table>'
    . '</body></html>';

$filename = strtolower(str_replace(' ', '_', $budget_label . '_' . $scope . '_' . $date_label . '.pdf'));
export_pdf($html, $filename);
