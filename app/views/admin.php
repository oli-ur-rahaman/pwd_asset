<?php
require __DIR__ . '/header.php';
$categories = get_asset_categories(true);
$subcategories = get_asset_subcategories(null, true);
$fields = get_asset_fields(true);
$templateColumns = asset_template_columns();
$uploadedTemplate = asset_template_uploaded_info();
?>
<section class="card">
    <h2>Excel Template</h2>
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
        <label>Upload Template Excel File
            <input type="file" name="template_file" accept=".xlsx,.xls" required>
        </label>
        <div class="toolbar-row">
            <button type="submit">Upload Template</button>
            <a href="asset_template.php" class="button-link">Download Current Template</a>
        </div>
    </form>
</section>

<section class="card">
    <h2>Categories / শ্রেণি</h2>
    <form method="post" action="index.php" class="inline-form">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="create_asset_category">
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
                                    <input type="hidden" name="category_id" value="<?= e((string)$category['id']); ?>">
                                    <button type="submit" class="btn-small office-save-button">Save</button>
                                </form>
                                <form method="post" action="index.php" class="inline-form">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="action" value="toggle_asset_category">
                                    <input type="hidden" name="category_id" value="<?= e((string)$category['id']); ?>">
                                    <input type="hidden" name="active_status" value="<?= $isActive ? '0' : '1'; ?>">
                                    <button type="submit" class="btn-small <?= $isActive ? 'btn-danger' : ''; ?>"><?= $isActive ? 'Disable' : 'Enable'; ?></button>
                                </form>
                                <form method="post" action="index.php" class="inline-form">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="action" value="delete_asset_category">
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
                                    <input type="hidden" name="subcategory_id" value="<?= e((string)$subcategory['id']); ?>">
                                    <button type="submit" class="btn-small office-save-button">Save</button>
                                </form>
                                <form method="post" action="index.php" class="inline-form">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="action" value="toggle_asset_subcategory">
                                    <input type="hidden" name="subcategory_id" value="<?= e((string)$subcategory['id']); ?>">
                                    <input type="hidden" name="active_status" value="<?= $isActive ? '0' : '1'; ?>">
                                    <button type="submit" class="btn-small <?= $isActive ? 'btn-danger' : ''; ?>"><?= $isActive ? 'Disable' : 'Enable'; ?></button>
                                </form>
                                <form method="post" action="index.php" class="inline-form">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="action" value="delete_asset_subcategory">
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
    <form method="post" action="index.php" class="grid">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="create_asset_field">
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
            <select name="data_type" required>
                <?php foreach (asset_supported_data_types() as $type): ?>
                    <option value="<?= e($type); ?>"><?= e($type); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Dropdown Options
            <textarea name="options_text" rows="3" placeholder="One option per line"></textarea>
        </label>
        <label><input type="checkbox" name="is_required" value="1"> Mandatory</label>
        <label><input type="checkbox" name="is_displayed" value="1" checked> Show in tables</label>
        <label><input type="checkbox" name="is_import_enabled" value="1" checked> Allow in import</label>
        <button type="submit">Add Field</button>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Serial</th><th>Label</th><th>Key</th><th>Type</th><th>Dropdown Options</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($fields as $field): ?>
                    <?php
                        $formId = 'field-' . (int)$field['id'];
                        $isActive = (int)$field['active_status'] === 1;
                        $optionLines = [];
                        foreach (get_asset_field_options((int)$field['id'], true) as $option) {
                            $optionLines[] = (string)$option['option_value'];
                        }
                    ?>
                    <tr>
                        <td><input form="<?= e($formId); ?>" class="inline-edit" type="number" name="sort_order" min="1" step="1" value="<?= e((string)$field['sort_order']); ?>" required></td>
                        <td><input form="<?= e($formId); ?>" class="inline-edit" type="text" name="label" value="<?= e($field['label']); ?>" required></td>
                        <td><input form="<?= e($formId); ?>" class="inline-readonly" type="text" value="<?= e($field['field_key']); ?>" readonly></td>
                        <td>
                            <select form="<?= e($formId); ?>" class="inline-edit" name="data_type">
                                <?php foreach (asset_supported_data_types() as $type): ?>
                                    <option value="<?= e($type); ?>" <?= $field['data_type'] === $type ? 'selected' : ''; ?>><?= e($type); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><textarea form="<?= e($formId); ?>" class="inline-edit field-options-box" name="options_text" rows="3" placeholder="One option per line"><?= e(implode("\n", $optionLines)); ?></textarea></td>
                        <td><span class="<?= $isActive ? 'status-active' : 'status-inactive'; ?>"><?= $isActive ? 'Active' : 'Disabled'; ?></span></td>
                        <td>
                            <div class="action-row">
                                <form method="post" action="index.php" id="<?= e($formId); ?>" class="office-inline-form">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="action" value="update_asset_field">
                                    <input type="hidden" name="field_id" value="<?= e((string)$field['id']); ?>">
                                    <input type="hidden" name="field_key" value="<?= e($field['field_key']); ?>">
                                    <label class="inline-check"><input form="<?= e($formId); ?>" type="checkbox" name="is_required" value="1" <?= (int)$field['is_required'] === 1 ? 'checked' : ''; ?>> Required</label>
                                    <label class="inline-check"><input form="<?= e($formId); ?>" type="checkbox" name="is_displayed" value="1" <?= (int)$field['is_displayed'] === 1 ? 'checked' : ''; ?>> Display</label>
                                    <label class="inline-check"><input form="<?= e($formId); ?>" type="checkbox" name="is_import_enabled" value="1" <?= (int)$field['is_import_enabled'] === 1 ? 'checked' : ''; ?>> Import</label>
                                    <button type="submit" class="btn-small office-save-button">Save</button>
                                </form>
                                <form method="post" action="index.php" class="inline-form">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="action" value="toggle_asset_field">
                                    <input type="hidden" name="field_id" value="<?= e((string)$field['id']); ?>">
                                    <input type="hidden" name="active_status" value="<?= $isActive ? '0' : '1'; ?>">
                                    <button type="submit" class="btn-small <?= $isActive ? 'btn-danger' : ''; ?>"><?= $isActive ? 'Disable' : 'Enable'; ?></button>
                                </form>
                                <form method="post" action="index.php" class="inline-form">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="action" value="delete_asset_field">
                                    <input type="hidden" name="field_id" value="<?= e((string)$field['id']); ?>">
                                    <button type="submit" class="btn-small btn-danger">Delete</button>
                                </form>
                            </div>
                            <div class="hint">Use one line per dropdown option.</div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/footer.php'; ?>
