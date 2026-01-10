<?php
require __DIR__ . '/app/lib/bootstrap.php';

$page = request_str('page', 'dashboard');
$action = $_POST['action'] ?? null;

if ($action === 'login') {
    $email = input_str('email');
    $password = input_str('password');
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Invalid CSRF token.');
    }
    if (!$email || !$password || !login_user($email, $password)) {
        flash('error', 'Invalid credentials.');
        redirect('index.php?page=login');
    }
    redirect('index.php?page=dashboard');
}

if ($action === 'logout') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Invalid CSRF token.');
    }
    logout_user();
    redirect('index.php?page=login');
}

if ($action === 'add_record') {
    require_login();
    if (!is_division_user()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Invalid CSRF token.');
    }

    $table = input_str('table');
    if (!in_array($table, ['revenue', 'development'], true)) {
        http_response_code(400);
        exit('Invalid table.');
    }

    $user = current_user();
    $fy = get_current_fy();
    if (!$fy) {
        http_response_code(400);
        exit('No current fiscal year set.');
    }

    $data = [
        'fy_id' => (int)$fy['id'],
        'division_id' => (int)$user['division_id'],
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

    flash('success', 'Data saved.');
    redirect('index.php?page=dashboard');
}

if ($action === 'csv_import') {
    require_login();
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Invalid CSRF token.');
    }

    if (empty($_FILES['csv_file']['tmp_name'])) {
        flash('error', 'CSV file is required.');
        redirect('index.php?page=admin');
    }

    $type = input_str('import_type');
    $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
    if (!$handle) {
        flash('error', 'Unable to read CSV.');
        redirect('index.php?page=admin');
    }

    $headers = fgetcsv($handle);
    if (!$headers) {
        flash('error', 'Invalid CSV headers.');
        redirect('index.php?page=admin');
    }

    $count = 0;
    while (($row = fgetcsv($handle)) !== false) {
        $data = array_combine($headers, $row);
        $get_id = function (string $key): ?int {
            $raw = $data[$key] ?? '';
            $raw = trim((string)$raw);
            if ($raw === '') {
                return null;
            }
            $value = (int)$raw;
            return $value > 0 ? $value : null;
        };
        $get_text = function (string $key): ?string {
            $raw = $data[$key] ?? null;
            if ($raw === null) {
                return null;
            }
            $value = trim((string)$raw);
            return $value === '' ? null : $value;
        };
        if ($type === 'divisions') {
            $stmt = db()->prepare('INSERT INTO divisions (office_name, office_address, office_type, zone_id, circle_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
            $stmt->execute([
                $get_text('office_name') ?? '',
                $get_text('office_address'),
                (int)($data['office_type'] ?? 2),
                $get_id('zone_id'),
                $get_id('circle_id'),
            ]);
        } elseif ($type === 'circles') {
            $stmt = db()->prepare('INSERT INTO circles (office_name, office_address, office_type, zone_id, created_at) VALUES (?, ?, ?, ?, NOW())');
            $stmt->execute([
                $get_text('office_name') ?? '',
                $get_text('office_address'),
                (int)($data['office_type'] ?? 2),
                $get_id('zone_id'),
            ]);
        } elseif ($type === 'zones') {
            $stmt = db()->prepare('INSERT INTO zones (office_name, office_address, office_type, created_at) VALUES (?, ?, ?, NOW())');
            $stmt->execute([
                $get_text('office_name') ?? '',
                $get_text('office_address'),
                (int)($data['office_type'] ?? 2),
            ]);
        } elseif ($type === 'users') {
            $password = $data['password'] ?? 'changeme';
            $stmt = db()->prepare('INSERT INTO users (email_id, officer_name, password, office_type, office_role, zone_id, circle_id, division_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $stmt->execute([
                $get_text('email_id') ?? '',
                $get_text('officer_name'),
                password_hash($password, PASSWORD_DEFAULT),
                (int)($data['office_type'] ?? 4),
                (int)($data['office_role'] ?? 1),
                $get_id('zone_id'),
                $get_id('circle_id'),
                $get_id('division_id'),
            ]);
        }
        $count++;
    }
    fclose($handle);

    flash('success', 'Imported ' . $count . ' rows.');
    redirect('index.php?page=admin');
}

if ($action === 'add_fy') {
    require_login();
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Invalid CSRF token.');
    }

    $fy_label = input_str('fiscal_years');
    $make_current = input_int('make_current') === 1;
    if ($fy_label === '') {
        flash('error', 'Fiscal year is required.');
        redirect('index.php?page=admin');
    }

    if ($make_current) {
        db()->exec('UPDATE fy SET now_flag = 0');
    }
    $stmt = db()->prepare('INSERT INTO fy (fiscal_years, now_flag, created_at) VALUES (?, ?, NOW())');
    $stmt->execute([$fy_label, $make_current ? 1 : 0]);
    flash('success', 'Fiscal year added.');
    redirect('index.php?page=admin');
}

if ($action === 'set_current_fy') {
    require_login();
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Invalid CSRF token.');
    }
    $fy_id = input_int('fy_id');
    if ($fy_id <= 0) {
        flash('error', 'Invalid fiscal year.');
        redirect('index.php?page=admin');
    }
    db()->exec('UPDATE fy SET now_flag = 0');
    $stmt = db()->prepare('UPDATE fy SET now_flag = 1 WHERE id = ?');
    $stmt->execute([$fy_id]);
    flash('success', 'Current fiscal year updated.');
    redirect('index.php?page=admin');
}

if ($action === 'create_user') {
    require_login();
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Invalid CSRF token.');
    }

    $email = input_str('email_id');
    $name = input_str('officer_name');
    $password = input_str('password');
    $office_type = input_int('office_type');
    $office_role = input_int('office_role');
    $zone_id = input_int('zone_id');
    $circle_id = input_int('circle_id');
    $division_id = input_int('division_id');

    if ($email === '' || $password === '') {
        flash('error', 'Email and password are required.');
        redirect('index.php?page=admin');
    }

    $stmt = db()->prepare('INSERT INTO users (email_id, officer_name, password, office_type, office_role, zone_id, circle_id, division_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
    $stmt->execute([
        $email,
        $name,
        password_hash($password, PASSWORD_DEFAULT),
        $office_type,
        $office_role,
        $zone_id,
        $circle_id,
        $division_id,
    ]);

    flash('success', 'User created.');
    redirect('index.php?page=admin');
}

if ($page === 'login') {
    require __DIR__ . '/app/views/login.php';
    exit;
}

require_login();

if ($page === 'logout') {
    logout_user();
    redirect('index.php?page=login');
}

if ($page === 'logs') {
    if (!can_view_logs()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    require __DIR__ . '/app/views/logs.php';
    exit;
}

if ($page === 'admin') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    require __DIR__ . '/app/views/admin.php';
    exit;
}

require __DIR__ . '/app/views/dashboard.php';
