<?php
require __DIR__ . '/header.php';
$fy_list = get_fy_list();
$zones = db()->query('SELECT id, office_name FROM zones ORDER BY office_name')->fetchAll();
$circles = db()->query('SELECT id, office_name FROM circles ORDER BY office_name')->fetchAll();
$divisions = db()->query('SELECT id, office_name FROM divisions ORDER BY office_name')->fetchAll();
?>
<section class="card">
    <h2>Fiscal Years</h2>
    <form method="post" action="index.php" class="inline-form">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="add_fy">
        <label>Fiscal Year
            <input type="text" name="fiscal_years" placeholder="2025-26" required>
        </label>
        <label>
            <input type="checkbox" name="make_current" value="1">
            Set as current
        </label>
        <button type="submit">Add</button>
    </form>
    <form method="post" action="index.php" class="inline-form">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="set_current_fy">
        <label>Current FY
            <select name="fy_id">
                <?php foreach ($fy_list as $fy): ?>
                    <option value="<?= e((string)$fy['id']); ?>" <?= (int)$fy['now_flag'] === 1 ? 'selected' : ''; ?>>
                        <?= e($fy['fiscal_years']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Update</button>
    </form>
</section>

<section class="card">
    <h2>Create User</h2>
    <form method="post" action="index.php" class="grid">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="create_user">
        <label>Email
            <input type="email" name="email_id" required>
        </label>
        <label>Officer Name
            <input type="text" name="officer_name">
        </label>
        <label>Password
            <input type="password" name="password" required>
        </label>
        <label>Office Type
            <select name="office_type">
                <option value="1">Chief Engineer</option>
                <option value="2">Zone</option>
                <option value="3">Circle</option>
                <option value="4">Division</option>
            </select>
        </label>
        <label>Office Role
            <select name="office_role">
                <option value="1">User</option>
                <option value="2">Admin</option>
                <option value="3">Superadmin</option>
            </select>
        </label>
        <label>Zone
            <select name="zone_id">
                <option value="0">None</option>
                <?php foreach ($zones as $zone): ?>
                    <option value="<?= e((string)$zone['id']); ?>"><?= e($zone['office_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Circle
            <select name="circle_id">
                <option value="0">None</option>
                <?php foreach ($circles as $circle): ?>
                    <option value="<?= e((string)$circle['id']); ?>"><?= e($circle['office_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Division
            <select name="division_id">
                <option value="0">None</option>
                <?php foreach ($divisions as $division): ?>
                    <option value="<?= e((string)$division['id']); ?>"><?= e($division['office_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Create User</button>
    </form>
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
                <option value="users">Users</option>
            </select>
        </label>
        <label>CSV File
            <input type="file" name="csv_file" accept=".csv" required>
        </label>
        <button type="submit">Import CSV</button>
    </form>
    <p class="hint">CSV headers must match: zones (office_name, office_address, office_type), circles (office_name, office_address, office_type, zone_id), divisions (office_name, office_address, office_type, zone_id, circle_id, field_office), users (email_id, officer_name, password, office_type, office_role, zone_id, circle_id, division_id).</p>
</section>
<?php require __DIR__ . '/footer.php'; ?>
