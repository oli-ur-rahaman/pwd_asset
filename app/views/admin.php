<?php
require __DIR__ . '/header.php';
$canManageSuperadmin = can_manage_superadmin_scope();
$activeSegmentId = asset_active_segment_id((int)request_str('segment_id', '0'));
$activeSegment = asset_active_segment($activeSegmentId, true);
$segments = get_asset_segments(true);
$categories = get_asset_categories(true, $activeSegmentId);
$subcategories = get_asset_subcategories(null, true, $activeSegmentId);
$fields = get_asset_management_fields(true, $activeSegmentId);
$templateColumns = asset_template_columns($activeSegmentId);
$uploadedTemplate = asset_template_uploaded_info($activeSegmentId);
$subcategoryEnabled = asset_subcategory_enabled($activeSegmentId);
$categorySelectionEnabled = asset_category_selection_enabled($activeSegmentId);
$assetNumberVisibleToUsers = asset_number_visible_to_users();
$scopeSwitchEnabled = asset_scope_switch_enabled($activeSegmentId);
$filterCardEnabledForSuperadmin = asset_filter_card_enabled_for_superadmin($activeSegmentId);
$filterCardEnabledForUsers = asset_filter_card_enabled_for_users($activeSegmentId);
$bulkImportEnabled = asset_bulk_import_enabled($activeSegmentId);
$filterScopeOptions = asset_filter_scope_options();
?>
<?php if (!$canManageSuperadmin): ?>
    <style>
        .superadmin-readonly-page button[type="submit"],
        .superadmin-readonly-page .btn-danger,
        .superadmin-readonly-page .office-save-button {
            display: none !important;
        }
        .superadmin-readonly-page input:not([type="hidden"]),
        .superadmin-readonly-page select,
        .superadmin-readonly-page textarea {
            pointer-events: none;
            background: #f4f6f8;
            color: #425466;
        }
    </style>
<?php endif; ?>
<div class="<?= !$canManageSuperadmin ? 'superadmin-readonly-page' : ''; ?>">
<?php if (!$canManageSuperadmin): ?>
    <section class="card">
        <p class="hint">View-only superadmin users can review this page but cannot change segment settings, templates, categories, sub-categories, or fields.</p>
    </section>
<?php endif; ?>
<section class="card">
    <h2>Segments</h2>
    <div class="toolbar-row scope-switch-row">
        <?php foreach ($segments as $segment): ?>
            <a href="index.php?<?= e(http_build_query(['page' => 'admin', 'segment_id' => (int)$segment['id']])); ?>" class="button-link<?= $activeSegmentId === (int)$segment['id'] ? ' is-active' : ''; ?>">
                <?= e((string)$segment['segment_name']); ?>
            </a>
        <?php endforeach; ?>
    </div>
    <form method="post" action="index.php" class="inline-form">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="create_asset_segment">
        <input type="text" name="segment_name" placeholder="New segment name" required>
        <button type="submit">Add Segment</button>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Name</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($segments as $segment): ?>
                    <?php $segmentFormId = 'segment-' . (int)$segment['id']; $segmentActive = (int)($segment['active_status'] ?? 0) === 1; ?>
                    <tr>
                        <td><input form="<?= e($segmentFormId); ?>" class="inline-edit" type="text" name="segment_name" value="<?= e((string)$segment['segment_name']); ?>" required></td>
                        <td><span class="<?= $segmentActive ? 'status-active' : 'status-inactive'; ?>"><?= $segmentActive ? 'Active' : 'Disabled'; ?></span></td>
                        <td>
                            <div class="action-row">
                                <form method="post" action="index.php" id="<?= e($segmentFormId); ?>" class="office-inline-form">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="action" value="update_asset_segment">
                                    <input type="hidden" name="segment_id" value="<?= e((string)$segment['id']); ?>">
                                    <button type="submit" class="btn-small office-save-button">Save</button>
                                </form>
                                <form method="post" action="index.php" class="inline-form">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="action" value="toggle_asset_segment">
                                    <input type="hidden" name="segment_id" value="<?= e((string)$segment['id']); ?>">
                                    <input type="hidden" name="active_status" value="<?= $segmentActive ? '0' : '1'; ?>">
                                    <button type="submit" class="btn-small <?= $segmentActive ? 'btn-danger' : ''; ?>"><?= $segmentActive ? 'Disable' : 'Enable'; ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
 </div>

<section class="card">
    <h2>Excel Template</h2>
    <?php if ($activeSegment): ?><p class="muted">Active segment: <?= e((string)$activeSegment['segment_name']); ?></p><?php endif; ?>
    <p class="hint">Serial No stays first and Instruction stays last. The middle columns are driven by active import fields.</p>
    <?php if ($uploadedTemplate): ?>
        <p class="muted">Custom template uploaded at <?= e($uploadedTemplate['updated_at']); ?>.</p>
    <?php else: ?>
        <p class="muted">No custom template uploaded yet. Default generated template will be used.</p>
    <?php endif; ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>SL</th><th>Column Name</th><th>Key</th></tr>
            </thead>
            <tbody>
                <?php foreach ($templateColumns as $index => $column): ?>
                    <tr>
                        <td><?= e((string)($index + 1)); ?></td>
                        <td><?= e($column['label']); ?></td>
                        <td><?= e($column['key']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <form method="post" action="index.php" enctype="multipart/form-data" class="grid">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="upload_asset_template">
        <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
        <label>Upload Template Excel File
            <input type="file" name="template_file" accept=".xlsx,.xls" required>
        </label>
        <div class="toolbar-row">
            <button type="submit">Upload Template</button>
            <a href="asset_template.php?<?= e(http_build_query(['segment_id' => $activeSegmentId])); ?>" class="button-link">Download Current Template</a>
            <a href="asset_template.php?<?= e(http_build_query(['mode' => 'auto', 'segment_id' => $activeSegmentId])); ?>" class="button-link">Download Auto Template</a>
        </div>
    </form>
</section>

<section class="card">
    <h2>Sub-category Visibility</h2>
    <p class="hint">Hide sub-category everywhere when your current workflow does not need it. If a segment has no active sub-category, it stays hidden automatically.</p>
    <form method="post" action="index.php" class="inline-form">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="save_subcategory_visibility">
        <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
        <label><input type="checkbox" name="asset_subcategory_enabled" value="1" <?= $subcategoryEnabled ? 'checked' : ''; ?>> Show Sub-category</label>
        <button type="submit">Save</button>
    </form>
</section>

<section class="card">
    <h2>Board Cards</h2>
    <p class="hint">These controls are segment specific.</p>
    <form method="post" action="index.php" class="inline-form">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="save_asset_scope_switch_visibility">
        <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
        <label><input type="checkbox" name="show_office_scope_switch" value="1" <?= $scopeSwitchEnabled ? 'checked' : ''; ?>> Show My Office / Office Under Me card</label>
        <button type="submit">Save</button>
    </form>
    <form method="post" action="index.php" class="inline-form">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="save_asset_filter_card_visibility">
        <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
        <label><input type="checkbox" name="show_filter_card_superadmin" value="1" <?= $filterCardEnabledForSuperadmin ? 'checked' : ''; ?>> Show Filter card for superadmin</label>
        <label><input type="checkbox" name="show_filter_card_users" value="1" <?= $filterCardEnabledForUsers ? 'checked' : ''; ?>> Show Filter card for users</label>
        <button type="submit">Save</button>
    </form>
    <form method="post" action="index.php" class="inline-form">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="save_asset_bulk_import_visibility">
        <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
        <label><input type="checkbox" name="allow_bulk_import" value="1" <?= $bulkImportEnabled ? 'checked' : ''; ?>> Show Bulk Entry for users</label>
        <button type="submit">Save</button>
    </form>
</section>

<section class="card">
    <h2>Asset Number Visibility</h2>
    <p class="hint">Control whether office-side users see the Asset Number column in asset tables.</p>
    <form method="post" action="index.php" class="inline-form">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="save_asset_number_visibility">
        <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
        <label><input type="checkbox" name="asset_number_visible_to_users" value="1" <?= $assetNumberVisibleToUsers ? 'checked' : ''; ?>> Show Asset Number for office users</label>
        <button type="submit">Save</button>
    </form>
</section>

<section class="card">
    <h2>Categories / শ্রেণি</h2>
    <form method="post" action="index.php" class="inline-form">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="create_asset_category">
        <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
        <input type="text" name="name" placeholder="New category" required>
        <button type="submit">Add Category</button>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Name</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <?php $formId = 'category-' . (int)$category['id']; $isActive = (int)$category['active_status'] === 1; ?>
                    <tr>
                        <td><input form="<?= e($formId); ?>" class="inline-edit" type="text" name="name" value="<?= e($category['name']); ?>" required></td>
                        <td><span class="<?= $isActive ? 'status-active' : 'status-inactive'; ?>"><?= $isActive ? 'Active' : 'Disabled'; ?></span></td>
                        <td>
                            <div class="action-row">
                                <form method="post" action="index.php" id="<?= e($formId); ?>" class="office-inline-form">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="action" value="update_asset_category">
                                    <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
                                    <input type="hidden" name="category_id" value="<?= e((string)$category['id']); ?>">
                                    <button type="submit" class="btn-small office-save-button">Save</button>
                                </form>
                                <form method="post" action="index.php" class="inline-form">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="action" value="toggle_asset_category">
                                    <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
                                    <input type="hidden" name="category_id" value="<?= e((string)$category['id']); ?>">
                                    <input type="hidden" name="active_status" value="<?= $isActive ? '0' : '1'; ?>">
                                    <button type="submit" class="btn-small <?= $isActive ? 'btn-danger' : ''; ?>"><?= $isActive ? 'Disable' : 'Enable'; ?></button>
                                </form>
                                <form method="post" action="index.php" class="inline-form">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="action" value="delete_asset_category">
                                    <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
                                    <input type="hidden" name="category_id" value="<?= e((string)$category['id']); ?>">
                                    <button type="submit" class="btn-small btn-danger">Delete</button>
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
    <h2>Sub-categories / উপ-শ্রেণি</h2>
    <form method="post" action="index.php" class="inline-form">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="create_asset_subcategory">
        <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
        <select name="category_id" required>
            <option value="">Category</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= e((string)$category['id']); ?>"><?= e($category['name']); ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="name" placeholder="New sub-category" required>
        <button type="submit">Add Sub-category</button>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Category</th><th>Name</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($subcategories as $subcategory): ?>
                    <?php $formId = 'subcategory-' . (int)$subcategory['id']; $isActive = (int)$subcategory['active_status'] === 1; ?>
                    <tr>
                        <td>
                            <select form="<?= e($formId); ?>" class="inline-edit" name="category_id" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= e((string)$category['id']); ?>" <?= (int)$subcategory['category_id'] === (int)$category['id'] ? 'selected' : ''; ?>><?= e($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input form="<?= e($formId); ?>" class="inline-edit" type="text" name="name" value="<?= e($subcategory['name']); ?>" required></td>
                        <td><span class="<?= $isActive ? 'status-active' : 'status-inactive'; ?>"><?= $isActive ? 'Active' : 'Disabled'; ?></span></td>
                        <td>
                            <div class="action-row">
                                <form method="post" action="index.php" id="<?= e($formId); ?>" class="office-inline-form">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="action" value="update_asset_subcategory">
                                    <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
                                    <input type="hidden" name="subcategory_id" value="<?= e((string)$subcategory['id']); ?>">
                                    <button type="submit" class="btn-small office-save-button">Save</button>
                                </form>
                                <form method="post" action="index.php" class="inline-form">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="action" value="toggle_asset_subcategory">
                                    <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
                                    <input type="hidden" name="subcategory_id" value="<?= e((string)$subcategory['id']); ?>">
                                    <input type="hidden" name="active_status" value="<?= $isActive ? '0' : '1'; ?>">
                                    <button type="submit" class="btn-small <?= $isActive ? 'btn-danger' : ''; ?>"><?= $isActive ? 'Disable' : 'Enable'; ?></button>
                                </form>
                                <form method="post" action="index.php" class="inline-form">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="action" value="delete_asset_subcategory">
                                    <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
                                    <input type="hidden" name="subcategory_id" value="<?= e((string)$subcategory['id']); ?>">
                                    <button type="submit" class="btn-small btn-danger">Delete</button>
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
    <h2>Asset Fields / সম্পদের কলাম</h2>
    <form method="post" action="index.php" class="grid asset-field-form" data-asset-field-form>
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="create_asset_field">
        <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
        <label>Serial
            <input type="number" name="sort_order" min="1" step="1" value="<?= e((string)((count($fields) + 1) * 10)); ?>" required>
        </label>
        <label>Label
            <input type="text" name="label" required>
        </label>
        <label>Field Key (optional)
            <input type="text" name="field_key">
        </label>
        <label>Type
            <select name="data_type" required data-field-type-select>
                <?php foreach (asset_supported_data_types() as $type): ?>
                    <option value="<?= e($type); ?>"><?= e($type); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="field-config-group" data-field-config="dropdown">
            <label>Dropdown Options
                <textarea name="options_text" rows="3" placeholder="One option per line"></textarea>
            </label>
        </div>
        <div class="field-config-group" data-field-config="number">
            <label>Number Format Rule
                <input type="text" name="number_format_rule" placeholder="8.2 or -*8.*2">
            </label>
            <div class="hint">
                <?php foreach (asset_number_format_rule_examples() as $example): ?>
                    <div><?= e($example); ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="field-config-group" data-field-config="conditional">
            <label>Secondary Label
                <input type="text" name="secondary_label" placeholder="Secondary dropdown label">
            </label>
        </div>
        <div class="field-config-group" data-field-config="conditional">
            <label>Primary Dropdown Options
                <textarea name="conditional_primary_options_text" rows="4" placeholder="One primary option per line"></textarea>
            </label>
        </div>
        <div class="field-config-group" data-field-config="conditional">
            <label>Conditional Rules
                <textarea name="conditional_rules_text" rows="5" placeholder="numeric=roman,english,greek&#10;letter=english,hindi,bengali"></textarea>
            </label>
            <div class="hint">Use one rule per line in the format: <code>Primary=child1,child2</code></div>
        </div>
        <div class="field-config-group" data-field-config="file">
            <label>File Mode
                <select name="file_is_multiple">
                    <option value="0">Single file</option>
                    <option value="1">Multiple files</option>
                </select>
            </label>
        </div>
        <div class="field-config-group" data-field-config="file">
            <label>Max Files
                <input type="number" name="file_max_files" min="1" step="1" value="1">
            </label>
        </div>
        <div class="field-config-group" data-field-config="file">
            <label>Max File Size (MB)
                <input type="number" name="file_max_size_mb" min="0" step="0.1" value="0">
            </label>
        </div>
        <div class="field-config-group" data-field-config="file">
            <label>Total Upload Size (MB)
                <input type="number" name="file_total_size_mb" min="0" step="0.1" value="0">
            </label>
        </div>
        <div class="field-config-group" data-field-config="file">
            <label>Allowed Extensions
                <input type="text" name="file_allowed_extensions" placeholder="pdf,jpg,docx,xlsx">
            </label>
        </div>
        <label><input type="checkbox" name="is_required" value="1"> Mandatory</label>
        <label><input type="checkbox" name="is_displayed" value="1" checked> Show in tables</label>
        <label><input type="checkbox" name="is_import_enabled" value="1" checked> Allow in import</label>
        <label><input type="checkbox" name="is_unique" value="1"> Unique value</label>
        <label>Filter Scope
            <select name="filter_scope">
                <?php foreach ($filterScopeOptions as $scopeValue => $scopeLabel): ?>
                    <option value="<?= e((string)$scopeValue); ?>" <?= (int)$scopeValue === asset_filter_scope_none() ? 'selected' : ''; ?>><?= e($scopeLabel); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Add Field</button>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Serial</th><th>Label</th><th>Key</th><th>Type</th><th>Options / File Rules</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($fields as $field): ?>
                    <?php
                        $formId = 'field-' . (int)$field['id'];
                        $isActive = (int)$field['active_status'] === 1;
                        $optionLines = [];
                        $fileRule = get_asset_field_file_rule((int)$field['id']);
                        $conditionalChild = asset_is_conditional_primary($field) ? get_asset_conditional_child_field((int)$field['id'], true) : null;
                        $conditionalMap = asset_is_conditional_primary($field) ? asset_decode_conditional_map($field) : [];
                        $conditionalRuleLines = [];
                        foreach (get_asset_field_options((int)$field['id'], true) as $option) {
                            $optionLines[] = (string)$option['option_value'];
                        }
                        foreach ($conditionalMap as $primary => $children) {
                            $conditionalRuleLines[] = $primary . '=' . implode(',', $children);
                        }
                    ?>
                    <tr>
                        <td><input form="<?= e($formId); ?>" class="inline-edit" type="number" name="sort_order" min="1" step="1" value="<?= e((string)$field['sort_order']); ?>" required></td>
                        <td><input form="<?= e($formId); ?>" class="inline-edit" type="text" name="label" value="<?= e($field['label']); ?>" required></td>
                        <td><input form="<?= e($formId); ?>" class="inline-readonly" type="text" value="<?= e($field['field_key']); ?>" readonly></td>
                        <td>
                            <select form="<?= e($formId); ?>" class="inline-edit" name="data_type" data-field-type-select>
                                <?php foreach (asset_supported_data_types() as $type): ?>
                                    <option value="<?= e($type); ?>" <?= $field['data_type'] === $type ? 'selected' : ''; ?>><?= e($type); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <div class="field-config-group" data-field-config="dropdown">
                                <textarea form="<?= e($formId); ?>" class="inline-edit field-options-box" name="options_text" rows="3" placeholder="One option per line"><?= e(implode("\n", $optionLines)); ?></textarea>
                            </div>
                            <div class="field-config-group" data-field-config="number">
                                <input form="<?= e($formId); ?>" class="inline-edit" type="text" name="number_format_rule" value="<?= e((string)($field['number_format_rule'] ?? '')); ?>" placeholder="8.2 or -*8.*2">
                                <div class="hint">
                                    <?php foreach (asset_number_format_rule_examples() as $example): ?>
                                        <div><?= e($example); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="field-config-group" data-field-config="conditional">
                                <label>Secondary Label
                                    <input form="<?= e($formId); ?>" class="inline-edit" type="text" name="secondary_label" value="<?= e((string)($conditionalChild['label'] ?? '')); ?>" placeholder="Secondary dropdown label">
                                </label>
                                <label>Primary Dropdown Options
                                    <textarea form="<?= e($formId); ?>" class="inline-edit field-options-box" name="conditional_primary_options_text" rows="3" placeholder="One primary option per line"><?= e(implode("\n", $optionLines)); ?></textarea>
                                </label>
                                <label>Conditional Rules
                                    <textarea form="<?= e($formId); ?>" class="inline-edit field-options-box" name="conditional_rules_text" rows="4" placeholder="numeric=roman,english,greek"><?= e(implode("\n", $conditionalRuleLines)); ?></textarea>
                                </label>
                                <div class="hint">Primary and secondary fields save together.</div>
                            </div>
                            <div class="grid compact-grid field-config-group" data-field-config="file">
                                <label>File Mode
                                    <select form="<?= e($formId); ?>" class="inline-edit" name="file_is_multiple">
                                        <option value="0" <?= (int)$fileRule['is_multiple'] === 0 ? 'selected' : ''; ?>>Single file</option>
                                        <option value="1" <?= (int)$fileRule['is_multiple'] === 1 ? 'selected' : ''; ?>>Multiple files</option>
                                    </select>
                                </label>
                                <label>Max Files
                                    <input form="<?= e($formId); ?>" class="inline-edit" type="number" name="file_max_files" min="1" step="1" value="<?= e((string)$fileRule['max_files']); ?>">
                                </label>
                                <label>Max File Size (MB)
                                    <input form="<?= e($formId); ?>" class="inline-edit" type="number" name="file_max_size_mb" min="0" step="0.1" value="<?= e(asset_megabytes_from_bytes((int)$fileRule['max_file_size_bytes'])); ?>">
                                </label>
                                <label>Total Upload Size (MB)
                                    <input form="<?= e($formId); ?>" class="inline-edit" type="number" name="file_total_size_mb" min="0" step="0.1" value="<?= e(asset_megabytes_from_bytes((int)$fileRule['max_total_size_bytes'])); ?>">
                                </label>
                                <label>Allowed Extensions
                                    <input form="<?= e($formId); ?>" class="inline-edit" type="text" name="file_allowed_extensions" value="<?= e((string)$fileRule['allowed_extensions']); ?>" placeholder="pdf,jpg,docx,xlsx">
                                </label>
                            </div>
                        </td>
                        <td><span class="<?= $isActive ? 'status-active' : 'status-inactive'; ?>"><?= $isActive ? 'Active' : 'Disabled'; ?></span></td>
                        <td>
                            <div class="action-row">
                                <form method="post" action="index.php" id="<?= e($formId); ?>" class="office-inline-form">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="action" value="update_asset_field">
                                    <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
                                    <input type="hidden" name="field_id" value="<?= e((string)$field['id']); ?>">
                                    <input type="hidden" name="field_key" value="<?= e($field['field_key']); ?>">
                                    <label class="inline-check"><input form="<?= e($formId); ?>" type="checkbox" name="is_required" value="1" <?= (int)$field['is_required'] === 1 ? 'checked' : ''; ?>> Required</label>
                                    <label class="inline-check"><input form="<?= e($formId); ?>" type="checkbox" name="is_displayed" value="1" <?= (int)$field['is_displayed'] === 1 ? 'checked' : ''; ?>> Display</label>
                                    <label class="inline-check"><input form="<?= e($formId); ?>" type="checkbox" name="is_import_enabled" value="1" <?= (int)$field['is_import_enabled'] === 1 ? 'checked' : ''; ?> <?= in_array($field['data_type'], ['file'], true) ? 'disabled' : ''; ?>> Import</label>
                                    <label class="inline-check"><input form="<?= e($formId); ?>" type="checkbox" name="is_unique" value="1" <?= (int)($field['is_unique'] ?? 0) === 1 ? 'checked' : ''; ?> <?= in_array($field['data_type'], ['file', 'conditional'], true) ? 'disabled' : ''; ?>> Unique</label>
                                    <label>Filter Scope
                                        <select form="<?= e($formId); ?>" class="inline-edit" name="filter_scope">
                                            <?php foreach ($filterScopeOptions as $scopeValue => $scopeLabel): ?>
                                                <option value="<?= e((string)$scopeValue); ?>" <?= asset_normalize_filter_scope($field['filter_scope'] ?? (($field['is_filter_enabled'] ?? 0) ? asset_filter_scope_all() : asset_filter_scope_none())) === (int)$scopeValue ? 'selected' : ''; ?>><?= e($scopeLabel); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <button type="submit" class="btn-small office-save-button">Save</button>
                                </form>
                                <form method="post" action="index.php" class="inline-form">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="action" value="toggle_asset_field">
                                    <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
                                    <input type="hidden" name="field_id" value="<?= e((string)$field['id']); ?>">
                                    <input type="hidden" name="active_status" value="<?= $isActive ? '0' : '1'; ?>">
                                    <button type="submit" class="btn-small <?= $isActive ? 'btn-danger' : ''; ?>"><?= $isActive ? 'Disable' : 'Enable'; ?></button>
                                </form>
                                <form method="post" action="index.php" class="inline-form">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="action" value="delete_asset_field">
                                    <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
                                    <input type="hidden" name="field_id" value="<?= e((string)$field['id']); ?>">
                                    <button type="submit" class="btn-small btn-danger">Delete</button>
                                </form>
                            </div>
                            <div class="hint">Use one line per dropdown option. File rules apply only when type is set to file. Conditional fields save two linked dropdown columns together.</div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/footer.php'; ?>
