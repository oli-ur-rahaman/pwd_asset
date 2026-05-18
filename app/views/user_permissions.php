<?php
require __DIR__ . '/header.php';
$tables = get_office_user_management_tables();
?>
<section class="card">
    <h2>User Permissions</h2>
    <p class="hint">Allow or block offices from creating and managing additional users in bulk.</p>
</section>

<?php foreach ([2 => 'Zone Offices', 3 => 'Circle Offices', 4 => 'Division Offices', 5 => 'Sub-division Offices'] as $type => $title): ?>
    <section class="card">
        <h3><?= e($title); ?></h3>
        <form method="post" action="index.php">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="bulk_update_office_user_management_permissions">
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th><input type="checkbox" class="select-all"></th>
                        <th>Office</th>
                        <th>Office Head</th>
                        <th>ID</th>
                        <th>Permission</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$tables[$type]): ?>
                        <tr><td colspan="5" class="muted">No offices found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($tables[$type] as $row): ?>
                        <tr>
                            <td><input type="checkbox" name="offices[]" value="<?= e($type . ':' . $row['office_id']); ?>"></td>
                            <td><?= e($row['office_name']); ?></td>
                            <td><?= e($row['officer_name']); ?></td>
                            <td><?= e($row['email_id']); ?></td>
                            <td><?= (int)$row['allow_user_management'] === 1 ? 'Allowed' : 'Blocked'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="bulk-actions">
                <button type="submit" name="allowed_status" value="1" class="btn-small">Allow Selected</button>
                <button type="submit" name="allowed_status" value="0" class="btn-small btn-danger">Block Selected</button>
            </div>
        </form>
    </section>
<?php endforeach; ?>

<?php require __DIR__ . '/footer.php'; ?>
