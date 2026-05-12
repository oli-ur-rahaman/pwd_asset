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

if ($page === 'login') {
    require __DIR__ . '/app/views/login.php';
    exit;
}

require_login();

if ($action !== null && !csrf_validate($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Invalid CSRF token.');
}

if ($action === 'create_asset_category') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $name = input_str('name');
    if ($name === '') {
        flash('error', 'Category name is required.');
    } else {
        create_asset_category($name);
        flash('success', 'Category created.');
    }
    redirect('index.php?page=admin');
}

if ($action === 'update_asset_category') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $id = input_int('category_id');
    $name = input_str('name');
    if ($id <= 0 || $name === '') {
        flash('error', 'Category update failed.');
    } else {
        update_asset_category($id, $name);
        flash('success', 'Category updated.');
    }
    redirect('index.php?page=admin');
}

if ($action === 'toggle_asset_category') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    set_asset_category_status(input_int('category_id'), input_int('active_status', 1));
    flash('success', 'Category status updated.');
    redirect('index.php?page=admin');
}

if ($action === 'delete_asset_category') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    if (!delete_asset_category(input_int('category_id'))) {
        flash('error', 'Used categories cannot be deleted. Disable it instead.');
    } else {
        flash('success', 'Category deleted.');
    }
    redirect('index.php?page=admin');
}

if ($action === 'create_asset_subcategory') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $categoryId = input_int('category_id');
    $name = input_str('name');
    if ($categoryId <= 0 || $name === '') {
        flash('error', 'Sub-category তথ্য অসম্পূর্ণ।');
    } else {
        create_asset_subcategory($categoryId, $name);
        flash('success', 'Sub-category created.');
    }
    redirect('index.php?page=admin');
}

if ($action === 'update_asset_subcategory') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $id = input_int('subcategory_id');
    $categoryId = input_int('category_id');
    $name = input_str('name');
    if ($id <= 0 || $categoryId <= 0 || $name === '') {
        flash('error', 'Sub-category update failed.');
    } else {
        update_asset_subcategory($id, $categoryId, $name);
        flash('success', 'Sub-category updated.');
    }
    redirect('index.php?page=admin');
}

if ($action === 'toggle_asset_subcategory') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    set_asset_subcategory_status(input_int('subcategory_id'), input_int('active_status', 1));
    flash('success', 'Sub-category status updated.');
    redirect('index.php?page=admin');
}

if ($action === 'delete_asset_subcategory') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    if (!delete_asset_subcategory(input_int('subcategory_id'))) {
        flash('error', 'Used sub-categories cannot be deleted. Disable it instead.');
    } else {
        flash('success', 'Sub-category deleted.');
    }
    redirect('index.php?page=admin');
}

if ($action === 'create_asset_field' || $action === 'update_asset_field') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $fieldId = input_int('field_id');
    $validation = validate_asset_field_definition($_POST, $action === 'update_asset_field' ? $fieldId : null);
    if ($validation['errors']) {
        flash('error', implode(' ', $validation['errors']));
        redirect('index.php?page=admin');
    }
    if ($action === 'create_asset_field') {
        create_asset_field($validation['payload']);
        flash('success', 'Field created.');
    } else {
        update_asset_field($fieldId, $validation['payload']);
        flash('success', 'Field updated.');
    }
    redirect('index.php?page=admin');
}

if ($action === 'toggle_asset_field') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    set_asset_field_status(input_int('field_id'), input_int('active_status', 1));
    flash('success', 'Field status updated.');
    redirect('index.php?page=admin');
}

if ($action === 'delete_asset_field') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    if (!delete_asset_field(input_int('field_id'))) {
        flash('error', 'This field cannot be deleted. Disable it instead.');
    } else {
        flash('success', 'Field deleted.');
    }
    redirect('index.php?page=admin');
}

if ($action === 'upload_asset_template') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    try {
        save_uploaded_asset_template($_FILES['template_file'] ?? []);
        flash('success', 'Excel template uploaded.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('index.php?page=admin');
}

if ($action === 'asset_save') {
    $user = current_user();
    $assetId = input_int('asset_id');
    $validation = validate_asset_payload($_POST);
    if ($validation['errors']) {
        flash('error', implode(' ', array_values($validation['errors'])));
        redirect('index.php?page=board');
    }
    try {
        if ($assetId > 0) {
            $asset = get_asset($assetId, true);
            if (!$asset || !user_can_manage_asset($user, $asset)) {
                http_response_code(403);
                exit('Not allowed.');
            }
            update_asset($assetId, $validation['payload'], $user);
            flash('success', 'Asset updated.');
        } else {
            create_asset($validation['payload'], $user);
            flash('success', 'Asset saved.');
        }
    } catch (Throwable $e) {
        flash('error', 'Asset save failed: ' . $e->getMessage());
    }
    redirect('index.php?page=board');
}

if ($action === 'asset_bulk_delete') {
    $ids = $_POST['asset_ids'] ?? [];
    $deleted = soft_delete_assets(is_array($ids) ? $ids : [], current_user());
    flash($deleted > 0 ? 'success' : 'error', $deleted > 0 ? $deleted . ' asset(s) deleted.' : 'No assets were deleted.');
    redirect('index.php?page=board');
}

if ($action === 'asset_declare') {
    $ctx = current_office_context();
    if (!$ctx) {
        flash('error', 'Office declaration is not available for this user.');
    } else {
        declare_office_assets($ctx['office_type'], $ctx['office_id'], (int)current_user()['id']);
        add_log((int)current_user()['id'], 'office_asset_declarations', $ctx['office_id'], 'Office asset data declared up to date.');
        flash('success', 'Declaration saved.');
    }
    redirect('index.php?page=board');
}

if ($action === 'asset_reset_declarations') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $pairs = [];
    $rawPairs = $_POST['declarations'] ?? [];
    if (is_array($rawPairs)) {
        foreach ($rawPairs as $raw) {
            [$officeType, $officeId] = array_pad(explode(':', (string)$raw), 2, 0);
            $pairs[] = ['office_type' => (int)$officeType, 'office_id' => (int)$officeId];
        }
    } else {
        $pairs[] = ['office_type' => input_int('office_type'), 'office_id' => input_int('office_id')];
    }
    $count = reset_office_asset_declarations($pairs, (int)current_user()['id']);
    flash('success', $count . ' declaration(s) reset.');
    redirect('index.php?page=declarations');
}

if ($action === 'create_office') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $officeKind = input_str('office_kind');
    $name = input_str('office_name');
    $address = input_str('office_address');
    $email = input_str('email_id');
    if ($name === '' || $email === '') {
        flash('error', 'Office name and user email are required.');
        redirect('index.php?page=offices');
    }
    try {
        create_office_with_user($officeKind, $name, $address, $email, input_int('zone_id'), input_int('circle_id'));
        flash('success', 'Office and linked user created.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('index.php?page=offices');
}

if ($action === 'update_office') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $officeKind = input_str('office_kind');
    $officeId = input_int('office_id');
    $name = input_str('office_name');
    $address = input_str('office_address');
    $email = input_str('email_id');
    if ($officeId <= 0 || $name === '') {
        flash('error', 'Office update failed.');
        redirect('index.php?page=offices');
    }
    try {
        update_office_with_user($officeKind, $officeId, $name, $address, $email, input_int('zone_id'), input_int('circle_id'));
        flash('success', 'Office saved.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('index.php?page=offices');
}

if ($action === 'toggle_office_status') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    try {
        toggle_office_active_status(input_str('office_kind'), input_int('office_id'), input_int('active_status', 1) === 1 ? 1 : 0);
        flash('success', 'Office status updated.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('index.php?page=offices');
}

if ($action === 'reset_office_password') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    try {
        reset_office_user_password(input_str('office_kind'), input_int('office_id'));
        flash('success', 'Password reset to the default email-based format.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('index.php?page=offices');
}

if ($action === 'asset_import_upload') {
    if (empty($_FILES['asset_file']['tmp_name'])) {
        flash('error', 'Please choose an Excel file.');
        redirect('index.php?page=board');
    }
    $result = parse_asset_import_file($_FILES['asset_file']['tmp_name'], $_FILES['asset_file']['name'], current_user());
    if ($result['errors']) {
        flash('error', implode(' ', $result['errors']));
    } else {
        flash('success', 'Import file audited. Please review the rows below.');
    }
    redirect('index.php?page=board');
}

if ($action === 'asset_import_save') {
    $rows = $_POST['rows'] ?? [];
    if (!is_array($rows)) {
        flash('error', 'No import rows submitted.');
        redirect('index.php?page=board');
    }
    $reviewRows = restage_asset_import_rows($rows);
    $hasErrors = false;
    foreach ($reviewRows as $row) {
        if (!empty($row['errors'])) {
            $hasErrors = true;
            break;
        }
    }
    if ($hasErrors) {
        flash('error', 'Please fix the highlighted import rows before saving.');
        redirect('index.php?page=board');
    }
    $result = commit_asset_import_review(current_user());
    if ($result['errors']) {
        flash('error', implode(' ', $result['errors']));
    } else {
        flash('success', $result['saved'] . ' asset(s) imported successfully.');
    }
    redirect('index.php?page=board');
}

if ($action === 'asset_import_cancel') {
    unset($_SESSION['asset_import_review']);
    flash('success', 'Import review cleared.');
    redirect('index.php?page=board');
}

if ($action === 'csv_import') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    if (empty($_FILES['csv_file']['tmp_name'])) {
        flash('error', 'CSV file is required.');
        redirect('index.php?page=offices');
    }
    $type = input_str('import_type');
    $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
    if (!$handle) {
        flash('error', 'Unable to read CSV.');
        redirect('index.php?page=offices');
    }
    $headers = fgetcsv($handle);
    if (!$headers) {
        fclose($handle);
        flash('error', 'Invalid CSV headers.');
        redirect('index.php?page=offices');
    }
    $headers[0] = ltrim($headers[0], "\xEF\xBB\xBF");
    $count = 0;
    while (($row = fgetcsv($handle)) !== false) {
        $row = array_pad($row, count($headers), '');
        $data = array_combine($headers, array_slice($row, 0, count($headers)));
        if ($type === 'zones') {
            db()->prepare('INSERT INTO zones (office_name, office_address, office_type, created_at) VALUES (?, ?, ?, NOW())')->execute([
                trim((string)($data['office_name'] ?? '')),
                trim((string)($data['office_address'] ?? '')),
                (int)($data['office_type'] ?? 2),
            ]);
            $count++;
        } elseif ($type === 'circles') {
            db()->prepare('INSERT INTO circles (office_name, office_address, office_type, zone_id, created_at) VALUES (?, ?, ?, ?, NOW())')->execute([
                trim((string)($data['office_name'] ?? '')),
                trim((string)($data['office_address'] ?? '')),
                (int)($data['office_type'] ?? 3),
                !empty($data['zone_id']) ? (int)$data['zone_id'] : null,
            ]);
            $count++;
        } elseif ($type === 'divisions') {
            db()->prepare('INSERT INTO divisions (office_name, office_address, office_type, zone_id, circle_id, field_office, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())')->execute([
                trim((string)($data['office_name'] ?? '')),
                trim((string)($data['office_address'] ?? '')),
                (int)($data['office_type'] ?? 4),
                !empty($data['zone_id']) ? (int)$data['zone_id'] : null,
                !empty($data['circle_id']) ? (int)$data['circle_id'] : null,
                (int)($data['field_office'] ?? 1),
            ]);
            $count++;
        } elseif ($type === 'users') {
            $email = trim((string)($data['email_id'] ?? ''));
            if ($email === '') {
                continue;
            }
            $exists = db()->prepare('SELECT id FROM users WHERE email_id = ? LIMIT 1');
            $exists->execute([$email]);
            if ($exists->fetchColumn()) {
                continue;
            }
            db()->prepare('INSERT INTO users (email_id, officer_name, password, office_type, office_role, zone_id, circle_id, division_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())')->execute([
                $email,
                trim((string)($data['officer_name'] ?? '')),
                password_hash((string)($data['password'] ?? 'changeme'), PASSWORD_DEFAULT),
                (int)($data['office_type'] ?? 4),
                (int)($data['office_role'] ?? 1),
                !empty($data['zone_id']) ? (int)$data['zone_id'] : null,
                !empty($data['circle_id']) ? (int)$data['circle_id'] : null,
                !empty($data['division_id']) ? (int)$data['division_id'] : null,
            ]);
            $count++;
        }
    }
    fclose($handle);
    flash('success', 'Imported ' . $count . ' rows.');
    redirect('index.php?page=offices');
}

if ($action === 'update_profile') {
    $name = input_str('officer_name');
    $password = input_str('password');
    $confirm = input_str('password_confirm');
    $user = current_user();
    if ($name === '') {
        $name = (string)($user['officer_name'] ?? '');
    }
    if ($password !== '') {
        if ($password !== $confirm) {
            flash('error', 'Passwords do not match.');
            redirect('index.php?page=profile');
        }
        db()->prepare('UPDATE users SET officer_name = ?, password = ?, updated_at = NOW() WHERE id = ?')->execute([$name, password_hash($password, PASSWORD_DEFAULT), (int)$user['id']]);
    } else {
        db()->prepare('UPDATE users SET officer_name = ?, updated_at = NOW() WHERE id = ?')->execute([$name, (int)$user['id']]);
    }
    $_SESSION['user']['officer_name'] = $name;
    flash('success', 'Profile updated.');
    redirect('index.php?page=profile');
}

if ($action === 'reset_user_password') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $userId = input_int('user_id');
    $newPassword = input_str('new_password');
    if ($userId <= 0 || $newPassword === '') {
        flash('error', 'User and password are required.');
    } else {
        db()->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?')->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
        flash('success', 'Password reset.');
    }
    redirect('index.php?page=users');
}

if ($action === 'toggle_user_status') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $userId = input_int('user_id');
    $status = input_int('active_status', 1) === 1 ? 1 : 0;
    if ((int)current_user()['id'] === $userId && $status === 0) {
        flash('error', 'You cannot deactivate your own account.');
    } else {
        db()->prepare('UPDATE users SET active_status = ?, updated_at = NOW() WHERE id = ?')->execute([$status, $userId]);
        flash('success', $status === 1 ? 'User activated.' : 'User deactivated.');
    }
    redirect('index.php?page=users');
}

if ($action === 'update_user') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $userId = input_int('user_id');
    $email = input_str('email_id');
    $name = input_str('officer_name');
    $officeRole = input_int('office_role', 1);
    $officeType = input_int('office_type', 1);
    $zoneId = input_int('zone_id');
    $circleId = input_int('circle_id');
    $divisionId = input_int('division_id');
    if ($userId <= 0 || $email === '') {
        flash('error', 'User and email are required.');
        redirect('index.php?page=users');
    }
    db()->prepare('UPDATE users SET email_id = ?, officer_name = ?, office_role = ?, office_type = ?, zone_id = ?, circle_id = ?, division_id = ?, updated_at = NOW() WHERE id = ?')->execute([
        $email,
        $name === '' ? null : $name,
        $officeRole,
        $officeType,
        $zoneId > 0 ? $zoneId : null,
        $circleId > 0 ? $circleId : null,
        $divisionId > 0 ? $divisionId : null,
        $userId,
    ]);
    flash('success', 'User updated.');
    redirect('index.php?page=users');
}

if ($action === 'save_interface') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $existing = get_info_row();
    $extras = [];
    if (array_key_exists('site_name', $_POST)) {
        $extras['site_name'] = input_str('site_name');
    }
    save_info_row(
        array_key_exists('video_tutorial_url', $_POST) ? input_str('video_tutorial_url') : ($existing['video_tutorial_url'] ?? null),
        array_key_exists('login_message', $_POST) ? input_str('login_message') : ($existing['login_message'] ?? null),
        $extras
    );
    flash('success', 'Interface settings saved.');
    redirect('index.php?page=interface');
}

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

if ($page === 'offices') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    require __DIR__ . '/app/views/offices.php';
    exit;
}

if ($page === 'declarations') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    require __DIR__ . '/app/views/declarations.php';
    exit;
}

if ($page === 'users') {
    redirect('index.php?page=offices');
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
