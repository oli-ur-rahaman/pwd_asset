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

$adminRedirect = static function (): void {
    $segmentId = input_int('segment_id');
    $params = ['page' => 'admin'];
    if ($segmentId > 0) {
        $params['segment_id'] = $segmentId;
    }
    redirect('index.php?' . http_build_query($params));
};

$boardRedirect = static function (): void {
    $params = ['page' => 'board'];
    $segmentId = input_int('segment_id');
    if ($segmentId > 0) {
        $params['segment_id'] = $segmentId;
    }
    $officeViewScope = input_str('office_view_scope');
    if ($officeViewScope !== '') {
        $params['office_view_scope'] = $officeViewScope;
    }
    redirect('index.php?' . http_build_query($params));
};

if ($action === 'create_asset_segment') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    try {
        $segmentId = create_asset_segment(input_str('segment_name'), max(0, input_int('sort_order')));
        flash('success', 'Segment created.');
        redirect('index.php?' . http_build_query(['page' => 'admin', 'segment_id' => $segmentId]));
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        $adminRedirect();
    }
}

if ($action === 'update_asset_segment') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    try {
        $segmentId = input_int('segment_id');
        update_asset_segment($segmentId, input_str('segment_name'), max(0, input_int('sort_order')));
        flash('success', 'Segment updated.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    $adminRedirect();
}

if ($action === 'toggle_asset_segment') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    try {
        set_asset_segment_status(input_int('segment_id'), input_int('active_status', 1));
        flash('success', 'Segment status updated.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    $adminRedirect();
}

if ($action === 'create_asset_category') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $name = input_str('name');
    $segmentId = input_int('segment_id');
    if ($name === '') {
        flash('error', 'Category name is required.');
    } else {
        create_asset_category($name, $segmentId);
        flash('success', 'Category created.');
    }
    $adminRedirect();
}

if ($action === 'update_asset_category') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $id = input_int('category_id');
    $name = input_str('name');
    $segmentId = input_int('segment_id');
    if ($id <= 0 || $name === '') {
        flash('error', 'Category update failed.');
    } else {
        try {
            update_asset_category($id, $name, $segmentId);
            flash('success', 'Category updated.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
    }
    $adminRedirect();
}

if ($action === 'toggle_asset_category') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    try {
        set_asset_category_status(input_int('category_id'), input_int('active_status', 1), input_int('segment_id'));
        flash('success', 'Category status updated.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    $adminRedirect();
}

if ($action === 'delete_asset_category') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    try {
        if (!delete_asset_category(input_int('category_id'), input_int('segment_id'))) {
            flash('error', 'Used categories cannot be deleted. Disable it instead.');
        } else {
            flash('success', 'Category deleted.');
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    $adminRedirect();
}

if ($action === 'create_asset_subcategory') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $categoryId = input_int('category_id');
    $name = input_str('name');
    $segmentId = input_int('segment_id');
    if ($categoryId <= 0 || $name === '') {
        flash('error', 'Sub-category তথ্য অসম্পূর্ণ।');
    } else {
        try {
            create_asset_subcategory($categoryId, $name, $segmentId);
            flash('success', 'Sub-category created.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
    }
    $adminRedirect();
}

if ($action === 'update_asset_subcategory') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $id = input_int('subcategory_id');
    $categoryId = input_int('category_id');
    $name = input_str('name');
    $segmentId = input_int('segment_id');
    if ($id <= 0 || $categoryId <= 0 || $name === '') {
        flash('error', 'Sub-category update failed.');
    } else {
        try {
            update_asset_subcategory($id, $categoryId, $name, $segmentId);
            flash('success', 'Sub-category updated.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
    }
    $adminRedirect();
}

if ($action === 'toggle_asset_subcategory') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    try {
        set_asset_subcategory_status(input_int('subcategory_id'), input_int('active_status', 1), input_int('segment_id'));
        flash('success', 'Sub-category status updated.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    $adminRedirect();
}

if ($action === 'delete_asset_subcategory') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    try {
        if (!delete_asset_subcategory(input_int('subcategory_id'), input_int('segment_id'))) {
            flash('error', 'Used sub-categories cannot be deleted. Disable it instead.');
        } else {
            flash('success', 'Sub-category deleted.');
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    $adminRedirect();
}

if ($action === 'save_subcategory_visibility') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    set_asset_subcategory_enabled(!empty($_POST['asset_subcategory_enabled']) ? 1 : 0, input_int('segment_id'));
    flash('success', 'Sub-category visibility updated.');
    $adminRedirect();
}

if ($action === 'save_asset_scope_visibility_settings') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    save_asset_scope_visibility_settings($_POST['scope_visibility'] ?? [], input_int('segment_id'));
    flash('success', 'Office scope visibility updated.');
    $adminRedirect();
}

if ($action === 'save_asset_filter_card_visibility') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    set_asset_filter_card_visibility(
        !empty($_POST['show_filter_card_superadmin']) ? 1 : 0,
        !empty($_POST['show_filter_card_users']) ? 1 : 0,
        input_int('segment_id')
    );
    flash('success', 'Filter card visibility updated.');
    $adminRedirect();
}

if ($action === 'save_asset_bulk_import_visibility') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    set_asset_bulk_import_enabled(!empty($_POST['allow_bulk_import']) ? 1 : 0, input_int('segment_id'));
    flash('success', 'Bulk upload visibility updated.');
    $adminRedirect();
}

if ($action === 'save_asset_number_visibility') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    set_asset_number_visible_to_users(!empty($_POST['asset_number_visible_to_users']) ? 1 : 0, input_int('segment_id'));
    flash('success', 'Asset number visibility updated.');
    $adminRedirect();
}

if ($action === 'save_asset_data_provider_visibility') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    set_asset_data_provider_visible(!empty($_POST['show_data_provider_superadmin']) ? 1 : 0, input_int('segment_id'));
    flash('success', 'Data provider visibility updated.');
    $adminRedirect();
}

if ($action === 'create_asset_field' || $action === 'update_asset_field') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $fieldId = input_int('field_id');
    $validation = validate_asset_field_definition($_POST, $action === 'update_asset_field' ? $fieldId : null);
    if ($validation['errors']) {
        flash('error', implode(' ', $validation['errors']));
        $adminRedirect();
    }
    if ($action === 'create_asset_field') {
        create_asset_field($validation['payload']);
        flash('success', 'Field created.');
    } else {
        update_asset_field($fieldId, $validation['payload']);
        flash('success', 'Field updated.');
    }
    $adminRedirect();
}

if ($action === 'toggle_asset_field') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    try {
        set_asset_field_status(input_int('field_id'), input_int('active_status', 1), input_int('segment_id'));
        flash('success', 'Field status updated.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    $adminRedirect();
}

if ($action === 'delete_asset_field') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    try {
        if (!delete_asset_field(input_int('field_id'), input_int('segment_id'))) {
            flash('error', 'This field cannot be deleted. Disable it instead.');
        } else {
            flash('success', 'Field deleted.');
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    $adminRedirect();
}

if ($action === 'upload_asset_template') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    try {
        $summary = save_uploaded_asset_template($_FILES['template_file'] ?? [], input_int('segment_id'));
        flash('success', 'Excel template uploaded. Categories added: ' . (int)($summary['categories_created'] ?? 0) . ', sub-categories added: ' . (int)($summary['subcategories_created'] ?? 0) . '.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    $adminRedirect();
}

if ($action === 'upload_bimh_data') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    try {
        $summary = save_uploaded_bimh_workbook($_FILES['bimh_file'] ?? []);
        flash(
            'success',
            'BIMH data uploaded successfully. Total processed: '
            . (int)($summary['imported'] ?? 0)
            . ', new rows: ' . (int)($summary['inserted'] ?? 0)
            . ', updated rows: ' . (int)($summary['updated'] ?? 0) . '.'
        );
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    $adminRedirect();
}

if ($action === 'create_office_order') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    try {
        create_office_order(input_str('subject'), $_FILES['order_files'] ?? [], (int)current_user()['id']);
        flash('success', 'Office order uploaded.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('index.php?page=office_orders');
}

if ($action === 'asset_save') {
    $user = current_user();
    if (!can_modify_office_assets($user)) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $assetId = input_int('asset_id');
    $validation = validate_asset_payload($_POST, $assetId > 0 ? $assetId : null, $_FILES);
    if ($validation['errors']) {
        flash('error', implode(' ', array_values($validation['errors'])));
        $boardRedirect();
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
    $boardRedirect();
}

if ($action === 'asset_upload_field_files') {
    $user = current_user();
    if (!can_modify_office_assets($user)) {
        http_response_code(403);
        exit('Not allowed.');
    }
    try {
        upload_asset_files_for_field(input_int('asset_id'), input_str('field_key'), $user, $_FILES);
        flash('success', 'File uploaded successfully.');
    } catch (Throwable $e) {
        flash('error', 'File upload failed: ' . $e->getMessage());
    }
    $boardRedirect();
}

if ($action === 'asset_bulk_delete') {
    if (!can_modify_office_assets(current_user())) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $ids = $_POST['asset_ids'] ?? [];
    $deleted = soft_delete_assets(is_array($ids) ? $ids : [], current_user());
    flash($deleted > 0 ? 'success' : 'error', $deleted > 0 ? $deleted . ' asset(s) deleted.' : 'No assets were deleted.');
    $boardRedirect();
}

if ($action === 'asset_declare') {
    if (!can_modify_office_assets(current_user())) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $ctx = current_office_context();
    if (!$ctx) {
        flash('error', 'Office declaration is not available for this user.');
    } else {
        $segment = asset_active_segment(input_int('segment_id'));
        $segmentName = trim((string)($segment['segment_name'] ?? ''));
        $submissionCheck = validate_office_asset_declaration_requirements($ctx['office_type'], $ctx['office_id'], input_int('segment_id'), current_user());
        if (($submissionCheck['row_count'] ?? 0) > 0) {
            flash('error', (string)($submissionCheck['message'] ?? 'Action Required before submission.'));
        } else {
            declare_office_assets($ctx['office_type'], $ctx['office_id'], (int)current_user()['id'], input_int('segment_id'));
            add_log((int)current_user()['id'], 'office_asset_declarations', $ctx['office_id'], 'Office asset data declared up to date.');
            flash(
                'success',
                'This ' . ($segmentName !== '' ? $segmentName : 'segment') . ' table has been sent to admin. However you can still edit or add new information in the table.'
            );
        }
    }
    $boardRedirect();
}

if ($action === 'asset_reset_declarations') {
    if (!can_manage_superadmin_scope()) {
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
    $resetAllSegments = input_int('reset_all_segments', 0) === 1;
    if ($resetAllSegments) {
        $count = 0;
        foreach (get_asset_segments(true) as $segment) {
            $count += reset_office_asset_declarations($pairs, (int)current_user()['id'], (int)$segment['id']);
        }
        flash('success', $count . ' declaration(s) reset across all segments.');
    } else {
        $count = reset_office_asset_declarations($pairs, (int)current_user()['id'], input_int('segment_id'));
        flash('success', $count . ' declaration(s) reset.');
    }
    $params = ['page' => 'declarations'];
    $segmentId = input_int('segment_id');
    if (!$resetAllSegments && $segmentId > 0) {
        $params['segment_id'] = $segmentId;
    }
    redirect('index.php?' . http_build_query($params));
}

if ($action === 'create_office') {
    if (!can_manage_superadmin_scope()) {
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
        create_office_with_user($officeKind, $name, $address, $email, input_int('zone_id'), input_int('circle_id'), input_int('division_id'));
        flash('success', 'Office and linked user created.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('index.php?page=offices');
}

if ($action === 'update_office') {
    if (!can_manage_superadmin_scope()) {
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
        update_office_with_user($officeKind, $officeId, $name, $address, $email, input_int('zone_id'), input_int('circle_id'), input_int('division_id'));
        flash('success', 'Office saved.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('index.php?page=offices');
}

if ($action === 'save_office_user_management_flag') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    try {
        set_office_user_management_flag(input_str('office_kind'), input_int('office_id'), !empty($_POST['allow_office_user_management']) ? 1 : 0);
        flash('success', 'Office user management permission updated.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('index.php?page=offices');
}

if ($action === 'save_superadmin_additional_user') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    try {
        create_or_update_superadmin_additional_user(
            input_str('email_id'),
            input_str('officer_name'),
            ($id = input_int('managed_user_id')) > 0 ? $id : null
        );
        flash('success', 'Superadmin additional user saved. Default password is 1234.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('index.php?page=profile');
}

if ($action === 'reset_superadmin_additional_user_password') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    try {
        reset_superadmin_additional_user_password(input_int('managed_user_id'));
        flash('success', 'Password reset to 1234.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('index.php?page=profile');
}

if ($action === 'toggle_superadmin_additional_user_status') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    try {
        toggle_superadmin_additional_user_status(input_int('managed_user_id'), input_int('active_status', 1));
        flash('success', 'Superadmin additional user status updated.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('index.php?page=profile');
}

if ($action === 'save_additional_office_user') {
    $officeType = input_int('office_type');
    $officeId = input_int('office_id');
    if (!user_can_manage_office_users(current_user(), $officeType, $officeId)) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $returnPage = input_str('return_page', is_superadmin() ? 'offices' : 'profile');
    try {
        create_or_update_additional_office_user(
            $officeType,
            $officeId,
            input_str('email_id'),
            input_str('officer_name'),
            input_int('office_access_level', 2),
            ($id = input_int('managed_user_id')) > 0 ? $id : null
        );
        flash('success', 'Office user saved. Default password for newly added users is 1234.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('index.php?page=' . $returnPage);
}

if ($action === 'reset_additional_office_user_password') {
    $officeType = input_int('office_type');
    $officeId = input_int('office_id');
    if (!user_can_manage_office_users(current_user(), $officeType, $officeId)) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $returnPage = input_str('return_page', is_superadmin() ? 'offices' : 'profile');
    try {
        reset_additional_office_user_password($officeType, $officeId, input_int('managed_user_id'));
        flash('success', 'Password reset to 1234.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('index.php?page=' . $returnPage);
}

if ($action === 'toggle_additional_office_user_status') {
    $officeType = input_int('office_type');
    $officeId = input_int('office_id');
    if (!user_can_manage_office_users(current_user(), $officeType, $officeId)) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $returnPage = input_str('return_page', is_superadmin() ? 'offices' : 'profile');
    try {
        toggle_additional_office_user_status($officeType, $officeId, input_int('managed_user_id'), input_int('active_status', 1));
        flash('success', 'Office user status updated.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('index.php?page=' . $returnPage);
}

if ($action === 'toggle_office_status') {
    if (!can_manage_superadmin_scope()) {
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
    if (!can_manage_superadmin_scope()) {
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
    if (!can_modify_office_assets(current_user())) {
        http_response_code(403);
        exit('Not allowed.');
    }
    if (!asset_bulk_import_enabled(input_int('segment_id'))) {
        http_response_code(403);
        exit('Bulk upload is disabled for this segment.');
    }
    if (empty($_FILES['asset_file']['tmp_name'])) {
        flash('error', 'Please choose an Excel file.');
        $boardRedirect();
    }
    try {
        $result = parse_asset_import_file($_FILES['asset_file']['tmp_name'], $_FILES['asset_file']['name'], current_user(), input_int('segment_id'));
        if ($result['errors']) {
            flash('error', implode(' ', $result['errors']));
        } else {
            flash('success', 'Import file audited. Please review the rows below.');
        }
    } catch (Throwable $e) {
        flash('error', 'Failed to read the uploaded Excel file. ' . $e->getMessage());
    }
    $boardRedirect();
}

if ($action === 'asset_import_save') {
    if (!can_modify_office_assets(current_user())) {
        http_response_code(403);
        exit('Not allowed.');
    }
    if (!asset_bulk_import_enabled(input_int('segment_id'))) {
        http_response_code(403);
        exit('Bulk upload is disabled for this segment.');
    }
    $rows = $_POST['rows'] ?? [];
    if (!is_array($rows)) {
        unset($_SESSION['asset_import_review']);
        flash('success', 'Import review cleared.');
        $boardRedirect();
    }
    $reviewRows = restage_asset_import_rows($rows);
    if (!$reviewRows) {
        flash('success', 'Import review cleared.');
        $boardRedirect();
    }
    $result = commit_asset_import_review(current_user());
    if (($result['saved'] ?? 0) > 0 && ($result['remaining'] ?? 0) > 0) {
        flash('success', $result['saved'] . ' asset(s) imported. ' . $result['remaining'] . ' row(s) still need fixes.');
    } elseif (($result['saved'] ?? 0) > 0) {
        flash('success', $result['saved'] . ' asset(s) imported successfully.');
    } elseif ($result['errors']) {
        $message = ($result['remaining'] ?? 0) > 0
            ? 'Please fix the highlighted import rows before saving.'
            : implode(' ', array_unique(array_map('strval', $result['errors'])));
        flash('error', $message);
    } else {
        flash('success', 'No rows were imported.');
    }
    $boardRedirect();
}

if ($action === 'asset_import_cancel') {
    unset($_SESSION['asset_import_review']);
    flash('success', 'Import review cleared.');
    $boardRedirect();
}

if ($action === 'asset_download_data') {
    $user = current_user();
    $officeViewScope = input_str('office_view_scope', 'my_office');
    $segmentId = input_int('segment_id');
    if (is_superadmin()) {
        if (!$user) {
            http_response_code(403);
            exit('Not allowed.');
        }
    } elseif (!current_office_context($user)) {
        http_response_code(403);
        exit('Not allowed.');
    }
    try {
        if (is_superadmin()) {
            $scope = input_str('office_scope', 'zone');
            $filters = [
                'category_id' => input_int('category_id'),
                'condition_value' => input_str('condition_value', ''),
            ];
            if (asset_subcategory_enabled($segmentId)) {
                $filters['subcategory_id'] = input_int('subcategory_id');
            }
            if ($scope === 'zone') {
                $filters['office_type'] = 2;
                $filters['office_id'] = input_int('zone_id');
            } elseif ($scope === 'circle') {
                $filters['office_type'] = 3;
                $filters['office_id'] = input_int('circle_id');
            } elseif ($scope === 'division') {
                $filters['office_type'] = 4;
                $filters['office_id'] = input_int('division_id');
            } elseif ($scope === 'subdivision') {
                $filters['office_type'] = 5;
                $filters['office_id'] = input_int('subdivision_id');
            }
            $filters['segment_id'] = $segmentId;
            export_asset_data_excel($filters, $user, true);
        } else {
            $filters = ['office_view_scope' => $officeViewScope, 'segment_id' => $segmentId];
            export_asset_data_excel($filters, $user, $officeViewScope === 'office_under_me');
        }
    } catch (Throwable $e) {
        flash('error', 'Download failed: ' . $e->getMessage());
        $boardRedirect();
    }
}

if ($action === 'save_asset_table_visibility') {
    $categoryId = input_int('category_id');
    $tableScope = input_str('table_scope', 'my_office');
    $segmentId = input_int('segment_id');
    $visibleColumns = array_values(array_filter(array_map('strval', $_POST['visible_columns'] ?? [])));
    try {
        $boardFields = get_asset_fields(false, $segmentId);
        $boardLabels = [];
        foreach ($boardFields as $field) {
            $rawLabel = trim((string)($field['label'] ?? ''));
            $parts = preg_split('/\s*\/\s*/u', $rawLabel);
            $boardLabels[$field['field_key']] = trim((string)($parts[0] ?? $rawLabel));
        }
        save_asset_table_column_preferences(
            (int)current_user()['id'],
            $categoryId,
            asset_table_available_columns($boardFields, $boardLabels, $tableScope, $segmentId),
            $visibleColumns,
            !empty($_POST['apply_to_all']),
            $tableScope,
            $segmentId
        );
        flash('success', !empty($_POST['apply_to_all']) ? 'Column visibility applied to all tables.' : 'Column visibility saved.');
    } catch (Throwable $e) {
        flash('error', 'Column visibility save failed: ' . $e->getMessage());
    }
    $params = ['page' => 'board'];
    if ($segmentId > 0) {
        $params['segment_id'] = $segmentId;
    }
    $officeViewScope = input_str('office_view_scope');
    if ($officeViewScope !== '') {
        $params['office_view_scope'] = $officeViewScope;
    }
    redirect('index.php?' . http_build_query($params));
}

if ($action === 'csv_import') {
    if (!can_manage_superadmin_scope()) {
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
        } elseif ($type === 'subdivisions') {
            db()->prepare('INSERT INTO subdivisions (office_name, office_address, office_type, zone_id, circle_id, division_id, active_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())')->execute([
                trim((string)($data['office_name'] ?? '')),
                trim((string)($data['office_address'] ?? '')),
                (int)($data['office_type'] ?? 5),
                !empty($data['zone_id']) ? (int)$data['zone_id'] : null,
                !empty($data['circle_id']) ? (int)$data['circle_id'] : null,
                !empty($data['division_id']) ? (int)$data['division_id'] : null,
                (int)($data['active_status'] ?? 1),
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
            $officeType = (int)($data['office_type'] ?? 4);
            $zoneId = !empty($data['zone_id']) ? (int)$data['zone_id'] : null;
            $circleId = !empty($data['circle_id']) ? (int)$data['circle_id'] : null;
            $divisionId = !empty($data['division_id']) ? (int)$data['division_id'] : null;
            $subdivisionId = !empty($data['subdivision_id']) ? (int)$data['subdivision_id'] : null;
            $officeRole = (int)($data['office_role'] ?? 1);
            $primaryOfficeId = match ($officeType) {
                2 => $zoneId,
                3 => $circleId,
                4 => $divisionId,
                5 => $subdivisionId,
                default => null,
            };
            $isPrimary = $officeRole === 1 && $primaryOfficeId && !find_primary_office_user($officeType, (int)$primaryOfficeId);
            db()->prepare('INSERT INTO users (email_id, officer_name, password, office_type, office_role, zone_id, circle_id, division_id, subdivision_id, is_primary_office_user, office_access_level, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())')->execute([
                $email,
                trim((string)($data['officer_name'] ?? '')),
                password_hash((string)($data['password'] ?? 'changeme'), PASSWORD_DEFAULT),
                $officeType,
                $officeRole,
                $zoneId,
                $circleId,
                $divisionId,
                $subdivisionId,
                $isPrimary ? 1 : 0,
                $isPrimary ? 1 : 2,
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
    if (!can_manage_superadmin_scope()) {
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
    if (!can_manage_superadmin_scope()) {
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
    if (!can_manage_superadmin_scope()) {
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
    $subdivisionId = input_int('subdivision_id');
    if ($userId <= 0 || $email === '') {
        flash('error', 'User and email are required.');
        redirect('index.php?page=users');
    }
    db()->prepare('UPDATE users SET email_id = ?, officer_name = ?, office_role = ?, office_type = ?, zone_id = ?, circle_id = ?, division_id = ?, subdivision_id = ?, updated_at = NOW() WHERE id = ?')->execute([
        $email,
        $name === '' ? null : $name,
        $officeRole,
        $officeType,
        $zoneId > 0 ? $zoneId : null,
        $circleId > 0 ? $circleId : null,
        $divisionId > 0 ? $divisionId : null,
        $subdivisionId > 0 ? $subdivisionId : null,
        $userId,
    ]);
    flash('success', 'User updated.');
    redirect('index.php?page=users');
}

if ($action === 'save_interface') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $existing = get_info_row();
    $extras = [];
    if (array_key_exists('site_name', $_POST)) {
        $extras['site_name'] = input_str('site_name');
    }
    if (array_key_exists('welcome_message', $_POST)) {
        $extras['welcome_message'] = input_str('welcome_message');
    }
    if (array_key_exists('ui_theme_key', $_POST)) {
        $extras['ui_theme_key'] = input_str('ui_theme_key');
    }
    save_info_row(
        array_key_exists('video_tutorial_url', $_POST) ? input_str('video_tutorial_url') : ($existing['video_tutorial_url'] ?? null),
        array_key_exists('login_message', $_POST) ? input_str('login_message') : ($existing['login_message'] ?? null),
        $extras
    );
    flash('success', 'Interface settings saved.');
    redirect('index.php?page=interface');
}

if ($action === 'bulk_update_office_user_management_permissions') {
    if (!can_manage_superadmin_scope()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $pairs = [];
    foreach (($_POST['offices'] ?? []) as $raw) {
        [$officeType, $officeId] = array_pad(explode(':', (string)$raw), 2, 0);
        $pairs[] = ['office_type' => (int)$officeType, 'office_id' => (int)$officeId];
    }
    try {
        $updated = bulk_set_office_user_management_permissions($pairs, input_int('allowed_status', 1) === 1 ? 1 : 0);
        flash('success', $updated . ' office permission(s) updated.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('index.php?page=user_permissions');
}

if ($page === 'logout') {
    logout_user();
    redirect('index.php?page=login');
}

if ($page === 'logs') {
    redirect('index.php?page=board');
}

if ($page === 'board') {
    require __DIR__ . '/app/views/board.php';
    exit;
}

if ($page === 'office_order_file') {
    stream_office_order_file((int)request_str('id', '0'));
}

if ($page === 'asset_file') {
    stream_asset_file((int)request_str('id', '0'), current_user());
}

if ($page === 'bimh_lookup') {
    $user = current_user();
    if (!$user) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'Not allowed.']);
        exit;
    }
    $bimhId = trim((string)request_str('bimh_id', ''));
    $estName = asset_bimh_est_name_for_id($bimhId);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'bimh_id' => $bimhId,
        'est_name' => $estName,
        'found' => $bimhId !== '' && $estName !== '' && $estName !== 'BIMH ID is not in the Database.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($page === 'office_orders') {
    require __DIR__ . '/app/views/office_orders.php';
    exit;
}

if ($page === 'csv_template') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    $type = request_str('type');
    $map = [
        'zones' => 'zone_template.csv',
        'circles' => 'circle_template.csv',
        'divisions' => 'division_template.csv',
        'subdivisions' => 'subdivision_template.csv',
        'users' => 'users_template.csv',
    ];
    if (!isset($map[$type])) {
        http_response_code(404);
        exit('Template not found.');
    }
    $path = __DIR__ . '/csv/' . $map[$type];
    if (!is_file($path)) {
        http_response_code(404);
        exit('Template not found.');
    }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . basename($path) . '"');
    header('Content-Length: ' . (string)filesize($path));
    readfile($path);
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

if ($page === 'user_permissions') {
    if (!is_superadmin()) {
        http_response_code(403);
        exit('Not allowed.');
    }
    require __DIR__ . '/app/views/user_permissions.php';
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
