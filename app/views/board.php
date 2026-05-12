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
if (!is_superadmin()) {
    $ctx = current_office_context($user);
    if ($ctx) {
        $declaration = get_asset_declaration($ctx['office_type'], $ctx['office_id']);
    }
}

$editAssetId = (int)request_str('edit_asset', '0');
$editingAsset = $editAssetId > 0 ? get_asset($editAssetId, true) : null;
$editValues = $editingAsset['values'] ?? [];
$review = $_SESSION['asset_import_review'] ?? null;

$zones = db()->query('SELECT id, office_name FROM zones ORDER BY office_name')->fetchAll();
$circles = db()->query('SELECT id, office_name, zone_id FROM circles ORDER BY office_name')->fetchAll();
$divisions = db()->query('SELECT id, office_name, zone_id, circle_id FROM divisions ORDER BY office_name')->fetchAll();
?>
<section class="card hero-card">
    <div class="hero-row">
        <div>
            <h2><?= e((string)($info['site_name'] ?? 'PWD Asset Management System')); ?></h2>
            <p class="muted">Office: <?= e(current_office_label($user)); ?> | User: <?= e((string)($user['email_id'] ?? '')); ?></p>
        </div>
        <?php if (!is_superadmin()): ?>
            <form method="post" action="index.php" class="inline-form">
                <?= csrf_input(); ?>
                <input type="hidden" name="action" value="asset_declare">
                <button type="submit" <?= !empty($declaration['declared_status']) ? 'disabled' : ''; ?>>
                    <?= !empty($declaration['declared_status']) ? 'Data Declared / ঘোষণা সম্পন্ন' : 'Declare Data Up To Date / হালনাগাদ ঘোষণা'; ?>
                </button>
            </form>
        <?php endif; ?>
    </div>
    <?php if (!empty($declaration['declared_status'])): ?>
        <p class="hint">Declared at: <?= e((string)$declaration['declared_at']); ?></p>
    <?php endif; ?>
</section>

<?php if (is_superadmin()): ?>
<section class="card">
    <h2>Master Filters / ফিল্টার</h2>
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
                <option value="Very well" <?= ($filters['condition_value'] ?? '') === 'Very well' ? 'selected' : ''; ?>>Very well</option>
                <option value="Usable" <?= ($filters['condition_value'] ?? '') === 'Usable' ? 'selected' : ''; ?>>Usable</option>
                <option value="Unusable" <?= ($filters['condition_value'] ?? '') === 'Unusable' ? 'selected' : ''; ?>>Unusable</option>
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
    </form>
</section>
<?php endif; ?>

<?php if (!is_superadmin()): ?>
<section class="card">
    <div class="toolbar-row">
        <button type="button" data-modal="asset-modal">Manual Data Entry / ম্যানুয়াল এন্ট্রি</button>
        <button type="button" data-modal="import-modal">Bulk Entry / এক্সেল আপলোড</button>
        <a href="asset_template.php" class="button-link">Download Template / টেমপ্লেট</a>
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
                                        <th><?= e($field['label']); ?></th>
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
            <button type="submit" class="btn-danger">Soft Delete Selected / ডিলিট</button>
        </div>
    <?php endif; ?>
</form>

<?php if (!is_superadmin()): ?>
<div class="modal-backdrop<?= $editingAsset ? ' open' : ''; ?>" id="asset-modal" aria-hidden="<?= $editingAsset ? 'false' : 'true'; ?>">
    <div class="modal-card modal-wide" role="dialog" aria-modal="true" aria-labelledby="asset-modal-title">
        <h3 id="asset-modal-title"><?= $editingAsset ? 'Edit Asset / সম্পদ সম্পাদনা' : 'Manual Asset Entry / সম্পদ এন্ট্রি'; ?></h3>
        <form method="post" action="index.php" class="grid">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="asset_save">
            <input type="hidden" name="asset_id" value="<?= e((string)($editingAsset['id'] ?? '0')); ?>">
            <label>Category / শ্রেণি
                <select name="category_id" id="asset-category-select" required>
                    <option value="">Select</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e((string)$category['id']); ?>" <?= (int)($editingAsset['category_id'] ?? 0) === (int)$category['id'] ? 'selected' : ''; ?>><?= e($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Sub-category / উপ-শ্রেণি
                <select name="subcategory_id" id="asset-subcategory-select" required>
                    <option value="">Select</option>
                    <?php foreach ($subcategories as $subcategory): ?>
                        <option value="<?= e((string)$subcategory['id']); ?>" data-category="<?= e((string)$subcategory['category_id']); ?>" <?= (int)($editingAsset['subcategory_id'] ?? 0) === (int)$subcategory['id'] ? 'selected' : ''; ?>><?= e($subcategory['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php foreach ($fields as $field): ?>
                <?php if ((int)$field['active_status'] !== 1) { continue; } ?>
                <label><?= e($field['label']); ?>
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
        <h3 id="import-modal-title">Bulk Asset Upload / এক্সেল আপলোড</h3>
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
    <div class="modal-card modal-wide" role="dialog" aria-modal="true" aria-labelledby="import-review-title">
        <h3 id="import-review-title">Import Audit Review / অডিট রিভিউ</h3>
        <form method="post" action="index.php" class="grid">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="asset_import_save">
            <div class="table-wrap">
                <table class="audit-table">
                    <thead>
                        <tr>
                            <th>Row</th>
                            <th>Category</th>
                            <th>Sub-category</th>
                            <?php foreach ($fields as $field): ?>
                                <?php if ((int)$field['is_import_enabled'] === 1 && (int)$field['active_status'] === 1): ?>
                                    <th><?= e($field['label']); ?></th>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <th>Audit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($review['rows'] as $rowIndex => $row): ?>
                            <tr class="<?= !empty($row['errors']) ? 'has-errors' : ''; ?>">
                                <td>
                                    <?= e((string)$row['row_number']); ?>
                                    <input type="hidden" name="rows[<?= $rowIndex; ?>][row_number]" value="<?= e((string)$row['row_number']); ?>">
                                </td>
                                <td><input type="text" name="rows[<?= $rowIndex; ?>][category]" value="<?= e($row['category']); ?>"></td>
                                <td><input type="text" name="rows[<?= $rowIndex; ?>][subcategory]" value="<?= e($row['subcategory']); ?>"></td>
                                <?php foreach ($fields as $field): ?>
                                    <?php if ((int)$field['is_import_enabled'] === 1 && (int)$field['active_status'] === 1): ?>
                                        <?php $fieldError = $row['errors'][$field['field_key']] ?? null; ?>
                                        <td class="<?= $fieldError ? 'cell-error' : ''; ?>">
                                            <input type="text" name="rows[<?= $rowIndex; ?>][fields][<?= e($field['field_key']); ?>]" value="<?= e((string)($row['fields'][$field['field_key']] ?? '')); ?>">
                                        </td>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <td>
                                    <?php if (!empty($row['errors'])): ?>
                                        <?php foreach ($row['errors'] as $message): ?>
                                            <div class="error-text"><?= e($message); ?></div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="success-text">OK</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="modal-actions">
                <button type="submit">Save Validated Rows</button>
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

<?php require __DIR__ . '/footer.php'; ?>
