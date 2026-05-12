<?php
require __DIR__ . '/header.php';
$declarationTables = get_declaration_status_tables();
?>
<section class="card">
    <h2>Declarations / অফিস ঘোষণা</h2>
    <p class="hint">Reset declaration status individually or in bulk.</p>
</section>

<?php foreach ([2 => 'Zone Offices', 3 => 'Circle Offices', 4 => 'Division Offices'] as $type => $title): ?>
    <section class="card">
        <h3><?= e($title); ?></h3>
        <form method="post" action="index.php">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="asset_reset_declarations">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="select-all"></th>
                            <th>Office</th>
                            <th>Officer Name</th>
                            <th>Status</th>
                            <th>Declared At</th>
                            <th>Reset</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($declarationTables[$type] as $row): ?>
                            <tr>
                                <td><input type="checkbox" name="declarations[]" value="<?= e($type . ':' . $row['office_id']); ?>"></td>
                                <td><?= e($row['office_name']); ?></td>
                                <td><?= e((string)($row['declared_officer_name'] ?? '')); ?></td>
                                <td><?= !empty($row['declared_status']) ? 'Declared' : 'Undeclared'; ?></td>
                                <td><?= e((string)($row['declared_at'] ?? '')); ?></td>
                                <td>
                                    <button type="submit" name="declarations[]" value="<?= e($type . ':' . $row['office_id']); ?>" class="btn-small">Reset</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn-small">Reset Selected</button>
        </form>
    </section>
<?php endforeach; ?>

<?php require __DIR__ . '/footer.php'; ?>
