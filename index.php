<?php
require __DIR__ . '/app/lib/bootstrap.php';

$page = request_str('page', 'board');
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
    redirect('index.php?page=board');
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
    if (!in_array($table, ['operational', 'development', 'opr_repair', 'opr_other', 'dev_pw', 'opr_other_min', 'dev_other_min'], true)) {
        http_response_code(400);
        exit('Invalid table.');
    }

    $user = current_user();
    $fy = get_current_fy();
    if (!$fy) {
        http_response_code(400);
        exit('No current fiscal year set.');
    }

    $month_val = input_int('month_val', 1);
    if (!is_month_allowed($fy['fiscal_years'], $month_val)) {
        flash('error', 'Selected month is beyond the current fiscal year month.');
        redirect('index.php?page=board');
    }

    $data = [
        'fy_id' => (int)$fy['id'],
        'division_id' => (int)$user['division_id'],
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
    if (in_array($table, ['operational', 'development'], true)) {
        $ministry_id = input_int('ministry_id');
        if ($ministry_id <= 0) {
            flash('error', 'Ministry is required.');
            redirect('index.php?page=board');
        }
        $data['ministry_id'] = $ministry_id;
    }

    $record_id = insert_record($table, $data);
    add_log((int)$user['id'], $table, $record_id, 'Added new entry.');

    flash('success', 'Data saved.');
    redirect('index.php?page=board');
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
    $headers[0] = ltrim($headers[0], "\xEF\xBB\xBF");

    $count = 0;
    $skipped = 0;
    $header_count = count($headers);
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) < $header_count) {
            $row = array_pad($row, $header_count, '');
        } elseif (count($row) > $header_count) {
            $row = array_slice($row, 0, $header_count);
        }
        $data = array_combine($headers, $row);
        $get_id = function (string $key) use ($data): ?int {
            $raw = $data[$key] ?? '';
            $raw = trim((string)$raw);
            if ($raw === '' || strcasecmp($raw, 'NULL') === 0) {
                return null;
            }
            $value = (int)$raw;
            return $value > 0 ? $value : null;
        };
        $get_text = function (string $key) use ($data): ?string {
            $raw = $data[$key] ?? null;
            if ($raw === null) {
                return null;
            }
            $value = trim((string)$raw);
            if ($value === '' || strcasecmp($value, 'NULL') === 0) {
                return null;
            }
            return $value;
        };
        if ($type === 'divisions') {
            $stmt = db()->prepare('INSERT INTO divisions (office_name, office_address, office_type, zone_id, circle_id, field_office, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
            $stmt->execute([
                $get_text('office_name') ?? '',
                $get_text('office_address'),
                (int)($data['office_type'] ?? 2),
                $get_id('zone_id'),
                $get_id('circle_id'),
                (int)($data['field_office'] ?? 1),
            ]);
        } elseif ($type === 'ministries') {
            $name = $get_text('name');
            if ($name === null || $name === '') {
                $skipped++;
                continue;
            }
            $get_int = function (string $key, int $default) use ($data): int {
                if (!array_key_exists($key, $data)) {
                    return $default;
                }
                $raw = trim((string)($data[$key] ?? ''));
                if ($raw === '' || strcasecmp($raw, 'NULL') === 0) {
                    return $default;
                }
                return (int)$raw;
            };
            $vis_opr = $get_int('vis_opr', 1);
            $vis_dev = $get_int('vis_dev', 1);
            $inuse_status = $get_int('inuse_status', 1);
            $def_opr = $get_int('def_opr', 0);
            $def_dev = $get_int('def_dev', 0);
            $def_opr_sl = $get_int('def_opr_sl', 0);
            $def_dev_sl = $get_int('def_dev_sl', 0);

            $stmt = db()->prepare('INSERT INTO ministries (name, vis_opr, vis_dev, inuse_status, def_opr, def_dev, def_opr_sl, def_dev_sl, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $stmt->execute([$name, $vis_opr, $vis_dev, $inuse_status, $def_opr, $def_dev, $def_opr_sl, $def_dev_sl]);
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
            $email = $get_text('email_id');
            if (!$email) {
                $skipped++;
                continue;
            }
            $exists = db()->prepare('SELECT id FROM users WHERE email_id = ? LIMIT 1');
            $exists->execute([$email]);
            if ($exists->fetchColumn()) {
                $skipped++;
                continue;
            }

            $password = $data['password'] ?? 'changeme';
            $stmt = db()->prepare('INSERT INTO users (email_id, officer_name, password, office_type, office_role, zone_id, circle_id, division_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $stmt->execute([
                $email,
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

    $message = 'Imported ' . $count . ' rows.';
    if ($skipped > 0) {
        $message .= ' Skipped ' . $skipped . ' rows (missing or duplicate email).';
    }
    flash('success', $message);
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
    $office_role = input_int('office_role', 1);
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

if ($action === 'update_profile') {
    require_login();
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Invalid CSRF token.');
    }

    $name = input_str('officer_name');
    $password = input_str('password');
    $password_confirm = input_str('password_confirm');
    $user = current_user();
    if ($name === '') {
        $name = $user['officer_name'] ?? '';
    }

    if ($password !== '') {
        if ($password !== $password_confirm) {
            flash('error', 'Passwords do not match.');
            redirect('index.php?page=profile');
        }
        $stmt = db()->prepare('UPDATE users SET officer_name = ?, password = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$name, password_hash($password, PASSWORD_DEFAULT), (int)$user['id']]);
    } else {
        $stmt = db()->prepare('UPDATE users SET officer_name = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$name, (int)$user['id']]);
    }

    $_SESSION['user']['officer_name'] = $name;
    flash('success', 'Profile updated.');
    redirect('index.php?page=profile');
}

if ($action === 'reset_user_password') {
    require_login();
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Invalid CSRF token.');
    }
    $user_id = input_int('user_id');
    $new_password = input_str('new_password');
    if ($user_id <= 0 || $new_password === '') {
        flash('error', 'User and password are required.');
        redirect('index.php?page=users');
    }
    $stmt = db()->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([password_hash($new_password, PASSWORD_DEFAULT), $user_id]);
    flash('success', 'Password reset.');
    redirect('index.php?page=users');
}

if ($action === 'update_user') {
    require_login();
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Invalid CSRF token.');
    }
    $user_id = input_int('user_id');
    $email = input_str('email_id');
    $name = input_str('officer_name');
    $office_role = input_int('office_role', 1);
    $office_type = input_int('office_type', 1);
    $zone_id = input_int('zone_id');
    $circle_id = input_int('circle_id');
    $division_id = input_int('division_id');

    if ($user_id <= 0 || $email === '') {
        flash('error', 'User and email are required.');
        redirect('index.php?page=users');
    }

    $stmt = db()->prepare('SELECT id FROM users WHERE email_id = ? AND id <> ?');
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetchColumn()) {
        flash('error', 'Email already exists.');
        redirect('index.php?page=users');
    }

    if ($office_role === 2 || $office_role === 3) {
        $office_type = 1;
        $zone_id = 0;
        $circle_id = 0;
        $division_id = 0;
    } else {
        $office_role = 1;
        if ($division_id > 0) {
            $stmt = db()->prepare('SELECT zone_id, circle_id FROM divisions WHERE id = ? LIMIT 1');
            $stmt->execute([$division_id]);
            $division = $stmt->fetch();
            if (!$division) {
                flash('error', 'Invalid division selection.');
                redirect('index.php?page=users');
            }
            $circle_id = (int)($division['circle_id'] ?? 0);
            $zone_id = (int)($division['zone_id'] ?? 0);
            $office_type = 4;
        } elseif ($circle_id > 0) {
            $stmt = db()->prepare('SELECT zone_id FROM circles WHERE id = ? LIMIT 1');
            $stmt->execute([$circle_id]);
            $circle = $stmt->fetch();
            if (!$circle) {
                flash('error', 'Invalid circle selection.');
                redirect('index.php?page=users');
            }
            $zone_id = (int)($circle['zone_id'] ?? 0);
            $office_type = 3;
            $division_id = 0;
        } elseif ($zone_id > 0) {
            $stmt = db()->prepare('SELECT id FROM zones WHERE id = ? LIMIT 1');
            $stmt->execute([$zone_id]);
            if (!$stmt->fetchColumn()) {
                flash('error', 'Invalid zone selection.');
                redirect('index.php?page=users');
            }
            $office_type = 2;
            $circle_id = 0;
            $division_id = 0;
        } else {
            $office_type = 1;
            $circle_id = 0;
            $division_id = 0;
            $zone_id = 0;
        }
    }

    $stmt = db()->prepare('UPDATE users SET email_id = ?, officer_name = ?, office_role = ?, office_type = ?, zone_id = ?, circle_id = ?, division_id = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([
        $email,
        $name === '' ? null : $name,
        $office_role,
        $office_type,
        $zone_id > 0 ? $zone_id : null,
        $circle_id > 0 ? $circle_id : null,
        $division_id > 0 ? $division_id : null,
        $user_id,
    ]);
    flash('success', 'User updated.');
    redirect('index.php?page=users');
}

if ($action === 'save_interface') {
    require_login();
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Invalid CSRF token.');
    }
    $existing = get_info_row();
    $has_video = array_key_exists('video_tutorial_url', $_POST);
    $has_message = array_key_exists('login_message', $_POST);

    $video_url = $has_video ? input_str('video_tutorial_url') : ($existing['video_tutorial_url'] ?? null);
    $login_message = $has_message ? input_str('login_message') : ($existing['login_message'] ?? null);

    if ($has_video && $video_url === '') {
        $video_url = null;
    }
    if ($has_message && $login_message === '') {
        $login_message = null;
    }

    $extras = [];
    $extra_keys = ['site_name', 'i_opr_repair', 'i_opr_other', 'i_dev_pw', 'i_opr_min', 'i_dev_min', 'i_opr', 'i_dev'];
    foreach ($extra_keys as $key) {
        if (array_key_exists($key, $_POST)) {
            $value = input_str($key);
            $extras[$key] = $value === '' ? null : $value;
        }
    }

    save_info_row($video_url, $login_message, $extras);
    flash('success', 'Interface settings saved.');
    redirect('index.php?page=interface');
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

if ($page === 'board') {
    require __DIR__ . '/app/views/board.php';
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

if ($page === 'users') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    require __DIR__ . '/app/views/users.php';
    exit;
}

if ($page === 'profile') {
    require __DIR__ . '/app/views/profile.php';
    exit;
}

if ($page === 'interface') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    require __DIR__ . '/app/views/interface.php';
    exit;
}

require __DIR__ . '/app/views/board.php';
