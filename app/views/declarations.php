<?php
require __DIR__ . '/header.php';

$filters = [
    'status' => request_str('status', ''),
    'sent_earlier' => request_str('sent_earlier', ''),
    'sent_sooner' => request_str('sent_sooner', ''),
    'updated_earlier' => request_str('updated_earlier', ''),
    'updated_sooner' => request_str('updated_sooner', ''),
];

$normalizedFilters = [
    'status' => in_array($filters['status'], ['declared', 'undeclared'], true) ? $filters['status'] : '',
    'sent_earlier' => ctype_digit($filters['sent_earlier']) ? (int)$filters['sent_earlier'] : null,
    'sent_sooner' => ctype_digit($filters['sent_sooner']) ? (int)$filters['sent_sooner'] : null,
    'updated_earlier' => ctype_digit($filters['updated_earlier']) ? (int)$filters['updated_earlier'] : null,
    'updated_sooner' => ctype_digit($filters['updated_sooner']) ? (int)$filters['updated_sooner'] : null,
];

$declarationTables = get_declaration_status_tables($normalizedFilters);
?>
<section class="card">
    <h2>Declarations</h2>
    <p class="hint">Reset declaration status individually or in bulk.</p>
    <form method="get" action="index.php" class="grid board-filters-grid">
        <input type="hidden" name="page" value="declarations">
        <label>Status
            <select name="status">
                <option value="">All</option>
                <option value="declared" <?= $normalizedFilters['status'] === 'declared' ? 'selected' : ''; ?>>Sent</option>
                <option value="undeclared" <?= $normalizedFilters['status'] === 'undeclared' ? 'selected' : ''; ?>>Unsent</option>
            </select>
        </label>
        <label>Sent (days ago or earlier)
            <input type="number" min="0" step="1" name="sent_earlier" value="<?= e($filters['sent_earlier']); ?>">
        </label>
        <label>Sent (days ago or sooner)
            <input type="number" min="0" step="1" name="sent_sooner" value="<?= e($filters['sent_sooner']); ?>">
        </label>
        <label>Updated (days ago or earlier)
            <input type="number" min="0" step="1" name="updated_earlier" value="<?= e($filters['updated_earlier']); ?>">
        </label>
        <label>Updated (days ago or sooner)
            <input type="number" min="0" step="1" name="updated_sooner" value="<?= e($filters['updated_sooner']); ?>">
        </label>
        <button type="submit">Apply</button>
    </form>
</section>

<?php foreach ([2 => 'Zone Offices', 3 => 'Circle Offices', 4 => 'Division Offices', 5 => 'Sub-division Offices'] as $type => $title): ?>
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
                            <th>Last Sent</th>
                            <th>Last Update</th>
                            <th>Reset</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$declarationTables[$type]): ?>
                            <tr>
                                <td colspan="7" class="muted">No offices matched the current filters.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($declarationTables[$type] as $row): ?>
                            <tr>
                                <td><input type="checkbox" name="declarations[]" value="<?= e($type . ':' . $row['office_id']); ?>"></td>
                                <td><?= e($row['office_name']); ?></td>
                                <td><?= e((string)($row['declared_officer_name'] ?? '')); ?></td>
                                <td><?= !empty($row['declared_status']) ? 'Sent' : 'Unsent'; ?></td>
                                <td><?= e((string)($row['last_sent_label'] ?? 'Never')); ?></td>
                                <td><?= e((string)($row['last_update_label'] ?? 'No updates yet')); ?></td>
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
