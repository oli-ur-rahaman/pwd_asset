<?php
require __DIR__ . '/header.php';
$user = current_user();
$accessOptions = office_user_access_options();
$officeContext = current_office_context($user);
$officeUsers = $officeContext ? array_values(array_filter(get_office_users((int)$officeContext['office_type'], (int)$officeContext['office_id']), static fn(array $officeUser): bool => (int)($officeUser['is_primary_office_user'] ?? 0) !== 1)) : [];
$superadminUsers = is_superadmin() ? get_superadmin_additional_users() : [];
?>
<section class="card">
    <h2>Profile</h2>
    <form method="post" action="index.php" class="grid profile-form">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="update_profile">
        <label>Officer Name
            <input type="text" name="officer_name" value="<?= e((string)($user['officer_name'] ?? '')); ?>" required>
        </label>
        <button type="submit" class="btn-small">Update Name</button>
    </form>
</section>

<section class="card">
    <h2>Change Password</h2>
    <form method="post" action="index.php" class="grid profile-form">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="update_profile">
        <label>New Password
            <input type="password" name="password" required>
        </label>
        <label>Confirm New Password
            <input type="password" name="password_confirm" required>
        </label>
        <button type="submit" class="btn-small">Update Password</button>
    </form>
</section>

<?php if (is_superadmin()): ?>
    <section class="card" id="profile-superadmin-users">
        <div class="hero-row office-manage-users-head">
            <div>
                <h2>Additional Users</h2>
                <p class="hint">These users get superadmin view-only access. Default password is 1234.</p>
            </div>
        </div>
        <?php if (!can_manage_superadmin_scope()): ?>
            <p class="hint">View-only superadmin users can view this list but cannot change it.</p>
        <?php else: ?>
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
                    <?php foreach ($superadminUsers as $index => $managedUser): ?>
                        <tr>
                            <td><?= e((string)($index + 1)); ?></td>
                            <td><input type="text" name="officer_name" value="<?= e((string)($managedUser['officer_name'] ?? '')); ?>" form="superadmin-managed-user-<?= e((string)$managedUser['id']); ?>"></td>
                            <td><input type="email" name="email_id" value="<?= e((string)$managedUser['email_id']); ?>" form="superadmin-managed-user-<?= e((string)$managedUser['id']); ?>" required></td>
                            <td>1234</td>
                            <td><input type="text" value="View Only" readonly></td>
                            <td>
                                <div class="action-row">
                                    <form method="post" action="index.php" class="inline-form" id="superadmin-managed-user-<?= e((string)$managedUser['id']); ?>">
                                        <?= csrf_input(); ?>
                                        <input type="hidden" name="action" value="save_superadmin_additional_user">
                                        <input type="hidden" name="managed_user_id" value="<?= e((string)$managedUser['id']); ?>">
                                        <button type="submit" class="btn-small">Save</button>
                                    </form>
                                    <form method="post" action="index.php" class="inline-form">
                                        <?= csrf_input(); ?>
                                        <input type="hidden" name="action" value="reset_superadmin_additional_user_password">
                                        <input type="hidden" name="managed_user_id" value="<?= e((string)$managedUser['id']); ?>">
                                        <button type="submit" class="btn-small">Reset Password</button>
                                    </form>
                                    <form method="post" action="index.php" class="inline-form">
                                        <?= csrf_input(); ?>
                                        <input type="hidden" name="action" value="toggle_superadmin_additional_user_status">
                                        <input type="hidden" name="managed_user_id" value="<?= e((string)$managedUser['id']); ?>">
                                        <input type="hidden" name="active_status" value="<?= (int)($managedUser['active_status'] ?? 1) === 1 ? '0' : '1'; ?>">
                                        <button type="submit" class="btn-small <?= (int)($managedUser['active_status'] ?? 1) === 1 ? 'btn-danger' : ''; ?>"><?= (int)($managedUser['active_status'] ?? 1) === 1 ? 'Disable' : 'Enable'; ?></button>
                                    </form>
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
                    <td><input type="text" value="View Only" readonly></td>
                    <td>
                        <form method="post" action="index.php" class="inline-form managed-user-create-form" id="__FORM_ID__">
                            <?= csrf_input(); ?>
                            <input type="hidden" name="action" value="save_superadmin_additional_user">
                            <button type="submit" class="btn-small">Save</button>
                        </form>
                    </td>
                </tr>
            </template>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" data-add-managed-user-row="profile-superadmin-users">+ Add Row</button>
            </div>
        <?php endif; ?>
    </section>
<?php elseif ($officeContext): ?>
    <section class="card" id="profile-managed-user-table">
        <div class="hero-row office-manage-users-head">
            <div>
                <h2>Additional Users</h2>
                <p class="hint">Default password for newly added users is 1234.</p>
            </div>
        </div>

        <?php if (!office_allows_user_management((int)$officeContext['office_type'], (int)$officeContext['office_id'])): ?>
            <p class="hint">This office is currently not allowed to manage additional users.</p>
        <?php elseif (!user_can_manage_office_users($user, (int)$officeContext['office_type'], (int)$officeContext['office_id'])): ?>
            <p class="hint">Only the office head can manage additional users for this office.</p>
        <?php else: ?>
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
                        <tr>
                            <td><?= e((string)($index + 1)); ?></td>
                            <td><input type="text" name="officer_name" value="<?= e((string)($officeUser['officer_name'] ?? '')); ?>" form="profile-managed-user-<?= e((string)$officeUser['id']); ?>"></td>
                            <td><input type="email" name="email_id" value="<?= e((string)$officeUser['email_id']); ?>" form="profile-managed-user-<?= e((string)$officeUser['id']); ?>" required></td>
                            <td>1234</td>
                            <td>
                                <select name="office_access_level" form="profile-managed-user-<?= e((string)$officeUser['id']); ?>">
                                    <?php foreach ($accessOptions as $level => $label): ?>
                                        <?php if ($level === 1) continue; ?>
                                        <option value="<?= e((string)$level); ?>" <?= (int)$officeUser['office_access_level'] === $level ? 'selected' : ''; ?>><?= e($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <div class="action-row">
                                    <form method="post" action="index.php" class="inline-form" id="profile-managed-user-<?= e((string)$officeUser['id']); ?>">
                                        <?= csrf_input(); ?>
                                        <input type="hidden" name="action" value="save_additional_office_user">
                                        <input type="hidden" name="office_type" value="<?= e((string)$officeContext['office_type']); ?>">
                                        <input type="hidden" name="office_id" value="<?= e((string)$officeContext['office_id']); ?>">
                                        <input type="hidden" name="managed_user_id" value="<?= e((string)$officeUser['id']); ?>">
                                        <input type="hidden" name="return_page" value="profile">
                                        <button type="submit" class="btn-small">Save</button>
                                    </form>
                                    <form method="post" action="index.php" class="inline-form">
                                        <?= csrf_input(); ?>
                                        <input type="hidden" name="action" value="reset_additional_office_user_password">
                                        <input type="hidden" name="office_type" value="<?= e((string)$officeContext['office_type']); ?>">
                                        <input type="hidden" name="office_id" value="<?= e((string)$officeContext['office_id']); ?>">
                                        <input type="hidden" name="managed_user_id" value="<?= e((string)$officeUser['id']); ?>">
                                        <input type="hidden" name="return_page" value="profile">
                                        <button type="submit" class="btn-small">Reset Password</button>
                                    </form>
                                    <form method="post" action="index.php" class="inline-form">
                                        <?= csrf_input(); ?>
                                        <input type="hidden" name="action" value="toggle_additional_office_user_status">
                                        <input type="hidden" name="office_type" value="<?= e((string)$officeContext['office_type']); ?>">
                                        <input type="hidden" name="office_id" value="<?= e((string)$officeContext['office_id']); ?>">
                                        <input type="hidden" name="managed_user_id" value="<?= e((string)$officeUser['id']); ?>">
                                        <input type="hidden" name="active_status" value="<?= (int)($officeUser['active_status'] ?? 1) === 1 ? '0' : '1'; ?>">
                                        <input type="hidden" name="return_page" value="profile">
                                        <button type="submit" class="btn-small <?= (int)($officeUser['active_status'] ?? 1) === 1 ? 'btn-danger' : ''; ?>"><?= (int)($officeUser['active_status'] ?? 1) === 1 ? 'Disable' : 'Enable'; ?></button>
                                    </form>
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
                        <form method="post" action="index.php" class="inline-form managed-user-create-form" id="__FORM_ID__">
                            <?= csrf_input(); ?>
                            <input type="hidden" name="action" value="save_additional_office_user">
                            <input type="hidden" name="office_type" value="<?= e((string)$officeContext['office_type']); ?>">
                            <input type="hidden" name="office_id" value="<?= e((string)$officeContext['office_id']); ?>">
                            <input type="hidden" name="return_page" value="profile">
                            <button type="submit" class="btn-small">Save</button>
                        </form>
                    </td>
                </tr>
            </template>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" data-add-managed-user-row="profile-managed-user-table">+ Add Row</button>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
