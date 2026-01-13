<?php
require __DIR__ . '/header.php';

$users = db()->query('SELECT * FROM users ORDER BY id ASC')->fetchAll();
$zones = db()->query('SELECT id, office_name FROM zones ORDER BY office_name')->fetchAll();
$circles = db()->query('SELECT id, office_name, zone_id FROM circles ORDER BY office_name')->fetchAll();
$divisions = db()->query('SELECT id, office_name, zone_id, circle_id FROM divisions ORDER BY office_name')->fetchAll();

$zone_by_id = [];
foreach ($zones as $zone) {
    $zone_by_id[$zone['id']] = $zone['office_name'];
}
$circle_by_id = [];
foreach ($circles as $circle) {
    $circle_by_id[$circle['id']] = $circle;
}
$division_by_id = [];
foreach ($divisions as $division) {
    $division_by_id[$division['id']] = $division;
}

function user_office_name(array $user, array $zone_by_id, array $circle_by_id, array $division_by_id): string
{
    $role = (int)($user['office_role'] ?? 1);
    $type = (int)($user['office_type'] ?? 4);
    if ($role === 2 || $role === 3) {
        return "Chief Engineer's Office";
    }
    if ($type === 1) {
        return "Chief Engineer's Office";
    }
    if ($type === 2) {
        return $zone_by_id[$user['zone_id']] ?? '-';
    }
    if ($type === 3) {
        return $circle_by_id[$user['circle_id']]['office_name'] ?? '-';
    }
    return $division_by_id[$user['division_id']]['office_name'] ?? '-';
}

$role_filter = request_str('role', 'all');
$zone_filter = request_str('zone_id', 'all');
$circle_filter = request_str('circle_id', 'all');
$division_filter = request_str('division_id', 'all');

$users = array_values(array_filter($users, function ($u) use ($role_filter, $zone_filter, $circle_filter, $division_filter) {
    if ($role_filter !== 'all' && (int)$u['office_role'] !== (int)$role_filter) {
        return false;
    }
    if ($zone_filter !== 'all' && (int)($u['zone_id'] ?? 0) !== (int)$zone_filter) {
        return false;
    }
    if ($circle_filter !== 'all' && (int)($u['circle_id'] ?? 0) !== (int)$circle_filter) {
        return false;
    }
    if ($division_filter !== 'all' && (int)($u['division_id'] ?? 0) !== (int)$division_filter) {
        return false;
    }
    return true;
}));
?>
<section class="card">
    <h2>Users</h2>
    <?php if ($msg = flash('success')): ?>
        <div class="alert success"><?= e($msg); ?></div>
    <?php endif; ?>
    <?php if ($msg = flash('error')): ?>
        <div class="alert error"><?= e($msg); ?></div>
    <?php endif; ?>
    <form method="get" action="index.php" id="users-filters" class="grid board-filters-grid">
        <input type="hidden" name="page" value="users">
        <label>User Role
            <select name="role">
                <option value="all">All</option>
                <option value="1" <?= $role_filter === '1' ? 'selected' : ''; ?>>Normal</option>
                <option value="2" <?= $role_filter === '2' ? 'selected' : ''; ?>>Admin</option>
                <option value="3" <?= $role_filter === '3' ? 'selected' : ''; ?>>Superadmin</option>
            </select>
        </label>
        <label>Zone
            <select name="zone_id">
                <option value="all">All</option>
                <?php foreach ($zones as $zone): ?>
                    <option value="<?= e((string)$zone['id']); ?>" <?= $zone_filter === (string)$zone['id'] ? 'selected' : ''; ?>>
                        <?= e($zone['office_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Circle
            <select name="circle_id">
                <option value="all">All</option>
                <?php foreach ($circles as $circle): ?>
                    <option value="<?= e((string)$circle['id']); ?>" <?= $circle_filter === (string)$circle['id'] ? 'selected' : ''; ?>>
                        <?= e($circle['office_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Division
            <select name="division_id">
                <option value="all">All</option>
                <?php foreach ($divisions as $division): ?>
                    <option value="<?= e((string)$division['id']); ?>" <?= $division_filter === (string)$division['id'] ? 'selected' : ''; ?>>
                        <?= e($division['office_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>SL</th>
                    <th>Email ID</th>
                    <th>Office Name</th>
                    <th>Officer Name</th>
                    <th>New Password</th>
                    <th>Edit User</th>
                    <th>Reset Pass</th>
                </tr>
            </thead>
            <tbody>
                <?php $sl = 1; ?>
                <?php foreach ($users as $user_row): ?>
                    <tr>
                        <td><?= $sl++; ?></td>
                        <td><?= e($user_row['email_id']); ?></td>
                        <td><?= e(user_office_name($user_row, $zone_by_id, $circle_by_id, $division_by_id)); ?></td>
                        <td><?= e((string)($user_row['officer_name'] ?? '')); ?></td>
                        <td>
                            <form method="post" action="index.php" class="inline-form">
                                <?= csrf_input(); ?>
                                <input type="hidden" name="action" value="reset_user_password">
                                <input type="hidden" name="user_id" value="<?= e((string)$user_row['id']); ?>">
                                <input type="password" name="new_password" class="small-input" placeholder="New password" required>
                        </td>
                        <td>
                            <button type="button" class="btn-small" data-modal="edit-user-<?= e((string)$user_row['id']); ?>">Edit User</button>
                        </td>
                        <td>
                                <button type="submit" class="btn-small">Reset Pass</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php foreach ($users as $user_row): ?>
    <?php
        $office_name = user_office_name($user_row, $zone_by_id, $circle_by_id, $division_by_id);
        $zone_id = (int)($user_row['zone_id'] ?? 0);
        $circle_id = (int)($user_row['circle_id'] ?? 0);
        $division_id = (int)($user_row['division_id'] ?? 0);
    ?>
    <div class="modal-backdrop" id="edit-user-<?= e((string)$user_row['id']); ?>" aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="edit-user-title-<?= e((string)$user_row['id']); ?>">
            <h3 id="edit-user-title-<?= e((string)$user_row['id']); ?>">Edit User</h3>
            <form method="post" action="index.php" class="grid">
                <?= csrf_input(); ?>
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" value="<?= e((string)$user_row['id']); ?>">
                <label>Email ID
                    <input type="email" name="email_id" value="<?= e($user_row['email_id']); ?>" required>
                </label>
                <label>Officer Name
                    <input type="text" name="officer_name" value="<?= e((string)($user_row['officer_name'] ?? '')); ?>">
                </label>
                <label>User Role
                    <select name="office_role">
                        <option value="1" <?= (int)$user_row['office_role'] === 1 ? 'selected' : ''; ?>>Normal</option>
                        <option value="2" <?= (int)$user_row['office_role'] === 2 ? 'selected' : ''; ?>>Admin</option>
                        <option value="3" <?= (int)$user_row['office_role'] === 3 ? 'selected' : ''; ?>>Superadmin</option>
                    </select>
                </label>
                <label>Office Name
                    <input type="text" value="<?= e($office_name); ?>" readonly>
                </label>
                <label>Zone Name
                    <select name="zone_id" class="zone-select">
                        <option value="0">-</option>
                        <?php foreach ($zones as $zone): ?>
                            <option value="<?= e((string)$zone['id']); ?>" <?= $zone_id === (int)$zone['id'] ? 'selected' : ''; ?>>
                                <?= e($zone['office_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Circle Name
                    <select name="circle_id" class="circle-select">
                        <option value="0">-</option>
                        <?php foreach ($circles as $circle): ?>
                            <option value="<?= e((string)$circle['id']); ?>" data-zone="<?= e((string)$circle['zone_id']); ?>" <?= $circle_id === (int)$circle['id'] ? 'selected' : ''; ?>>
                                <?= e($circle['office_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Division Name
                    <select name="division_id" class="division-select">
                        <option value="0">-</option>
                        <?php foreach ($divisions as $division): ?>
                            <option value="<?= e((string)$division['id']); ?>" data-zone="<?= e((string)($division['zone_id'] ?? '')); ?>" data-circle="<?= e((string)($division['circle_id'] ?? '')); ?>" <?= $division_id === (int)$division['id'] ? 'selected' : ''; ?>>
                                <?= e($division['office_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="modal-actions">
                    <button type="submit">Save</button>
                    <button type="button" class="modal-close" data-close="edit-user-<?= e((string)$user_row['id']); ?>">Cancel</button>
                </div>
            </form>
        </div>
    </div>
<?php endforeach; ?>
<?php require __DIR__ . '/footer.php'; ?>
