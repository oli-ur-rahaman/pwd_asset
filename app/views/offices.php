<?php
require __DIR__ . '/header.php';
$overview = get_offices_overview();
$zones = $overview['zones'];
$circles = $overview['circles'];
$divisions = $overview['divisions'];
?>
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
                <option value="users">Users</option>
            </select>
        </label>
        <label>CSV File
            <input type="file" name="csv_file" accept=".csv" required>
        </label>
        <button type="submit">Import CSV</button>
    </form>
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
            <div class="modal-actions">
                <button type="submit">Save</button>
                <button type="button" class="modal-close" data-close="office-create-modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
