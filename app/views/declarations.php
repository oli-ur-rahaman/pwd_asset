<?php
require __DIR__ . '/header.php';
$canManageSuperadmin = can_manage_superadmin_scope();
$segments = get_asset_segments(true);
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
$sectionLabels = [2 => 'Zone Offices', 3 => 'Circle Offices', 4 => 'Division Offices', 5 => 'Sub-division Offices'];
$sectionKeys = [2 => 'zone', 3 => 'circle', 4 => 'division', 5 => 'subdivision'];
$matchesDeclarationFilters = static function (array $cell) use ($normalizedFilters): bool {
    $status = (string)($cell['status_key'] ?? 'undeclared');
    $sentAge = asset_relative_age_days((string)($cell['last_sent_at'] ?? ''));
    $updatedAge = asset_relative_age_days((string)($cell['last_update_at'] ?? ''));
    if ($normalizedFilters['status'] !== '' && $normalizedFilters['status'] !== $status) {
        return false;
    }
    if ($normalizedFilters['sent_earlier'] !== null && ($sentAge === null || $sentAge < (int)$normalizedFilters['sent_earlier'])) {
        return false;
    }
    if ($normalizedFilters['sent_sooner'] !== null && ($sentAge === null || $sentAge > (int)$normalizedFilters['sent_sooner'])) {
        return false;
    }
    if ($normalizedFilters['updated_earlier'] !== null && ($updatedAge === null || $updatedAge < (int)$normalizedFilters['updated_earlier'])) {
        return false;
    }
    if ($normalizedFilters['updated_sooner'] !== null && ($updatedAge === null || $updatedAge > (int)$normalizedFilters['updated_sooner'])) {
        return false;
    }
    return true;
};
$declarationTables = [];
foreach ($sectionLabels as $officeType => $title) {
    $officeRows = [];
    foreach ($segments as $segment) {
        $segmentRows = get_declarations_for_office_type($officeType, ['segment_id' => (int)$segment['id']]);
        foreach ($segmentRows as $row) {
            $officeId = (int)$row['office_id'];
            if (!isset($officeRows[$officeId])) {
                $officeRows[$officeId] = [
                    'office_id' => $officeId,
                    'office_name' => (string)$row['office_name'],
                    'segments' => [],
                ];
            }
            $officeRows[$officeId]['segments'][(int)$segment['id']] = $row;
        }
    }
    $officeRows = array_values(array_filter($officeRows, static function (array $row) use ($segments, $matchesDeclarationFilters): bool {
        foreach ($segments as $segment) {
            $cell = $row['segments'][(int)$segment['id']] ?? [
                'status_key' => 'undeclared',
                'last_sent_at' => '',
                'last_update_at' => '',
            ];
            if ($matchesDeclarationFilters($cell)) {
                return true;
            }
        }
        return false;
    }));
    usort($officeRows, static fn(array $left, array $right): int => strnatcasecmp((string)$left['office_name'], (string)$right['office_name']));
    $declarationTables[$officeType] = $officeRows;
}
?>
<section class="card">
    <h2>Declarations</h2>
    <?php if (!$canManageSuperadmin): ?>
        <p class="hint">View-only superadmin users can review declaration status here but cannot reset declarations.</p>
    <?php endif; ?>
    <div class="toolbar-row scope-switch-row declaration-toggle-row">
        <?php foreach ($sectionLabels as $officeType => $title): ?>
            <button type="button" class="button-link is-active" data-declaration-toggle="<?= e($sectionKeys[$officeType]); ?>"><?= e(str_replace(' Offices', '', $title)); ?></button>
        <?php endforeach; ?>
    </div>
    <form method="get" action="index.php" class="grid board-filters-grid declaration-filter-grid">
        <input type="hidden" name="page" value="declarations">
        <label>Status
            <select name="status">
                <option value="">All</option>
                <option value="declared" <?= $normalizedFilters['status'] === 'declared' ? 'selected' : ''; ?>>Sent</option>
                <option value="undeclared" <?= $normalizedFilters['status'] === 'undeclared' ? 'selected' : ''; ?>>Unsent</option>
            </select>
        </label>
        <label>Sent Earlier Than Days
            <input type="number" min="0" step="1" name="sent_earlier" value="<?= e($filters['sent_earlier']); ?>">
        </label>
        <label>Sent Within Days
            <input type="number" min="0" step="1" name="sent_sooner" value="<?= e($filters['sent_sooner']); ?>">
        </label>
        <label>Updated Earlier Than Days
            <input type="number" min="0" step="1" name="updated_earlier" value="<?= e($filters['updated_earlier']); ?>">
        </label>
        <label>Updated Within Days
            <input type="number" min="0" step="1" name="updated_sooner" value="<?= e($filters['updated_sooner']); ?>">
        </label>
        <button type="submit">Apply</button>
    </form>
</section>

<?php foreach ($sectionLabels as $officeType => $title): ?>
    <section class="card declaration-section" data-declaration-section="<?= e($sectionKeys[$officeType]); ?>">
        <h3><?= e($title); ?></h3>
        <div class="table-wrap">
            <table class="declaration-status-table">
                <thead>
                    <tr>
                        <th>Office</th>
                        <?php foreach ($segments as $segment): ?>
                            <th><?= e((string)$segment['segment_name']); ?></th>
                        <?php endforeach; ?>
                        <?php if ($canManageSuperadmin): ?><th>Action</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$declarationTables[$officeType]): ?>
                        <tr>
                            <td colspan="<?= count($segments) + ($canManageSuperadmin ? 2 : 1); ?>" class="muted">No offices matched the current filters.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($declarationTables[$officeType] as $row): ?>
                        <tr>
                            <td><?= e($row['office_name']); ?></td>
                            <?php foreach ($segments as $segment): ?>
                                <?php
                                    $cell = $row['segments'][(int)$segment['id']] ?? null;
                                    $isSent = !empty($cell['declared_status']);
                                    $sentLabel = (string)($cell['last_sent_label'] ?? 'Unsent');
                                    $updateLabel = (string)($cell['last_update_label'] ?? '');
                                ?>
                                <td class="declaration-status-cell <?= $isSent ? 'is-sent' : 'is-unsent'; ?>">
                                    <?php if ($isSent): ?>
                                        <span><?= e($sentLabel); ?><?php if ($updateLabel !== '' && $updateLabel !== 'No updates yet'): ?> (<?= e($updateLabel); ?>)<?php endif; ?></span>
                                    <?php else: ?>
                                        <span>Unsent</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                            <?php if ($canManageSuperadmin): ?>
                                <td>
                                    <form method="post" action="index.php" class="inline-form">
                                        <?= csrf_input(); ?>
                                        <input type="hidden" name="action" value="asset_reset_declarations">
                                        <input type="hidden" name="declarations[]" value="<?= e($officeType . ':' . $row['office_id']); ?>">
                                        <input type="hidden" name="office_type" value="<?= e((string)$officeType); ?>">
                                        <input type="hidden" name="office_id" value="<?= e((string)$row['office_id']); ?>">
                                        <input type="hidden" name="reset_all_segments" value="1">
                                        <button type="submit" class="icon-only-button btn-danger-soft" title="Reset" aria-label="Reset">&#x21bb;</button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endforeach; ?>

<?php require __DIR__ . '/footer.php'; ?>
