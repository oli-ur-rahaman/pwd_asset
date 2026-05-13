<?php
require __DIR__ . '/header.php';

$user = current_user();
$info = get_info_row();
$fieldMap = asset_field_map();
$fields = get_asset_fields();
$categories = get_asset_categories();
$subcategories = get_asset_subcategories(null, true);
$subcategoryByCategory = [];
foreach ($subcategories as $subcategory) {
    $subcategoryByCategory[(int)$subcategory['category_id']][] = $subcategory;
}

$filters = [];
$selectedOfficeType = 0;
$selectedOfficeId = 0;
if (is_superadmin()) {
    $selectedZone = (int)request_str('zone_id', '0');
    $selectedCircle = (int)request_str('circle_id', '0');
    $selectedDivision = (int)request_str('division_id', '0');
    if ($selectedDivision > 0) {
        $selectedOfficeType = 4;
        $selectedOfficeId = $selectedDivision;
    } elseif ($selectedCircle > 0) {
        $selectedOfficeType = 3;
        $selectedOfficeId = $selectedCircle;
    } elseif ($selectedZone > 0) {
        $selectedOfficeType = 2;
        $selectedOfficeId = $selectedZone;
    }
    if ($selectedOfficeType > 0) {
        $filters['office_type'] = $selectedOfficeType;
        $filters['office_id'] = $selectedOfficeId;
    }
    $filters['category_id'] = (int)request_str('category_id', '0');
    $filters['subcategory_id'] = (int)request_str('subcategory_id', '0');
    $filters['condition_value'] = request_str('condition_value', '');
    $filters['declared_status'] = request_str('declared_status', '');
}

$groupedAssets = get_assets_grouped_by_category($filters, $user);
$declaration = null;
$officeSummary = null;
if (!is_superadmin()) {
    $ctx = current_office_context($user);
    if ($ctx) {
        $declaration = get_asset_declaration($ctx['office_type'], $ctx['office_id']);
        $officeSummary = get_office_activity_summary($ctx['office_type'], $ctx['office_id']);
    }
}

$editAssetId = (int)request_str('edit_asset', '0');
$editingAsset = $editAssetId > 0 ? get_asset($editAssetId, true) : null;
$editValues = $editingAsset['values'] ?? [];
$review = $_SESSION['asset_import_review'] ?? null;

$zones = db()->query('SELECT id, office_name FROM zones ORDER BY office_name')->fetchAll();
$circles = db()->query('SELECT id, office_name, zone_id FROM circles ORDER BY office_name')->fetchAll();
$divisions = db()->query('SELECT id, office_name, zone_id, circle_id FROM divisions ORDER BY office_name')->fetchAll();
$uiFieldLabels = [];
foreach ($fields as $field) {
    $rawLabel = trim((string)($field['label'] ?? ''));
    $parts = preg_split('/\s*\/\s*/u', $rawLabel);
    $uiFieldLabels[$field['field_key']] = trim((string)($parts[0] ?? $rawLabel));
}
$categoryNameById = [];
foreach ($categories as $category) {
    $categoryNameById[(int)$category['id']] = (string)$category['name'];
}
$importFieldDefs = [];
foreach ($fields as $field) {
    if ((int)$field['is_import_enabled'] !== 1 || (int)$field['active_status'] !== 1) {
        continue;
    }
    $importFieldDefs[] = [
        'field_key' => (string)$field['field_key'],
        'label' => (string)($uiFieldLabels[$field['field_key']] ?? $field['label']),
        'data_type' => (string)$field['data_type'],
        'required' => (int)$field['is_required'] === 1,
        'options' => array_map(
            static fn(array $option): string => (string)$option['option_value'],
            get_asset_field_options((int)$field['id'])
        ),
    ];
}
$downloadFilters = [
    'office_type' => $selectedOfficeType,
    'office_id' => $selectedOfficeId,
    'category_id' => (int)($filters['category_id'] ?? 0),
    'subcategory_id' => (int)($filters['subcategory_id'] ?? 0),
    'condition_value' => (string)($filters['condition_value'] ?? ''),
];
$conditionOptions = isset($fieldMap['condition_value']) ? get_asset_field_options((int)$fieldMap['condition_value']['id']) : [];
?>
<section class="card hero-card">
    <div class="hero-row">
        <div class="hero-copy">
            <h2 class="hero-title"><?= e((string)($info['site_name'] ?? 'PWD Asset Management System')); ?></h2>
            <p class="hero-subtitle">Office: <?= e(current_office_label($user)); ?> | User: <?= e((string)($user['email_id'] ?? '')); ?></p>
        </div>
        <?php if (!is_superadmin()): ?>
            <div class="hero-actions">
                <?php if ($officeSummary): ?>
                    <div class="hero-summary">
                        <div class="hero-summary-item"><strong>Last Sent</strong><br><span class="muted"><?= e($officeSummary['last_sent_label']); ?></span></div>
                        <div class="hero-summary-item"><strong>Last Update</strong><br><span class="muted"><?= e($officeSummary['last_update_label']); ?></span></div>
                    </div>
                <?php endif; ?>
                <form method="post" action="index.php" class="inline-form">
                    <?= csrf_input(); ?>
                    <input type="hidden" name="action" value="asset_declare">
                    <button type="submit" class="hero-declare-button" <?= !empty($declaration['declared_status']) ? 'disabled' : ''; ?>>Declare as Completed</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    <?php if (!empty($declaration['declared_status'])): ?>
        <p class="hint">Declared at: <?= e((string)$declaration['declared_at']); ?></p>
    <?php endif; ?>
</section>

<?php if (is_superadmin()): ?>
<section class="card">
    <h2>Master Filters</h2>
    <form method="get" action="index.php" id="asset-filters" class="grid board-filters-grid">
        <input type="hidden" name="page" value="board">
        <label>Zone
            <select name="zone_id">
                <option value="0">All</option>
                <?php foreach ($zones as $zone): ?>
                    <option value="<?= e((string)$zone['id']); ?>" <?= $selectedZone === (int)$zone['id'] ? 'selected' : ''; ?>><?= e($zone['office_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Circle
            <select name="circle_id">
                <option value="0">All</option>
                <?php foreach ($circles as $circle): ?>
                    <option value="<?= e((string)$circle['id']); ?>" data-zone="<?= e((string)$circle['zone_id']); ?>" <?= $selectedCircle === (int)$circle['id'] ? 'selected' : ''; ?>><?= e($circle['office_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Division
            <select name="division_id">
                <option value="0">All</option>
                <?php foreach ($divisions as $division): ?>
                    <option value="<?= e((string)$division['id']); ?>" data-zone="<?= e((string)$division['zone_id']); ?>" data-circle="<?= e((string)$division['circle_id']); ?>" <?= $selectedDivision === (int)$division['id'] ? 'selected' : ''; ?>><?= e($division['office_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Category
            <select name="category_id">
                <option value="0">All</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e((string)$category['id']); ?>" <?= (int)($filters['category_id'] ?? 0) === (int)$category['id'] ? 'selected' : ''; ?>><?= e($category['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Sub-category
            <select name="subcategory_id">
                <option value="0">All</option>
                <?php foreach ($subcategories as $subcategory): ?>
                    <option value="<?= e((string)$subcategory['id']); ?>" data-category="<?= e((string)$subcategory['category_id']); ?>" <?= (int)($filters['subcategory_id'] ?? 0) === (int)$subcategory['id'] ? 'selected' : ''; ?>><?= e($subcategory['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Condition
            <select name="condition_value">
                <option value="">All</option>
                <option value="যোগ্য" <?= ($filters['condition_value'] ?? '') === 'যোগ্য' ? 'selected' : ''; ?>>যোগ্য</option>
                <option value="অযোগ্য" <?= ($filters['condition_value'] ?? '') === 'অযোগ্য' ? 'selected' : ''; ?>>অযোগ্য</option>
            </select>
        </label>
        <label>Declaration
            <select name="declared_status">
                <option value="">All</option>
                <option value="declared" <?= ($filters['declared_status'] ?? '') === 'declared' ? 'selected' : ''; ?>>Declared</option>
                <option value="undeclared" <?= ($filters['declared_status'] ?? '') === 'undeclared' ? 'selected' : ''; ?>>Undeclared</option>
            </select>
        </label>
        <button type="submit">Apply</button>
        <a href="index.php?page=board" class="icon-only-button" title="Refresh Filters" aria-label="Refresh Filters">&#x21bb;</a>
    </form>
</section>
<?php endif; ?>

<?php if (!is_superadmin()): ?>
<section class="card">
    <div class="toolbar-row">
        <button type="button" data-modal="asset-modal">+Add Asset</button>
        <button type="button" data-modal="import-modal">Bulk Entry</button>
        <a href="asset_template.php" class="button-link">Excel Template</a>
        <form method="post" action="index.php" class="inline-form">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="asset_download_data">
            <button type="submit" class="btn-secondary">Download Data</button>
        </form>
    </div>
</section>
<?php else: ?>
<section class="card">
    <div class="toolbar-row">
        <a href="asset_template.php" class="button-link">Excel Template</a>
        <button type="button" data-modal="superadmin-download-modal" class="btn-secondary">Download Data</button>
    </div>
</section>
<?php endif; ?>

<form method="post" action="index.php" class="asset-delete-form">
    <?= csrf_input(); ?>
    <input type="hidden" name="action" value="asset_bulk_delete">
    <section class="board-grid asset-category-grid">
        <?php foreach ($groupedAssets as $group): ?>
            <?php
                $category = $group['category'];
                $assets = $group['assets'];
            ?>
            <section class="card operational-budget-card">
                <div class="card-head">
                    <h2><?= e($category['name']); ?></h2>
                    <div class="muted"><?= count($assets); ?> asset(s)</div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <?php if (!is_superadmin()): ?><th><input type="checkbox" class="select-all"></th><?php endif; ?>
                                <th>SL No</th>
                                <th>Asset Number</th>
                                <?php if (is_superadmin()): ?><th>Office</th><?php endif; ?>
                                <th>Sub-category</th>
                                <?php foreach ($fields as $field): ?>
                                    <?php if ((int)$field['is_displayed'] === 1 && (int)$field['active_status'] === 1): ?>
                                        <th><?= e((string)($uiFieldLabels[$field['field_key']] ?? $field['label'])); ?></th>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$assets): ?>
                                <tr><td colspan="<?= is_superadmin() ? 6 + count($fields) : 5 + count($fields); ?>" class="muted">No assets found.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($assets as $index => $asset): ?>
                                <tr>
                                    <?php if (!is_superadmin()): ?>
                                        <td><input type="checkbox" name="asset_ids[]" value="<?= e((string)$asset['id']); ?>"></td>
                                    <?php endif; ?>
                                    <td><?= e((string)($index + 1)); ?></td>
                                    <td><?= e($asset['asset_number']); ?></td>
                                    <?php if (is_superadmin()): ?><td><?= e($asset['office_type_label'] . ' - ' . $asset['office_name']); ?></td><?php endif; ?>
                                    <td><?= e($asset['subcategory_name']); ?></td>
                                    <?php foreach ($fields as $field): ?>
                                        <?php if ((int)$field['is_displayed'] === 1 && (int)$field['active_status'] === 1): ?>
                                            <td><?= e((string)($asset['values'][$field['field_key']] ?? '')); ?></td>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <td>
                                        <?php if (!is_superadmin()): ?>
                                            <a href="index.php?page=board&edit_asset=<?= e((string)$asset['id']); ?>" class="btn-small">Edit</a>
                                        <?php else: ?>
                                            <span class="muted">View</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endforeach; ?>
    </section>
    <?php if (!is_superadmin()): ?>
        <div class="bulk-actions">
            <button type="submit" class="btn-danger">Soft Delete Selected</button>
        </div>
    <?php endif; ?>
</form>

<?php if (!is_superadmin()): ?>
<div class="modal-backdrop<?= $editingAsset ? ' open' : ''; ?>" id="asset-modal" aria-hidden="<?= $editingAsset ? 'false' : 'true'; ?>">
    <div class="modal-card asset-entry-modal" role="dialog" aria-modal="true" aria-labelledby="asset-modal-title">
        <h3 id="asset-modal-title"><?= $editingAsset ? 'Edit Asset' : 'Add Asset'; ?></h3>
        <form method="post" action="index.php" class="grid">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="asset_save">
            <input type="hidden" name="asset_id" value="<?= e((string)($editingAsset['id'] ?? '0')); ?>">
            <label>Category *
                <select name="category_id" id="asset-category-select" required>
                    <option value="">Select</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e((string)$category['id']); ?>" <?= (int)($editingAsset['category_id'] ?? 0) === (int)$category['id'] ? 'selected' : ''; ?>><?= e($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Sub-category *
                <select name="subcategory_id" id="asset-subcategory-select" required>
                    <option value="">Select</option>
                    <?php foreach ($subcategories as $subcategory): ?>
                        <option value="<?= e((string)$subcategory['id']); ?>" data-category="<?= e((string)$subcategory['category_id']); ?>" <?= (int)($editingAsset['subcategory_id'] ?? 0) === (int)$subcategory['id'] ? 'selected' : ''; ?>><?= e($subcategory['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php foreach ($fields as $field): ?>
                <?php if ((int)$field['active_status'] !== 1) { continue; } ?>
                <label><?= e((string)($uiFieldLabels[$field['field_key']] ?? $field['label'])); ?><?= (int)$field['is_required'] === 1 ? ' *' : ''; ?>
                    <?php $value = (string)($editValues[$field['field_key']] ?? ''); ?>
                    <?php if ($field['data_type'] === 'date'): ?>
                        <input type="date" name="fields[<?= e($field['field_key']); ?>]" value="<?= e($value); ?>" <?= (int)$field['is_required'] === 1 ? 'required' : ''; ?>>
                    <?php elseif ($field['data_type'] === 'number'): ?>
                        <input type="number" step="0.01" name="fields[<?= e($field['field_key']); ?>]" value="<?= e($value); ?>" <?= (int)$field['is_required'] === 1 ? 'required' : ''; ?>>
                    <?php elseif ($field['data_type'] === 'dropdown'): ?>
                        <select name="fields[<?= e($field['field_key']); ?>]" <?= (int)$field['is_required'] === 1 ? 'required' : ''; ?>>
                            <option value="">Select</option>
                            <?php foreach (get_asset_field_options((int)$field['id']) as $option): ?>
                                <option value="<?= e($option['option_value']); ?>" <?= $value === (string)$option['option_value'] ? 'selected' : ''; ?>><?= e($option['option_label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($field['data_type'] === 'yes_no'): ?>
                        <select name="fields[<?= e($field['field_key']); ?>]" <?= (int)$field['is_required'] === 1 ? 'required' : ''; ?>>
                            <option value="">Select</option>
                            <option value="Yes" <?= $value === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                            <option value="No" <?= $value === 'No' ? 'selected' : ''; ?>>No</option>
                        </select>
                    <?php elseif ($field['field_key'] === 'remarks'): ?>
                        <textarea name="fields[<?= e($field['field_key']); ?>]" rows="3"><?= e($value); ?></textarea>
                    <?php else: ?>
                        <input type="text" name="fields[<?= e($field['field_key']); ?>]" value="<?= e($value); ?>" <?= (int)$field['is_required'] === 1 ? 'required' : ''; ?>>
                    <?php endif; ?>
                </label>
            <?php endforeach; ?>
            <div class="modal-actions">
                <button type="submit">Save</button>
                <button type="button" class="modal-close" data-close="asset-modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="import-modal" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="import-modal-title">
        <h3 id="import-modal-title">Bulk Entry</h3>
        <form method="post" action="index.php" enctype="multipart/form-data" class="grid">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="asset_import_upload">
            <label>Excel File
                <input type="file" name="asset_file" accept=".xlsx,.xls" required>
            </label>
            <div class="modal-actions">
                <button type="submit">Audit File</button>
                <button type="button" class="modal-close" data-close="import-modal">Cancel</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($review && !empty($review['rows'])): ?>
<div class="modal-backdrop open" id="import-review-modal" aria-hidden="false">
    <div class="modal-card modal-wide import-review-modal-card" role="dialog" aria-modal="true" aria-labelledby="import-review-title">
        <h3 id="import-review-title">Import Audit Review</h3>
        <script type="application/json" id="import-review-meta"><?= json_encode([
            'categories' => array_map(static fn(array $category): array => [
                'id' => (int)$category['id'],
                'name' => (string)$category['name'],
            ], get_asset_categories()),
            'subcategories' => array_map(static fn(array $subcategory): array => [
                'id' => (int)$subcategory['id'],
                'category_id' => (int)$subcategory['category_id'],
                'name' => (string)$subcategory['name'],
            ], get_asset_subcategories(null, true)),
            'fields' => $importFieldDefs,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
        <form method="post" action="index.php" class="grid">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="asset_import_save">
            <div class="modal-actions">
                <button type="button" id="import-review-add-row">+Add Row</button>
                <button type="submit">Save Validated Rows</button>
            </div>
            <p class="import-review-summary" id="import-review-summary">Number of Rows need attention - 0</p>
            <div class="table-wrap">
                <table class="audit-table">
                    <thead>
                        <tr>
                            <th>Row</th>
                            <th>Category</th>
                            <th>Sub-category</th>
                            <?php foreach ($fields as $field): ?>
                                <?php if ((int)$field['is_import_enabled'] === 1 && (int)$field['active_status'] === 1): ?>
                                    <th><?= e((string)($uiFieldLabels[$field['field_key']] ?? $field['label'])); ?></th>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <th>Audit</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="import-review-body">
                        <?php foreach ($review['rows'] as $rowIndex => $row): ?>
                            <tr class="review-row <?= !empty($row['errors']) ? 'has-errors' : 'is-valid'; ?>">
                                <td>
                                    <?= e((string)$row['row_number']); ?>
                                    <input type="hidden" name="rows[<?= $rowIndex; ?>][row_number]" value="<?= e((string)$row['row_number']); ?>">
                                </td>
                                <td class="<?= !empty($row['errors']['category_id']) ? 'cell-error' : 'cell-valid'; ?>">
                                    <select class="review-input" data-review-role="category" name="rows[<?= $rowIndex; ?>][category]">
                                        <option value="">Select</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= e($category['name']); ?>" <?= strcasecmp((string)$row['category'], (string)$category['name']) === 0 ? 'selected' : ''; ?>><?= e($category['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="<?= !empty($row['errors']['subcategory_id']) ? 'cell-error' : 'cell-valid'; ?>">
                                    <select class="review-input" data-review-role="subcategory" name="rows[<?= $rowIndex; ?>][subcategory]">
                                        <option value="">Select</option>
                                        <?php foreach ($subcategories as $subcategory): ?>
                                            <option value="<?= e($subcategory['name']); ?>" data-category-name="<?= e((string)($categoryNameById[(int)$subcategory['category_id']] ?? '')); ?>" data-category-id="<?= e((string)$subcategory['category_id']); ?>" <?= strcasecmp((string)$row['subcategory'], (string)$subcategory['name']) === 0 ? 'selected' : ''; ?>><?= e($subcategory['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <?php foreach ($fields as $field): ?>
                                    <?php if ((int)$field['is_import_enabled'] === 1 && (int)$field['active_status'] === 1): ?>
                                        <?php $fieldError = $row['errors'][$field['field_key']] ?? null; ?>
                                        <td class="<?= $fieldError ? 'cell-error' : 'cell-valid'; ?>">
                                            <?php $fieldValue = (string)($row['fields'][$field['field_key']] ?? ''); ?>
                                            <?php if (in_array($field['data_type'], ['dropdown', 'yes_no'], true)): ?>
                                                <select
                                                    class="review-input"
                                                    data-review-role="field"
                                                    data-field-key="<?= e($field['field_key']); ?>"
                                                    data-field-type="<?= e($field['data_type']); ?>"
                                                    data-required="<?= (int)$field['is_required']; ?>"
                                                    name="rows[<?= $rowIndex; ?>][fields][<?= e($field['field_key']); ?>]"
                                                >
                                                    <option value="">Select</option>
                                                    <?php
                                                        $options = get_asset_field_options((int)$field['id']);
                                                        if ($field['data_type'] === 'yes_no' && !$options) {
                                                            $options = [
                                                                ['option_value' => 'Yes', 'option_label' => 'Yes'],
                                                                ['option_value' => 'No', 'option_label' => 'No'],
                                                            ];
                                                        }
                                                    ?>
                                                    <?php foreach ($options as $option): ?>
                                                        <option value="<?= e((string)$option['option_value']); ?>" <?= strcasecmp($fieldValue, (string)$option['option_value']) === 0 ? 'selected' : ''; ?>><?= e((string)$option['option_label']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php else: ?>
                                                <input
                                                    type="<?= $field['data_type'] === 'number' ? 'number' : ($field['data_type'] === 'date' ? 'date' : 'text'); ?>"
                                                    <?= $field['data_type'] === 'number' ? 'step="0.01"' : ''; ?>
                                                    class="review-input"
                                                    data-review-role="field"
                                                    data-field-key="<?= e($field['field_key']); ?>"
                                                    data-field-type="<?= e($field['data_type']); ?>"
                                                    data-required="<?= (int)$field['is_required']; ?>"
                                                    name="rows[<?= $rowIndex; ?>][fields][<?= e($field['field_key']); ?>]"
                                                    value="<?= e($fieldValue); ?>">
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <td class="audit-cell">
                                    <?php if (!empty($row['errors'])): ?>
                                        <?php foreach ($row['errors'] as $message): ?>
                                            <div class="error-text"><?= e($message); ?></div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="success-text">OK</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="icon-only-button icon-delete-button review-delete-row" title="Delete Row" aria-label="Delete Row">&#x1f5d1;</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
        <form method="post" action="index.php" class="inline-form">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="asset_import_cancel">
            <button type="submit" class="btn-small">Cancel Review</button>
        </form>
</div>
</div>
<?php endif; ?>

<?php if (is_superadmin()): ?>
<div class="modal-backdrop" id="superadmin-download-modal" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="superadmin-download-title">
        <h3 id="superadmin-download-title">Download Asset Data</h3>
        <?php
            $downloadScope = 'zone';
            if ($downloadFilters['office_type'] === 3) {
                $downloadScope = 'circle';
            } elseif ($downloadFilters['office_type'] === 4) {
                $downloadScope = 'division';
            }
        ?>
        <form method="post" action="index.php" class="grid" id="superadmin-download-form">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="asset_download_data">
            <input type="hidden" name="office_scope" id="download-office-scope" value="<?= e($downloadScope); ?>">
            <div class="download-modal-scope-row">
                <div class="segmented-control" id="download-scope-toggle" role="tablist" aria-label="Office Level">
                    <button type="button" class="segment<?= $downloadScope === 'zone' ? ' is-active' : ''; ?>" data-download-scope="zone">Zone</button>
                    <button type="button" class="segment<?= $downloadScope === 'circle' ? ' is-active' : ''; ?>" data-download-scope="circle">Circle</button>
                    <button type="button" class="segment<?= $downloadScope === 'division' ? ' is-active' : ''; ?>" data-download-scope="division">Division</button>
                </div>
                <button type="button" class="icon-only-button" id="download-reset-filters" title="Refresh Filters" aria-label="Refresh Filters">&#x21bb;</button>
            </div>
            <div class="download-modal-row">
                <label data-download-level="zone">Zone
                    <select name="zone_id" id="download-zone-select">
                        <option value="0">All</option>
                        <?php foreach ($zones as $zone): ?>
                            <option value="<?= e((string)$zone['id']); ?>" <?= $selectedZone === (int)$zone['id'] ? 'selected' : ''; ?>><?= e($zone['office_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label data-download-level="circle">Circle
                    <select name="circle_id" id="download-circle-select">
                        <option value="0">All</option>
                        <?php foreach ($circles as $circle): ?>
                            <option value="<?= e((string)$circle['id']); ?>" data-zone="<?= e((string)$circle['zone_id']); ?>" <?= $selectedCircle === (int)$circle['id'] ? 'selected' : ''; ?>><?= e($circle['office_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label data-download-level="division">Division
                    <select name="division_id" id="download-division-select">
                        <option value="0">All</option>
                        <?php foreach ($divisions as $division): ?>
                            <option value="<?= e((string)$division['id']); ?>" data-zone="<?= e((string)$division['zone_id']); ?>" data-circle="<?= e((string)$division['circle_id']); ?>" <?= $selectedDivision === (int)$division['id'] ? 'selected' : ''; ?>><?= e($division['office_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="download-modal-row">
                <label>Category
                    <select name="category_id" id="download-category-select">
                        <option value="0">All</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= e((string)$category['id']); ?>" <?= $downloadFilters['category_id'] === (int)$category['id'] ? 'selected' : ''; ?>><?= e($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Sub-category
                    <select name="subcategory_id" id="download-subcategory-select">
                        <option value="0">All</option>
                        <?php foreach ($subcategories as $subcategory): ?>
                            <option value="<?= e((string)$subcategory['id']); ?>" data-category="<?= e((string)$subcategory['category_id']); ?>" <?= $downloadFilters['subcategory_id'] === (int)$subcategory['id'] ? 'selected' : ''; ?>><?= e($subcategory['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="download-modal-row download-modal-row-single">
                <label>Condition
                    <select name="condition_value" id="download-condition-select">
                        <option value="">All</option>
                        <?php foreach ($conditionOptions as $option): ?>
                            <option value="<?= e((string)$option['option_value']); ?>" <?= $downloadFilters['condition_value'] === (string)$option['option_value'] ? 'selected' : ''; ?>><?= e((string)$option['option_label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn-secondary">Download</button>
                <button type="button" class="modal-close" data-close="superadmin-download-modal">Cancel</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
