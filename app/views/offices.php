<?php
require __DIR__ . '/header.php';
$canManageSuperadmin = can_manage_superadmin_scope();
$overview = get_offices_overview();
$zones = $overview['zones'];
$circles = $overview['circles'];
$divisions = $overview['divisions'];
$subdivisions = $overview['subdivisions'];
$accessOptions = office_user_access_options();
$renderManageUsersModal = static function (string $officeKind, int $officeType, int $officeId, string $officeName, bool $allowManagement, array $officeUsers, array $accessOptions): void {
    $modalId = 'manage-users-' . $officeKind . '-' . $officeId;
    ?>
    <div class="modal-backdrop" id="<?= e($modalId); ?>" aria-hidden="true">
        <div class="modal-card modal-wide" role="dialog" aria-modal="true" aria-labelledby="<?= e($modalId); ?>-title">
            <div class="hero-row office-manage-users-head">
                <div>
                    <h3 id="<?= e($modalId); ?>-title">Manage Users: <?= e($officeName); ?></h3>
                    <p class="hint">Default password for newly added users is 1234.</p>
                </div>
                <button type="button" class="modal-close" data-close="<?= e($modalId); ?>">Close</button>
            </div>
            <div class="office-manage-users-permission">
                <form method="post" action="index.php" class="inline-form">
                    <?= csrf_input(); ?>
                    <input type="hidden" name="action" value="save_office_user_management_flag">
                    <input type="hidden" name="office_kind" value="<?= e($officeKind); ?>">
                    <input type="hidden" name="office_id" value="<?= e((string)$officeId); ?>">
                    <label class="inline-check">
                        <input type="checkbox" name="allow_office_user_management" value="1" <?= $allowManagement ? 'checked' : ''; ?>>
                        Allow this office to manage additional users
                    </label>
                    <button type="submit" class="btn-small">Save Permission</button>
                </form>
            </div>
            <div class="table-wrap">
                <table class="office-users-table">
                    <thead>
                    <tr>
                        <th>SL</th>
                        <th>User Name</th>
                        <th>ID</th>
                        <th>Password</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody data-managed-user-body>
                    <?php foreach ($officeUsers as $index => $officeUser): ?>
                        <?php $isPrimary = (int)($officeUser['is_primary_office_user'] ?? 0) === 1; ?>
                        <tr>
                            <td><?= e((string)($index + 1)); ?></td>
                            <td>
                                <input type="text" name="<?= $isPrimary ? '' : 'officer_name'; ?>" value="<?= e((string)($officeUser['officer_name'] ?? '')); ?>" <?= $isPrimary ? 'readonly' : ''; ?> <?= $isPrimary ? '' : 'form="' . e('manage-user-form-' . (int)$officeUser['id']) . '"'; ?>>
                            </td>
                            <td>
                                <input type="email" name="<?= $isPrimary ? '' : 'email_id'; ?>" value="<?= e((string)$officeUser['email_id']); ?>" <?= $isPrimary ? 'readonly' : ''; ?> <?= $isPrimary ? '' : 'form="' . e('manage-user-form-' . (int)$officeUser['id']) . '"'; ?>>
                            </td>
                            <td><?= $isPrimary ? 'Office Head' : '1234'; ?></td>
                            <td>
                                <?php if ($isPrimary): ?>
                                    <input type="text" value="Office Head" readonly>
                                <?php else: ?>
                                    <select name="office_access_level" form="<?= e('manage-user-form-' . (int)$officeUser['id']); ?>">
                                        <?php foreach ($accessOptions as $level => $label): ?>
                                            <?php if ($level === 1) continue; ?>
                                            <option value="<?= e((string)$level); ?>" <?= (int)$officeUser['office_access_level'] === $level ? 'selected' : ''; ?>><?= e($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-row">
                                    <?php if (!$isPrimary): ?>
                                        <form method="post" action="index.php" class="inline-form" id="<?= e('manage-user-form-' . (int)$officeUser['id']); ?>">
                                            <?= csrf_input(); ?>
                                            <input type="hidden" name="action" value="save_additional_office_user">
                                            <input type="hidden" name="office_type" value="<?= e((string)$officeType); ?>">
                                            <input type="hidden" name="office_id" value="<?= e((string)$officeId); ?>">
                                            <input type="hidden" name="managed_user_id" value="<?= e((string)$officeUser['id']); ?>">
                                            <input type="hidden" name="return_page" value="offices">
                                            <button type="submit" class="btn-small">Save</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" action="index.php" class="inline-form">
                                        <?= csrf_input(); ?>
                                        <input type="hidden" name="action" value="reset_additional_office_user_password">
                                        <input type="hidden" name="office_type" value="<?= e((string)$officeType); ?>">
                                        <input type="hidden" name="office_id" value="<?= e((string)$officeId); ?>">
                                        <input type="hidden" name="managed_user_id" value="<?= e((string)$officeUser['id']); ?>">
                                        <input type="hidden" name="return_page" value="offices">
                                        <button type="submit" class="btn-small">Reset Password</button>
                                    </form>
                                    <?php if (!$isPrimary): ?>
                                        <form method="post" action="index.php" class="inline-form">
                                            <?= csrf_input(); ?>
                                            <input type="hidden" name="action" value="toggle_additional_office_user_status">
                                            <input type="hidden" name="office_type" value="<?= e((string)$officeType); ?>">
                                            <input type="hidden" name="office_id" value="<?= e((string)$officeId); ?>">
                                            <input type="hidden" name="managed_user_id" value="<?= e((string)$officeUser['id']); ?>">
                                            <input type="hidden" name="active_status" value="<?= (int)($officeUser['active_status'] ?? 1) === 1 ? '0' : '1'; ?>">
                                            <input type="hidden" name="return_page" value="offices">
                                            <button type="submit" class="btn-small <?= (int)($officeUser['active_status'] ?? 1) === 1 ? 'btn-danger' : ''; ?>"><?= (int)($officeUser['active_status'] ?? 1) === 1 ? 'Disable' : 'Enable'; ?></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <template data-managed-user-template>
                <tr>
                    <td data-managed-user-sl></td>
                    <td><input type="text" name="officer_name" form="__FORM_ID__"></td>
                    <td><input type="email" name="email_id" form="__FORM_ID__" required></td>
                    <td>1234</td>
                    <td>
                        <select name="office_access_level" form="__FORM_ID__">
                            <?php foreach ($accessOptions as $level => $label): ?>
                                <?php if ($level === 1) continue; ?>
                                <option value="<?= e((string)$level); ?>" <?= $level === 2 ? 'selected' : ''; ?>><?= e($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <div class="action-row">
                            <form method="post" action="index.php" class="inline-form managed-user-create-form" id="__FORM_ID__">
                                <?= csrf_input(); ?>
                                <input type="hidden" name="action" value="save_additional_office_user">
                                <input type="hidden" name="office_type" value="<?= e((string)$officeType); ?>">
                                <input type="hidden" name="office_id" value="<?= e((string)$officeId); ?>">
                                <input type="hidden" name="return_page" value="offices">
                                <button type="submit" class="btn-small">Save</button>
                            </form>
                        </div>
                    </td>
                </tr>
            </template>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" data-add-managed-user-row="<?= e($modalId); ?>">+ Add Row</button>
                <button type="button" class="modal-close" data-close="<?= e($modalId); ?>">Close</button>
            </div>
        </div>
    </div>
    <?php
};
?>
<?php if (!$canManageSuperadmin): ?>
    <style>
        .superadmin-readonly-page button[type="submit"],
        .superadmin-readonly-page [data-modal],
        .superadmin-readonly-page .btn-danger,
        .superadmin-readonly-page .office-save-button {
            display: none !important;
        }
        .superadmin-readonly-page input:not([type="hidden"]),
        .superadmin-readonly-page select,
        .superadmin-readonly-page textarea {
            pointer-events: none;
            background: #f4f6f8;
            color: #425466;
        }
    </style>
<?php endif; ?>
<div class="<?= !$canManageSuperadmin ? 'superadmin-readonly-page' : ''; ?>">
<?php if (!$canManageSuperadmin): ?>
    <section class="card">
        <p class="hint">View-only superadmin users can review office data and template links here, but cannot change offices, import CSV, or manage office users.</p>
    </section>
<?php endif; ?>
<section class="card hero-card">
    <div class="hero-row office-page-head">
        <div>
            <h2>Office Management</h2>
            <p class="hint">Add offices, move hierarchy, manage linked user emails, and control office access from one screen.</p>
        </div>
        <button type="button" data-modal="office-create-modal">+ Add</button>
    </div>
</section>

<section class="card">
    <h2>Bulk CSV Import</h2>
    <form method="post" action="index.php" enctype="multipart/form-data" class="grid">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="csv_import">
        <label>Type
            <select name="import_type">
                <option value="zones">Zones</option>
                <option value="circles">Circles</option>
                <option value="divisions">Divisions</option>
                <option value="subdivisions">Sub-divisions</option>
                <option value="users">Users</option>
            </select>
        </label>
        <label>CSV File
            <input type="file" name="csv_file" accept=".csv" required>
        </label>
        <button type="submit">Import CSV</button>
    </form>
    <div class="toolbar-row office-template-links">
        <a href="index.php?page=csv_template&amp;type=zones" class="button-link">Zones Template</a>
        <a href="index.php?page=csv_template&amp;type=circles" class="button-link">Circles Template</a>
        <a href="index.php?page=csv_template&amp;type=divisions" class="button-link">Divisions Template</a>
        <a href="index.php?page=csv_template&amp;type=subdivisions" class="button-link">Sub-divisions Template</a>
        <a href="index.php?page=csv_template&amp;type=users" class="button-link">Users Template</a>
    </div>
</section>

<section class="card">
    <h2>Zones</h2>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Zone Name</th>
                <th>Address</th>
                <th>User Email</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($zones as $zone): ?>
                <?php
                $user = $zone['linked_user'] ?? null;
                $formId = 'office-zone-' . (int)$zone['id'];
                $isActive = (int)($zone['active_status'] ?? 1) === 1;
                ?>
                <tr>
                    <td>
                        <input form="<?= e($formId); ?>" class="inline-edit" type="text" name="office_name" value="<?= e($zone['office_name']); ?>" required>
                    </td>
                    <td>
                        <input form="<?= e($formId); ?>" class="inline-edit" type="text" name="office_address" value="<?= e((string)($zone['office_address'] ?? '')); ?>">
                    </td>
                    <td>
                        <input form="<?= e($formId); ?>" class="inline-edit" type="email" name="email_id" value="<?= e((string)($user['email_id'] ?? '')); ?>" placeholder="zone@pwd.gov.bd" required>
                    </td>
                    <td><span class="<?= $isActive ? 'status-active' : 'status-inactive'; ?>"><?= $isActive ? 'Active' : 'Inactive'; ?></span></td>
                    <td>
                        <div class="action-row">
                            <form method="post" action="index.php" id="<?= e($formId); ?>" class="office-inline-form">
                                <?= csrf_input(); ?>
                                <input type="hidden" name="action" value="update_office">
                                <input type="hidden" name="office_kind" value="zone">
                                <input type="hidden" name="office_id" value="<?= e((string)$zone['id']); ?>">
                                <button type="submit" class="btn-small office-save-button">Save</button>
                            </form>
                            <button type="button" class="btn-small" data-modal="manage-users-zone-<?= e((string)$zone['id']); ?>">Manage Users</button>
                            <form method="post" action="index.php" class="inline-form">
                                <?= csrf_input(); ?>
                                <input type="hidden" name="action" value="reset_office_password">
                                <input type="hidden" name="office_kind" value="zone">
                                <input type="hidden" name="office_id" value="<?= e((string)$zone['id']); ?>">
                                <button type="submit" class="btn-small">Reset Password</button>
                            </form>
                            <form method="post" action="index.php" class="inline-form">
                                <?= csrf_input(); ?>
                                <input type="hidden" name="action" value="toggle_office_status">
                                <input type="hidden" name="office_kind" value="zone">
                                <input type="hidden" name="office_id" value="<?= e((string)$zone['id']); ?>">
                                <input type="hidden" name="active_status" value="<?= $isActive ? '0' : '1'; ?>">
                                <button type="submit" class="btn-small <?= $isActive ? 'btn-danger' : ''; ?>"><?= $isActive ? 'Deactivate' : 'Activate'; ?></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php foreach ($zones as $zone): ?>
    <?php $renderManageUsersModal('zone', 2, (int)$zone['id'], (string)$zone['office_name'], (int)($zone['allow_office_user_management'] ?? 1) === 1, array_values(array_filter(get_office_users(2, (int)$zone['id']), static fn(array $officeUser): bool => (int)($officeUser['is_primary_office_user'] ?? 0) !== 1)), $accessOptions); ?>
<?php endforeach; ?>

<section class="card">
    <h2>Sub-divisions</h2>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Sub-division Name</th>
                <th>Address</th>
                <th>Division</th>
                <th>Circle</th>
                <th>Zone</th>
                <th>User Email</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($subdivisions as $subdivision): ?>
                <?php
                $user = $subdivision['linked_user'] ?? null;
                $formId = 'office-subdivision-' . (int)$subdivision['id'];
                $isActive = (int)($subdivision['active_status'] ?? 1) === 1;
                ?>
                <tr>
                    <td><input form="<?= e($formId); ?>" class="inline-edit" type="text" name="office_name" value="<?= e($subdivision['office_name']); ?>" required></td>
                    <td><input form="<?= e($formId); ?>" class="inline-edit" type="text" name="office_address" value="<?= e((string)($subdivision['office_address'] ?? '')); ?>"></td>
                    <td>
                        <select form="<?= e($formId); ?>" class="inline-edit office-division-select" name="division_id" data-target-circle="subdivision-circle-display-<?= e((string)$subdivision['id']); ?>" data-target-zone="subdivision-zone-display-<?= e((string)$subdivision['id']); ?>" required>
                            <?php foreach ($divisions as $division): ?>
                                <option value="<?= e((string)$division['id']); ?>" data-circle-name="<?= e((string)$division['circle_name']); ?>" data-zone-name="<?= e((string)$division['zone_name']); ?>" <?= (int)$subdivision['division_id'] === (int)$division['id'] ? 'selected' : ''; ?>><?= e($division['office_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input id="subdivision-circle-display-<?= e((string)$subdivision['id']); ?>" class="inline-readonly" type="text" value="<?= e((string)($subdivision['circle_name'] ?? '')); ?>" readonly></td>
                    <td><input id="subdivision-zone-display-<?= e((string)$subdivision['id']); ?>" class="inline-readonly" type="text" value="<?= e((string)($subdivision['zone_name'] ?? '')); ?>" readonly></td>
                    <td><input form="<?= e($formId); ?>" class="inline-edit" type="email" name="email_id" value="<?= e((string)($user['email_id'] ?? '')); ?>" placeholder="ee_xyz@pwd.gov.bd" required></td>
                    <td><span class="<?= $isActive ? 'status-active' : 'status-inactive'; ?>"><?= $isActive ? 'Active' : 'Inactive'; ?></span></td>
                    <td>
                        <div class="action-row">
                            <form method="post" action="index.php" id="<?= e($formId); ?>" class="office-inline-form">
                                <?= csrf_input(); ?>
                                <input type="hidden" name="action" value="update_office">
                                <input type="hidden" name="office_kind" value="subdivision">
                                <input type="hidden" name="office_id" value="<?= e((string)$subdivision['id']); ?>">
                                <button type="submit" class="btn-small office-save-button">Save</button>
                            </form>
                            <button type="button" class="btn-small" data-modal="manage-users-subdivision-<?= e((string)$subdivision['id']); ?>">Manage Users</button>
                            <form method="post" action="index.php" class="inline-form">
                                <?= csrf_input(); ?>
                                <input type="hidden" name="action" value="reset_office_password">
                                <input type="hidden" name="office_kind" value="subdivision">
                                <input type="hidden" name="office_id" value="<?= e((string)$subdivision['id']); ?>">
                                <button type="submit" class="btn-small">Reset Password</button>
                            </form>
                            <form method="post" action="index.php" class="inline-form">
                                <?= csrf_input(); ?>
                                <input type="hidden" name="action" value="toggle_office_status">
                                <input type="hidden" name="office_kind" value="subdivision">
                                <input type="hidden" name="office_id" value="<?= e((string)$subdivision['id']); ?>">
                                <input type="hidden" name="active_status" value="<?= $isActive ? '0' : '1'; ?>">
                                <button type="submit" class="btn-small <?= $isActive ? 'btn-danger' : ''; ?>"><?= $isActive ? 'Deactivate' : 'Activate'; ?></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php foreach ($subdivisions as $subdivision): ?>
    <?php $renderManageUsersModal('subdivision', 5, (int)$subdivision['id'], (string)$subdivision['office_name'], (int)($subdivision['allow_office_user_management'] ?? 1) === 1, array_values(array_filter(get_office_users(5, (int)$subdivision['id']), static fn(array $officeUser): bool => (int)($officeUser['is_primary_office_user'] ?? 0) !== 1)), $accessOptions); ?>
<?php endforeach; ?>

<section class="card">
    <h2>Circles</h2>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Circle Name</th>
                <th>Address</th>
                <th>Zone</th>
                <th>User Email</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($circles as $circle): ?>
                <?php
                $user = $circle['linked_user'] ?? null;
                $formId = 'office-circle-' . (int)$circle['id'];
                $isActive = (int)($circle['active_status'] ?? 1) === 1;
                ?>
                <tr>
                    <td>
                        <input form="<?= e($formId); ?>" class="inline-edit" type="text" name="office_name" value="<?= e($circle['office_name']); ?>" required>
                    </td>
                    <td>
                        <input form="<?= e($formId); ?>" class="inline-edit" type="text" name="office_address" value="<?= e((string)($circle['office_address'] ?? '')); ?>">
                    </td>
                    <td>
                        <select form="<?= e($formId); ?>" class="inline-edit" name="zone_id" required>
                            <?php foreach ($zones as $zone): ?>
                                <option value="<?= e((string)$zone['id']); ?>" <?= (int)$circle['zone_id'] === (int)$zone['id'] ? 'selected' : ''; ?>><?= e($zone['office_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <input form="<?= e($formId); ?>" class="inline-edit" type="email" name="email_id" value="<?= e((string)($user['email_id'] ?? '')); ?>" placeholder="circle@pwd.gov.bd" required>
                    </td>
                    <td><span class="<?= $isActive ? 'status-active' : 'status-inactive'; ?>"><?= $isActive ? 'Active' : 'Inactive'; ?></span></td>
                    <td>
                        <div class="action-row">
                            <form method="post" action="index.php" id="<?= e($formId); ?>" class="office-inline-form">
                                <?= csrf_input(); ?>
                                <input type="hidden" name="action" value="update_office">
                                <input type="hidden" name="office_kind" value="circle">
                                <input type="hidden" name="office_id" value="<?= e((string)$circle['id']); ?>">
                                <button type="submit" class="btn-small office-save-button">Save</button>
                            </form>
                            <button type="button" class="btn-small" data-modal="manage-users-circle-<?= e((string)$circle['id']); ?>">Manage Users</button>
                            <form method="post" action="index.php" class="inline-form">
                                <?= csrf_input(); ?>
                                <input type="hidden" name="action" value="reset_office_password">
                                <input type="hidden" name="office_kind" value="circle">
                                <input type="hidden" name="office_id" value="<?= e((string)$circle['id']); ?>">
                                <button type="submit" class="btn-small">Reset Password</button>
                            </form>
                            <form method="post" action="index.php" class="inline-form">
                                <?= csrf_input(); ?>
                                <input type="hidden" name="action" value="toggle_office_status">
                                <input type="hidden" name="office_kind" value="circle">
                                <input type="hidden" name="office_id" value="<?= e((string)$circle['id']); ?>">
                                <input type="hidden" name="active_status" value="<?= $isActive ? '0' : '1'; ?>">
                                <button type="submit" class="btn-small <?= $isActive ? 'btn-danger' : ''; ?>"><?= $isActive ? 'Deactivate' : 'Activate'; ?></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php foreach ($circles as $circle): ?>
    <?php $renderManageUsersModal('circle', 3, (int)$circle['id'], (string)$circle['office_name'], (int)($circle['allow_office_user_management'] ?? 1) === 1, array_values(array_filter(get_office_users(3, (int)$circle['id']), static fn(array $officeUser): bool => (int)($officeUser['is_primary_office_user'] ?? 0) !== 1)), $accessOptions); ?>
<?php endforeach; ?>

<section class="card">
    <h2>Divisions</h2>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Division Name</th>
                <th>Address</th>
                <th>Circle</th>
                <th>Zone</th>
                <th>User Email</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($divisions as $division): ?>
                <?php
                $user = $division['linked_user'] ?? null;
                $formId = 'office-division-' . (int)$division['id'];
                $isActive = (int)($division['active_status'] ?? 1) === 1;
                ?>
                <tr>
                    <td>
                        <input form="<?= e($formId); ?>" class="inline-edit" type="text" name="office_name" value="<?= e($division['office_name']); ?>" required>
                    </td>
                    <td>
                        <input form="<?= e($formId); ?>" class="inline-edit" type="text" name="office_address" value="<?= e((string)($division['office_address'] ?? '')); ?>">
                    </td>
                    <td>
                        <select form="<?= e($formId); ?>" class="inline-edit office-circle-select" name="circle_id" data-target-zone="zone-display-<?= e((string)$division['id']); ?>" required>
                            <?php foreach ($circles as $circle): ?>
                                <option value="<?= e((string)$circle['id']); ?>" data-zone-id="<?= e((string)$circle['zone_id']); ?>" data-zone-name="<?= e((string)$circle['zone_name']); ?>" <?= (int)$division['circle_id'] === (int)$circle['id'] ? 'selected' : ''; ?>><?= e($circle['office_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <input id="zone-display-<?= e((string)$division['id']); ?>" class="inline-readonly" type="text" value="<?= e((string)($division['zone_name'] ?? '')); ?>" readonly>
                    </td>
                    <td>
                        <input form="<?= e($formId); ?>" class="inline-edit" type="email" name="email_id" value="<?= e((string)($user['email_id'] ?? '')); ?>" placeholder="division@pwd.gov.bd" required>
                    </td>
                    <td><span class="<?= $isActive ? 'status-active' : 'status-inactive'; ?>"><?= $isActive ? 'Active' : 'Inactive'; ?></span></td>
                    <td>
                        <div class="action-row">
                            <form method="post" action="index.php" id="<?= e($formId); ?>" class="office-inline-form">
                                <?= csrf_input(); ?>
                                <input type="hidden" name="action" value="update_office">
                                <input type="hidden" name="office_kind" value="division">
                                <input type="hidden" name="office_id" value="<?= e((string)$division['id']); ?>">
                                <button type="submit" class="btn-small office-save-button">Save</button>
                            </form>
                            <button type="button" class="btn-small" data-modal="manage-users-division-<?= e((string)$division['id']); ?>">Manage Users</button>
                            <form method="post" action="index.php" class="inline-form">
                                <?= csrf_input(); ?>
                                <input type="hidden" name="action" value="reset_office_password">
                                <input type="hidden" name="office_kind" value="division">
                                <input type="hidden" name="office_id" value="<?= e((string)$division['id']); ?>">
                                <button type="submit" class="btn-small">Reset Password</button>
                            </form>
                            <form method="post" action="index.php" class="inline-form">
                                <?= csrf_input(); ?>
                                <input type="hidden" name="action" value="toggle_office_status">
                                <input type="hidden" name="office_kind" value="division">
                                <input type="hidden" name="office_id" value="<?= e((string)$division['id']); ?>">
                                <input type="hidden" name="active_status" value="<?= $isActive ? '0' : '1'; ?>">
                                <button type="submit" class="btn-small <?= $isActive ? 'btn-danger' : ''; ?>"><?= $isActive ? 'Deactivate' : 'Activate'; ?></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php foreach ($divisions as $division): ?>
    <?php $renderManageUsersModal('division', 4, (int)$division['id'], (string)$division['office_name'], (int)($division['allow_office_user_management'] ?? 1) === 1, array_values(array_filter(get_office_users(4, (int)$division['id']), static fn(array $officeUser): bool => (int)($officeUser['is_primary_office_user'] ?? 0) !== 1)), $accessOptions); ?>
<?php endforeach; ?>

<div class="modal-backdrop" id="office-create-modal" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="office-create-title">
        <h3 id="office-create-title">Add Office</h3>
        <form method="post" action="index.php" class="grid" id="office-create-form">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="create_office">
            <input type="hidden" name="office_kind" value="zone" id="office-kind-input">
            <div class="segmented-control" role="tablist" aria-label="Office type">
                <button type="button" class="segment is-active" data-office-kind="zone">Zone</button>
                <button type="button" class="segment" data-office-kind="circle">Circle</button>
                <button type="button" class="segment" data-office-kind="division">Division</button>
                <button type="button" class="segment" data-office-kind="subdivision">Sub-division</button>
            </div>
            <div class="grid office-kind-panel" data-office-kind-panel="zone">
                <label>Zone Name
                    <input type="text" name="office_name_zone">
                </label>
                <label>Address
                    <input type="text" name="office_address_zone">
                </label>
                <label>User Email
                    <input type="email" name="email_id_zone" placeholder="ee_xyz@pwd.gov.bd">
                </label>
            </div>
            <div class="grid office-kind-panel hidden" data-office-kind-panel="circle">
                <label>Circle Name
                    <input type="text" name="office_name_circle">
                </label>
                <label>Address
                    <input type="text" name="office_address_circle">
                </label>
                <label>Zone
                    <select name="zone_id_circle">
                        <option value="">Select zone</option>
                        <?php foreach ($zones as $zone): ?>
                            <option value="<?= e((string)$zone['id']); ?>"><?= e($zone['office_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>User Email
                    <input type="email" name="email_id_circle" placeholder="ee_xyz@pwd.gov.bd">
                </label>
            </div>
            <div class="grid office-kind-panel hidden" data-office-kind-panel="division">
                <label>Division Name
                    <input type="text" name="office_name_division">
                </label>
                <label>Address
                    <input type="text" name="office_address_division">
                </label>
                <label>Circle
                    <select name="circle_id_division" id="office-create-circle-select">
                        <option value="">Select circle</option>
                        <?php foreach ($circles as $circle): ?>
                            <option value="<?= e((string)$circle['id']); ?>" data-zone-id="<?= e((string)$circle['zone_id']); ?>" data-zone-name="<?= e((string)$circle['zone_name']); ?>"><?= e($circle['office_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Zone
                    <input type="text" id="office-create-zone-display" readonly placeholder="Auto from selected circle">
                </label>
                <input type="hidden" name="zone_id_division" id="office-create-zone-id">
                <label>User Email
                    <input type="email" name="email_id_division" placeholder="ee_xyz@pwd.gov.bd">
                </label>
            </div>
            <div class="grid office-kind-panel hidden" data-office-kind-panel="subdivision">
                <label>Sub-division Name
                    <input type="text" name="office_name_subdivision">
                </label>
                <label>Address
                    <input type="text" name="office_address_subdivision">
                </label>
                <label>Division
                    <select name="division_id_subdivision" id="office-create-division-select">
                        <option value="">Select division</option>
                        <?php foreach ($divisions as $division): ?>
                            <option value="<?= e((string)$division['id']); ?>" data-circle-name="<?= e((string)$division['circle_name']); ?>" data-zone-name="<?= e((string)$division['zone_name']); ?>"><?= e($division['office_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Circle
                    <input type="text" id="office-create-division-circle-display" readonly placeholder="Auto from selected division">
                </label>
                <label>Zone
                    <input type="text" id="office-create-division-zone-display" readonly placeholder="Auto from selected division">
                </label>
                <label>User Email
                    <input type="email" name="email_id_subdivision" placeholder="ee_xyz@pwd.gov.bd">
                </label>
            </div>
            <div class="modal-actions">
                <button type="submit">Save</button>
                <button type="button" class="modal-close" data-close="office-create-modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

</div>

<?php require __DIR__ . '/footer.php'; ?>
