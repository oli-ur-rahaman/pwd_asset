<?php
require __DIR__ . '/header.php';
$canManageSuperadmin = can_manage_superadmin_scope();
$activeSegmentId = asset_active_segment_id((int)request_str('segment_id', '0'), true);
$activeSegment = asset_active_segment($activeSegmentId, true);
$segments = get_asset_segments(true);
$categories = get_asset_categories(true, $activeSegmentId);
$subcategories = get_asset_subcategories(null, true, $activeSegmentId);
$fields = get_asset_management_fields(true, $activeSegmentId);
$templateColumns = asset_template_columns($activeSegmentId);
$uploadedTemplate = asset_template_uploaded_info($activeSegmentId);
$templateSource = asset_template_source($activeSegmentId);
$templateSourceOptions = asset_template_source_options($activeSegmentId);
$subcategoryEnabled = asset_subcategory_enabled($activeSegmentId);
$categorySelectionEnabled = asset_category_selection_enabled($activeSegmentId);
$assetNumberVisibleToUsers = asset_number_visible_to_users($activeSegmentId);
$dataProviderVisibleToSuperadmin = asset_data_provider_visible($activeSegmentId);
$scopeVisibilitySettings = asset_scope_visibility_settings($activeSegmentId);
$filterCardEnabledForSuperadmin = asset_filter_card_enabled_for_superadmin($activeSegmentId);
$filterCardEnabledForUsers = asset_filter_card_enabled_for_users($activeSegmentId);
$bulkImportEnabled = asset_bulk_import_enabled($activeSegmentId);
$filterScopeOptions = asset_filter_scope_options();
$numberRuleExamples = asset_number_format_rule_examples();
$commonDefinitionModeOptions = asset_common_definition_mode_options();
$commonRowPolicyOptions = asset_common_row_policy_options();
$commonSupportedTypes = asset_common_supported_field_data_types();
$commonSupportedTypeLookup = array_fill_keys($commonSupportedTypes, true);
$commonRowsSupported = asset_segment_common_rows_supported($activeSegmentId);
$commonProfilesByCategory = asset_common_profiles_by_category_map($activeSegmentId, true);
$commonBindingsByFieldId = get_asset_common_profile_field_bindings_for_segment($activeSegmentId);
$commonParentFieldCandidatesBySegment = asset_common_parent_field_candidates_by_segment(true);
$commonScopeOptions = asset_common_scope_options();
$userDefinedCommonModeNone = 'none';
$trackableFilterFields = array_values(array_filter($fields, static function (array $field): bool {
    return (int)($field['active_status'] ?? 0) === 1
        && !asset_is_conditional_secondary($field)
        && asset_filter_index_field_is_trackable($field);
}));
$categoriesById = [];
foreach ($categories as $category) {
    $categoriesById[(int)$category['id']] = $category;
}
$segmentsById = [];
foreach ($segments as $segment) {
    $segmentsById[(int)$segment['id']] = $segment;
}
$commonParentCategoriesBySegment = [];
foreach ($segments as $segment) {
    $commonParentCategoriesBySegment[(int)$segment['id']] = get_asset_categories(true, (int)$segment['id']);
}
$commonParentFieldsById = [];
foreach ($commonParentFieldCandidatesBySegment as $parentSegmentId => $parentFields) {
    foreach ($parentFields as $parentField) {
        $commonParentFieldsById[(int)$parentField['id']] = $parentField + ['segment_id' => (int)$parentSegmentId];
    }
}
$userDefinedCommonDeclarations = [];
foreach ($fields as $field) {
    $binding = $commonBindingsByFieldId[(int)($field['id'] ?? 0)] ?? null;
    if (!$binding || (string)($binding['definition_mode'] ?? '') !== asset_common_definition_mode_user_defined()) {
        continue;
    }
    if (asset_is_conditional_secondary($field)) {
        continue;
    }
    $parentField = $commonParentFieldsById[(int)($binding['parent_field_id'] ?? 0)] ?? null;
    $parentSegment = $segmentsById[(int)($binding['parent_segment_id'] ?? 0)] ?? null;
    $parentCategory = $categoriesById[(int)($binding['parent_category_id'] ?? 0)] ?? null;
    if (!$parentCategory && isset($commonParentCategoriesBySegment[(int)($binding['parent_segment_id'] ?? 0)])) {
        foreach ($commonParentCategoriesBySegment[(int)($binding['parent_segment_id'] ?? 0)] as $candidateCategory) {
            if ((int)($candidateCategory['id'] ?? 0) === (int)($binding['parent_category_id'] ?? 0)) {
                $parentCategory = $candidateCategory;
                break;
            }
        }
    }
    $userDefinedCommonDeclarations[] = [
        'child_field' => $field,
        'binding' => $binding,
        'parent_field' => $parentField,
        'parent_segment' => $parentSegment,
        'parent_category' => $parentCategory,
    ];
}
usort($userDefinedCommonDeclarations, static function (array $left, array $right): int {
    $leftOrder = (int)($left['binding']['sort_order'] ?? 0);
    $rightOrder = (int)($right['binding']['sort_order'] ?? 0);
    if ($leftOrder !== $rightOrder) {
        return $leftOrder <=> $rightOrder;
    }
    return strcasecmp((string)($left['child_field']['label'] ?? ''), (string)($right['child_field']['label'] ?? ''));
});
$existingUserDefinedCommonProfile = null;
foreach (get_asset_common_profiles_for_segment($activeSegmentId, true) as $candidateProfile) {
    if ((string)($candidateProfile['definition_mode'] ?? '') === asset_common_definition_mode_user_defined()) {
        $existingUserDefinedCommonProfile = $candidateProfile;
        break;
    }
}
$userDefinedCommonModeDrafts = $_SESSION['user_defined_common_row_policy_draft'] ?? [];
$userDefinedCommonModeSelection = $existingUserDefinedCommonProfile
    ? (string)($existingUserDefinedCommonProfile['row_policy'] ?? asset_common_row_policy_fixed())
    : trim((string)($userDefinedCommonModeDrafts[$activeSegmentId] ?? $userDefinedCommonModeNone));
if (!in_array($userDefinedCommonModeSelection, array_merge([$userDefinedCommonModeNone], array_keys($commonRowPolicyOptions)), true)) {
    $userDefinedCommonModeSelection = $userDefinedCommonModeNone;
}
$userDefinedDeclarationBlockedByMode = !$existingUserDefinedCommonProfile && $userDefinedCommonModeSelection === $userDefinedCommonModeNone;

if (!function_exists('render_common_admin_row_input')) {
    function render_common_admin_row_input(string $formId, array $fieldMeta, int $childFieldId, string $value): string
    {
        $fieldName = 'common_row_values[' . $childFieldId . ']';
        $class = 'inline-edit';
        $dataType = (string)($fieldMeta['data_type'] ?? 'text');
        if ($dataType === 'yes_no') {
            $html = '<select form="' . e($formId) . '" class="' . e($class) . '" name="' . e($fieldName) . '">';
            $html .= '<option value=""></option>';
            foreach (['Yes', 'No'] as $option) {
                $selected = strcasecmp($value, $option) === 0 ? ' selected' : '';
                $html .= '<option value="' . e($option) . '"' . $selected . '>' . e($option) . '</option>';
            }
            $html .= '</select>';
            return $html;
        }
        if ($dataType === 'dropdown') {
            $html = '<select form="' . e($formId) . '" class="' . e($class) . '" name="' . e($fieldName) . '">';
            $html .= '<option value=""></option>';
            foreach (get_asset_field_options((int)($fieldMeta['id'] ?? 0), true) as $option) {
                $optionValue = (string)($option['option_value'] ?? '');
                $selected = $optionValue === $value ? ' selected' : '';
                $html .= '<option value="' . e($optionValue) . '"' . $selected . '>' . e($optionValue) . '</option>';
            }
            $html .= '</select>';
            return $html;
        }
        $inputType = $dataType === 'date' ? 'date' : 'text';
        return '<input form="' . e($formId) . '" class="' . e($class) . '" type="' . e($inputType) . '" name="' . e($fieldName) . '" value="' . e($value) . '">';
    }
}
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
        <select name="new_batch_days">
            <?php foreach (asset_segment_new_batch_options() as $batchValue => $batchLabel): ?>
                <option value="<?= e((string)$batchValue); ?>" <?= (int)$batchValue === 0 ? 'selected' : ''; ?>><?= e((string)$batchLabel); ?></option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="sort_order" min="1" step="1" placeholder="Order (optional)">
        <button type="submit">Add Segment</button>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Name</th><th>New Batch</th><th>Order</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($segments as $segment): ?>
                    <?php $segmentFormId = 'segment-' . (int)$segment['id']; $segmentActive = (int)($segment['active_status'] ?? 0) === 1; ?>
                    <tr>
                        <td><input form="<?= e($segmentFormId); ?>" class="inline-edit" type="text" name="segment_name" value="<?= e((string)$segment['segment_name']); ?>" required></td>
                        <td>
                            <select form="<?= e($segmentFormId); ?>" class="inline-edit" name="new_batch_days">
                                <?php foreach (asset_segment_new_batch_options() as $batchValue => $batchLabel): ?>
                                    <option value="<?= e((string)$batchValue); ?>" <?= asset_normalize_segment_new_batch_days($segment['new_batch_days'] ?? 0) === (int)$batchValue ? 'selected' : ''; ?>><?= e((string)$batchLabel); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input form="<?= e($segmentFormId); ?>" class="inline-edit" type="number" name="sort_order" min="1" step="1" value="<?= e((string)($segment['sort_order'] ?? 0)); ?>" required></td>
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
    <p class="hint">Serial No stays first and Instruction stays last. The middle columns are driven by active import fields. Uploaded template validation checks only the `data` sheet column count and applies `info` sheet category/sub-category logic if that sheet exists.</p>
    <?php if ($uploadedTemplate): ?>
        <p class="muted">Custom template uploaded at <?= e($uploadedTemplate['updated_at']); ?>.</p>
    <?php else: ?>
        <p class="muted">No custom template uploaded yet. Default generated template will be used.</p>
    <?php endif; ?>
    <form method="post" action="index.php" class="inline-form">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="save_asset_template_source">
        <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
        <label>Template users will download
            <select name="template_source">
                <?php foreach ($templateSourceOptions as $value => $label): ?>
                    <option value="<?= e($value); ?>" <?= $templateSource === $value ? 'selected' : ''; ?>><?= e($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Save</button>
    </form>
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
            <?php if ($uploadedTemplate): ?>
                <a href="asset_template.php?<?= e(http_build_query(['mode' => 'uploaded', 'segment_id' => $activeSegmentId])); ?>" class="button-link">Uploaded Template</a>
            <?php else: ?>
                <span class="button-link is-disabled" aria-disabled="true">Uploaded Template</span>
            <?php endif; ?>
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
    <form method="post" action="index.php" class="grid">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="save_asset_scope_visibility_settings">
        <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Office Type</th><th>My Office</th><th>Office Under Me</th></tr>
                </thead>
                <tbody>
                    <?php foreach ([2, 3, 4, 5] as $officeType): ?>
                        <?php $row = $scopeVisibilitySettings[$officeType] ?? ['show_my_office' => true, 'show_office_under_me' => $officeType !== 5]; ?>
                        <tr>
                            <td><?= e(asset_office_type_label($officeType)); ?></td>
                            <td><label class="inline-check"><input type="checkbox" name="scope_visibility[<?= e((string)$officeType); ?>][show_my_office]" value="1" <?= !empty($row['show_my_office']) ? 'checked' : ''; ?>> Visible</label></td>
                            <td><label class="inline-check"><input type="checkbox" name="scope_visibility[<?= e((string)$officeType); ?>][show_office_under_me]" value="1" <?= !empty($row['show_office_under_me']) ? 'checked' : ''; ?>> Visible</label></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
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
    <p class="hint">This control is segment specific and decides whether office-side users see the Asset Number column in asset tables.</p>
    <form method="post" action="index.php" class="inline-form">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="save_asset_number_visibility">
        <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
        <label><input type="checkbox" name="asset_number_visible_to_users" value="1" <?= $assetNumberVisibleToUsers ? 'checked' : ''; ?>> Show Asset Number for office users</label>
        <button type="submit">Save</button>
    </form>
</section>

<section class="card">
    <h2>Data Provider Visibility</h2>
    <p class="hint">This control is segment specific and applies to all asset tables in that segment.</p>
    <form method="post" action="index.php" class="inline-form">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="save_asset_data_provider_visibility">
        <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
        <label><input type="checkbox" name="show_data_provider_superadmin" value="1" <?= $dataProviderVisibleToSuperadmin ? 'checked' : ''; ?>> Show Data Provider column</label>
        <button type="submit">Save</button>
    </form>
</section>

<section class="card">
    <h2>Filter Index Repair</h2>
    <p class="hint">This rebuilds the precomputed filter-item index for `text`, `number`, `file`, and `bimh` fields so board filters can load faster without repeatedly scanning live data.</p>
    <div class="toolbar-row">
        <form method="post" action="index.php" class="inline-form">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="rebuild_asset_filter_index_segment">
            <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
            <button type="submit">Rebuild Active Segment</button>
        </form>
        <form method="post" action="index.php" class="inline-form">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="rebuild_asset_filter_index_all">
            <button type="submit">Rebuild All Segments</button>
        </form>
    </div>
    <p class="muted">Trackable fields in this segment: <?= e((string)count($trackableFilterFields)); ?></p>
</section>

<section class="card">
    <h2>Declare User Defined Common Fields</h2>
    <p class="hint">Use a parent segment field here as an inherited common field in this segment. Labels, types, rules, options, colors, and tutorials come automatically from the parent field. For `conditional`, the primary and secondary pair is created together. Manage `superadmin_defined` row content from the dedicated <a href="index.php?page=common_fields">Common Fields</a> page.</p>
    <?php if (!$commonRowsSupported): ?>
        <p class="hint">This segment cannot use common fields because it currently has more than one category.</p>
    <?php else: ?>
        <form method="post" action="index.php" class="inline-form" style="margin:12px 0 18px;">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="save_user_defined_common_segment_mode">
            <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
            <label>Row Policy
                <select name="user_defined_common_segment_mode">
                    <option value="<?= e($userDefinedCommonModeNone); ?>" <?= $userDefinedCommonModeSelection === $userDefinedCommonModeNone ? 'selected' : ''; ?>>None</option>
                    <?php foreach ($commonRowPolicyOptions as $policyValue => $policyLabel): ?>
                        <option value="<?= e((string)$policyValue); ?>" <?= $userDefinedCommonModeSelection === (string)$policyValue ? 'selected' : ''; ?>><?= e((string)$policyLabel); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit">Save Mode</button>
            <?php if (!$existingUserDefinedCommonProfile): ?>
                <span class="hint">This mode will be used when you declare the first inherited field in this segment.</span>
            <?php endif; ?>
        </form>
        <form method="post" action="index.php" class="grid compact-grid" data-user-common-declare>
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="create_user_defined_common_field_declaration">
            <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
            <input type="hidden" name="user_common_row_policy" value="<?= e((string)$userDefinedCommonModeSelection); ?>">
            <label>Parent Segment
                <select name="user_common_parent_segment_id" data-user-common-parent-segment required>
                    <option value="">Select segment</option>
                    <?php foreach ($segments as $segment): ?>
                        <?php if ((int)$segment['id'] === $activeSegmentId): continue; endif; ?>
                        <?php if (!asset_segment_common_rows_supported((int)$segment['id'])): continue; endif; ?>
                        <?php if (empty($commonParentFieldCandidatesBySegment[(int)$segment['id']])): continue; endif; ?>
                        <option value="<?= e((string)$segment['id']); ?>"><?= e((string)$segment['segment_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Parent Field
                <select name="user_common_parent_field_id" data-user-common-parent-field required>
                    <option value="">Select field</option>
                    <?php foreach ($commonParentFieldCandidatesBySegment as $parentSegmentId => $parentFields): ?>
                        <?php if ((int)$parentSegmentId === $activeSegmentId): continue; endif; ?>
                        <?php foreach ($parentFields as $parentField): ?>
                            <option value="<?= e((string)$parentField['id']); ?>" data-parent-segment="<?= e((string)$parentSegmentId); ?>">
                                <?= e((string)$parentField['label']); ?> (<?= e((string)$parentField['data_type']); ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Sequence
                <input type="number" name="user_common_sort_order" min="1" step="1" value="<?= e((string)(count($userDefinedCommonDeclarations) + 1)); ?>" required>
            </label>
            <div class="toolbar-row">
                <button type="submit" <?= $userDefinedDeclarationBlockedByMode ? 'disabled' : ''; ?>>Declare Field</button>
            </div>
        </form>
        <?php if ($userDefinedDeclarationBlockedByMode): ?>
            <p class="hint">Choose `Fixed` or `Addable` in Row Policy before declaring inherited `user_defined` common fields.</p>
        <?php endif; ?>
    <?php endif; ?>
    <?php if ($userDefinedCommonDeclarations !== []): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Sequence</th><th>Parent Segment</th><th>Parent Category</th><th>Parent Field</th><th>Inherited Field</th><th>Type</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($userDefinedCommonDeclarations as $declaration): ?>
                        <?php
                            $childField = $declaration['child_field'];
                            $binding = $declaration['binding'];
                            $parentField = $declaration['parent_field'];
                            $parentSegment = $declaration['parent_segment'];
                            $parentCategory = $declaration['parent_category'];
                        ?>
                        <tr>
                            <td><?= e((string)((int)($binding['sort_order'] ?? 0))); ?></td>
                            <td><?= e((string)($parentSegment['segment_name'] ?? 'Unknown')); ?></td>
                            <td><?= e((string)($parentCategory['name'] ?? 'Default')); ?></td>
                            <td><?= e((string)($parentField['label'] ?? 'Unknown')); ?></td>
                            <td><?= e((string)($childField['label'] ?? '')); ?></td>
                            <td><?= e((string)($childField['data_type'] ?? '')); ?></td>
                            <td>
                                <form method="post" action="index.php" class="inline-form">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="action" value="delete_user_defined_common_field_declaration">
                                    <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
                                    <input type="hidden" name="field_id" value="<?= e((string)$childField['id']); ?>">
                                    <button type="submit" class="btn-small btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
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
                                <form method="post" action="index.php" id="<?= e($formId); ?>" class="office-inline-form" data-common-supported-types="<?= e(json_encode(array_values($commonSupportedTypes), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>" enctype="multipart/form-data">
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
    <form method="post" action="index.php" class="grid asset-field-form" data-asset-field-form data-common-supported-types="<?= e(json_encode(array_values($commonSupportedTypes), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>" enctype="multipart/form-data">
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
        <label>Field Information
            <textarea name="field_information" rows="3" placeholder="Explain this field for users"></textarea>
            <span class="hint">Link button format: <code>&lt;a href="Medical Service Information.xlsx" download&gt;Download Form&lt;/a&gt;</code>. Store the file in a web-accessible project location, such as the project root or a public file folder, then use that relative path in <code>href</code>.</span>
        </label>
        <label>Tutorial URL
            <input type="url" name="video_tutorial_url" placeholder="https://www.youtube.com/watch?v=...">
        </label>
        <label>Column Fill Color
            <select name="fill_color">
                <?php foreach (asset_field_fill_color_palette() as $fillColorValue => $fillColorLabel): ?>
                    <option value="<?= e((string)$fillColorValue); ?>"><?= e((string)$fillColorLabel); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Hosted Tutorial Video (MP4 up to 500 MB)
            <input type="file" name="tutorial_video_file" accept="video/mp4">
            <span class="hint">UI upload supports MP4 up to 500 MB. Larger files upload by FileZilla to <code><?= e(asset_tutorial_video_manual_path_note()); ?></code> and register the filename below.</span>
        </label>
        <label>Hosted Tutorial Server Filename
            <input type="text" name="hosted_tutorial_video_filename" placeholder="example.mp4">
        </label>
        <div class="field-config-group" data-field-config="dropdown">
            <label>Dropdown Options
                <textarea name="options_text" rows="3" placeholder="One option per line"></textarea>
            </label>
        </div>
        <div class="field-config-group" data-field-config="number">
            <label>
                <span class="field-label-row">
                    <span>Number Format Rule</span>
                    <button
                        type="button"
                        class="field-help-button field-help-inline"
                        data-number-rule-help
                        data-help-title="Number Format Rules"
                        data-help-lines="<?= e(json_encode($numberRuleExamples, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>"
                        title="Number format rules"
                        aria-label="Number format rules"
                    >i</button>
                </span>
                <input type="text" name="number_format_rule" placeholder="8.2 or -*8.*2">
            </label>
        </div>
        <div class="field-config-group" data-field-config="text">
            <label>Text Max Characters
                <input type="number" name="text_max_length" min="1" step="1" placeholder="Leave blank for no limit">
            </label>
        </div>
        <div class="field-config-group" data-field-config="conditional">
            <label>Secondary Label
                <input type="text" name="secondary_label" placeholder="Secondary dropdown label">
            </label>
        </div>
        <div class="field-config-group" data-field-config="conditional">
            <label>Secondary Information
                <textarea name="secondary_field_information" rows="3" placeholder="Explain the secondary field"></textarea>
                <span class="hint">Link button format: <code>&lt;a href="Medical Service Information.xlsx" download&gt;Download Form&lt;/a&gt;</code>. Store the file in a web-accessible project location, such as the project root or a public file folder, then use that relative path in <code>href</code>.</span>
            </label>
        </div>
        <div class="field-config-group" data-field-config="conditional">
            <label>Secondary Tutorial URL
                <input type="url" name="secondary_video_tutorial_url" placeholder="https://www.youtube.com/watch?v=...">
            </label>
        </div>
        <div class="field-config-group" data-field-config="conditional">
            <label>Secondary Column Fill Color
                <select name="secondary_fill_color">
                    <?php foreach (asset_field_fill_color_palette() as $fillColorValue => $fillColorLabel): ?>
                        <option value="<?= e((string)$fillColorValue); ?>"><?= e((string)$fillColorLabel); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="field-config-group" data-field-config="conditional">
            <label>Secondary Hosted Tutorial Video (MP4 up to 500 MB)
                <input type="file" name="secondary_tutorial_video_file" accept="video/mp4">
                <span class="hint">UI upload supports MP4 up to 500 MB. Larger files upload by FileZilla to <code><?= e(asset_tutorial_video_manual_path_note()); ?></code> and register the filename below.</span>
            </label>
        </div>
        <div class="field-config-group" data-field-config="conditional">
            <label>Secondary Hosted Tutorial Server Filename
                <input type="text" name="secondary_hosted_tutorial_video_filename" placeholder="example.mp4">
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
        <label>Mandatory Scope
            <select name="mandatory_scope">
                <?php foreach (asset_mandatory_scope_options() as $scopeValue => $scopeLabel): ?>
                    <option value="<?= e((string)$scopeValue); ?>" <?= (int)$scopeValue === asset_mandatory_scope_optional() ? 'selected' : ''; ?>><?= e($scopeLabel); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label><input type="checkbox" name="is_displayed" value="1" checked> Show in tables</label>
        <label><input type="checkbox" name="is_import_enabled" value="1" checked> Allow in import</label>
        <label><input type="checkbox" name="is_unique" value="1"> Unique value</label>
        <label><input type="checkbox" name="is_download_token" value="1"> Declare as download token</label>
        <label>Filter Scope
            <select name="filter_scope">
                <?php foreach ($filterScopeOptions as $scopeValue => $scopeLabel): ?>
                    <option value="<?= e((string)$scopeValue); ?>" <?= (int)$scopeValue === asset_filter_scope_none() ? 'selected' : ''; ?>><?= e($scopeLabel); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="field-config-group field-common-row-wrap" data-common-row-toggle-wrap>
            <label><input type="checkbox" name="is_common_row_field" value="1" data-common-row-toggle data-common-row-existing="0" data-common-row-segment-supported="<?= $commonRowsSupported ? '1' : '0'; ?>" <?= $commonRowsSupported ? '' : 'disabled'; ?>> Common fixed-row field</label>
            <span class="hint">
                This checkbox is for `superadmin_defined` common fields only.
                <?php if (!$commonRowsSupported): ?>
                    Common fields are allowed only when this segment has zero or one category.
                <?php endif; ?>
            </span>
        </div>
        <div class="grid compact-grid field-common-row-settings hidden" data-common-row-section>
            <input type="hidden" name="common_row_config_present" value="1">
            <input type="hidden" name="common_definition_mode" value="<?= e(asset_common_definition_mode_superadmin_defined()); ?>">
            <input type="hidden" name="common_row_policy" value="<?= e(asset_common_row_policy_fixed()); ?>">
            <div class="hint field-common-row-hint">This field will be handled as a `superadmin_defined` common field. After saving it here, manage rows from the <a href="index.php?page=common_fields">Common Fields</a> page.</div>
        </div>
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
                        $commonBinding = $commonBindingsByFieldId[(int)$field['id']] ?? null;
                        $fieldSupportsCommonRows = isset($commonSupportedTypeLookup[(string)($field['data_type'] ?? '')]);
                        $fieldIsInheritedUserDefined = $commonBinding && (string)($commonBinding['definition_mode'] ?? '') === asset_common_definition_mode_user_defined();
                        $fieldIsCommonRow = $commonBinding || (int)($field['is_common_row_field'] ?? 0) === 1;
                        $fieldIsSuperadminCommonRow = $fieldIsCommonRow && !$fieldIsInheritedUserDefined;
                        $commonRowCheckboxDisabled = $fieldIsInheritedUserDefined || !$fieldSupportsCommonRows || (!$commonRowsSupported && !$fieldIsCommonRow);
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
                            <div class="field-config-group">
                                <label>Field Information
                                    <textarea form="<?= e($formId); ?>" class="inline-edit field-options-box" name="field_information" rows="3" placeholder="Explain this field"><?= e((string)($field['field_information'] ?? '')); ?></textarea>
                                    <span class="hint">Link button format: <code>&lt;a href="Medical Service Information.xlsx" download&gt;Download Form&lt;/a&gt;</code>. Store the file in a web-accessible project location, such as the project root or a public file folder, then use that relative path in <code>href</code>.</span>
                                </label>
                                <label>Tutorial URL
                                    <input form="<?= e($formId); ?>" class="inline-edit" type="url" name="video_tutorial_url" value="<?= e((string)($field['video_tutorial_url'] ?? '')); ?>" placeholder="https://www.youtube.com/watch?v=...">
                                </label>
                                <label>Column Fill Color
                                    <select form="<?= e($formId); ?>" class="inline-edit" name="fill_color">
                                        <?php foreach (asset_field_fill_color_palette() as $fillColorValue => $fillColorLabel): ?>
                                            <option value="<?= e((string)$fillColorValue); ?>" <?= asset_normalize_field_fill_color((string)($field['fill_color'] ?? '')) === (string)$fillColorValue ? 'selected' : ''; ?>><?= e((string)$fillColorLabel); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>Hosted Tutorial Video (MP4 up to 500 MB)
                                    <input form="<?= e($formId); ?>" class="inline-edit" type="file" name="tutorial_video_file" accept="video/mp4">
                                    <span class="hint">UI upload supports MP4 up to 500 MB. Larger files upload by FileZilla to <code><?= e(asset_tutorial_video_manual_path_note()); ?></code> and register the filename below.</span>
                                </label>
                                <label>Hosted Tutorial Server Filename
                                    <input form="<?= e($formId); ?>" class="inline-edit" type="text" name="hosted_tutorial_video_filename" value="" placeholder="example.mp4">
                                    <?php if (asset_field_has_hosted_tutorial($field)): ?><span class="hint">Current hosted video: <?= e((string)($field['hosted_tutorial_video_original_name'] ?? $field['hosted_tutorial_video_path'] ?? '')); ?></span><?php endif; ?>
                                </label>
                                <?php if (asset_field_has_hosted_tutorial($field)): ?><label class="inline-check"><input form="<?= e($formId); ?>" type="checkbox" name="remove_hosted_tutorial_video" value="1"> Remove hosted video</label><?php endif; ?>
                            </div>
                            <div class="field-config-group" data-field-config="number">
                                <label>
                                    <span class="field-label-row">
                                        <span>Number Format Rule</span>
                                        <button
                                            type="button"
                                            class="field-help-button field-help-inline"
                                            data-number-rule-help
                                            data-help-title="Number Format Rules"
                                            data-help-lines="<?= e(json_encode($numberRuleExamples, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>"
                                            title="Number format rules"
                                            aria-label="Number format rules"
                                        >i</button>
                                    </span>
                                    <input form="<?= e($formId); ?>" class="inline-edit" type="text" name="number_format_rule" value="<?= e((string)($field['number_format_rule'] ?? '')); ?>" placeholder="8.2 or -*8.*2">
                                </label>
                            </div>
                            <div class="field-config-group" data-field-config="text">
                                <label>Text Max Characters
                                    <input form="<?= e($formId); ?>" class="inline-edit" type="number" name="text_max_length" min="1" step="1" value="<?= e((string)($field['text_max_length'] ?? '')); ?>" placeholder="Leave blank for no limit">
                                </label>
                            </div>
                            <div class="field-config-group" data-field-config="conditional">
                                <label>Secondary Label
                                    <input form="<?= e($formId); ?>" class="inline-edit" type="text" name="secondary_label" value="<?= e((string)($conditionalChild['label'] ?? '')); ?>" placeholder="Secondary dropdown label">
                                </label>
                                <label>Secondary Information
                                    <textarea form="<?= e($formId); ?>" class="inline-edit field-options-box" name="secondary_field_information" rows="3" placeholder="Explain the secondary field"><?= e((string)($conditionalChild['field_information'] ?? '')); ?></textarea>
                                    <span class="hint">Link button format: <code>&lt;a href="Medical Service Information.xlsx" download&gt;Download Form&lt;/a&gt;</code>. Store the file in a web-accessible project location, such as the project root or a public file folder, then use that relative path in <code>href</code>.</span>
                                </label>
                                <label>Secondary Tutorial URL
                                    <input form="<?= e($formId); ?>" class="inline-edit" type="url" name="secondary_video_tutorial_url" value="<?= e((string)($conditionalChild['video_tutorial_url'] ?? '')); ?>" placeholder="https://www.youtube.com/watch?v=...">
                                </label>
                                <label>Secondary Column Fill Color
                                    <select form="<?= e($formId); ?>" class="inline-edit" name="secondary_fill_color">
                                        <?php foreach (asset_field_fill_color_palette() as $fillColorValue => $fillColorLabel): ?>
                                            <option value="<?= e((string)$fillColorValue); ?>" <?= asset_normalize_field_fill_color((string)($conditionalChild['fill_color'] ?? '')) === (string)$fillColorValue ? 'selected' : ''; ?>><?= e((string)$fillColorLabel); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>Secondary Hosted Tutorial Video (MP4 up to 500 MB)
                                    <input form="<?= e($formId); ?>" class="inline-edit" type="file" name="secondary_tutorial_video_file" accept="video/mp4">
                                    <span class="hint">UI upload supports MP4 up to 500 MB. Larger files upload by FileZilla to <code><?= e(asset_tutorial_video_manual_path_note()); ?></code> and register the filename below.</span>
                                </label>
                                <label>Secondary Hosted Tutorial Server Filename
                                    <input form="<?= e($formId); ?>" class="inline-edit" type="text" name="secondary_hosted_tutorial_video_filename" value="" placeholder="example.mp4">
                                    <?php if ($conditionalChild && asset_field_has_hosted_tutorial($conditionalChild)): ?><span class="hint">Current hosted video: <?= e((string)($conditionalChild['hosted_tutorial_video_original_name'] ?? $conditionalChild['hosted_tutorial_video_path'] ?? '')); ?></span><?php endif; ?>
                                </label>
                                <?php if ($conditionalChild && asset_field_has_hosted_tutorial($conditionalChild)): ?><label class="inline-check"><input form="<?= e($formId); ?>" type="checkbox" name="remove_secondary_hosted_tutorial_video" value="1"> Remove secondary hosted video</label><?php endif; ?>
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
                                <form method="post" action="index.php" id="<?= e($formId); ?>" class="office-inline-form" enctype="multipart/form-data">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="action" value="update_asset_field">
                                    <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
                                    <input type="hidden" name="field_id" value="<?= e((string)$field['id']); ?>">
                                    <input type="hidden" name="field_key" value="<?= e($field['field_key']); ?>">
                                    <label>Mandatory Scope
                                        <select form="<?= e($formId); ?>" class="inline-edit" name="mandatory_scope">
                                            <?php foreach (asset_mandatory_scope_options() as $scopeValue => $scopeLabel): ?>
                                                <option value="<?= e((string)$scopeValue); ?>" <?= asset_field_mandatory_scope($field) === (int)$scopeValue ? 'selected' : ''; ?>><?= e($scopeLabel); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label class="inline-check"><input form="<?= e($formId); ?>" type="checkbox" name="is_displayed" value="1" <?= (int)$field['is_displayed'] === 1 ? 'checked' : ''; ?>> Display</label>
                                    <label class="inline-check"><input form="<?= e($formId); ?>" type="checkbox" name="is_import_enabled" value="1" <?= (int)$field['is_import_enabled'] === 1 ? 'checked' : ''; ?> <?= in_array($field['data_type'], ['file'], true) ? 'disabled' : ''; ?>> Import</label>
                                    <label class="inline-check"><input form="<?= e($formId); ?>" type="checkbox" name="is_unique" value="1" <?= (int)($field['is_unique'] ?? 0) === 1 ? 'checked' : ''; ?> <?= in_array($field['data_type'], ['file', 'conditional'], true) ? 'disabled' : ''; ?>> Unique</label>
                                    <label class="inline-check"><input form="<?= e($formId); ?>" type="checkbox" name="is_download_token" value="1" <?= (int)($field['is_download_token'] ?? 0) === 1 ? 'checked' : ''; ?>> Token</label>
                                    <label>Filter Scope
                                        <select form="<?= e($formId); ?>" class="inline-edit" name="filter_scope">
                                            <?php foreach ($filterScopeOptions as $scopeValue => $scopeLabel): ?>
                                                <option value="<?= e((string)$scopeValue); ?>" <?= asset_normalize_filter_scope($field['filter_scope'] ?? (($field['is_filter_enabled'] ?? 0) ? asset_filter_scope_all() : asset_filter_scope_none())) === (int)$scopeValue ? 'selected' : ''; ?>><?= e($scopeLabel); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label class="inline-check" data-common-row-toggle-wrap>
                                        <input form="<?= e($formId); ?>" type="checkbox" name="is_common_row_field" value="1" data-common-row-toggle data-common-row-existing="<?= $fieldIsSuperadminCommonRow ? '1' : '0'; ?>" data-common-row-segment-supported="<?= $commonRowsSupported ? '1' : '0'; ?>" <?= $fieldIsSuperadminCommonRow ? 'checked' : ''; ?> <?= $commonRowCheckboxDisabled ? 'disabled' : ''; ?>>
                                        Common row
                                    </label>
                                    <div class="grid compact-grid field-common-row-settings<?= $fieldIsSuperadminCommonRow ? '' : ' hidden'; ?>" data-common-row-section>
                                        <input form="<?= e($formId); ?>" type="hidden" name="common_row_config_present" value="1">
                                        <input form="<?= e($formId); ?>" type="hidden" name="common_definition_mode" value="<?= e(asset_common_definition_mode_superadmin_defined()); ?>">
                                        <input form="<?= e($formId); ?>" type="hidden" name="common_row_policy" value="<?= e((string)($commonBinding['row_policy'] ?? asset_common_row_policy_fixed())); ?>">
                                        <div class="hint">
                                            <?php if ($fieldIsInheritedUserDefined): ?>
                                                This is an inherited `user_defined` field. Manage it from the “Declare User Defined Common Fields” card above.
                                            <?php else: ?>
                                                This field is handled as `superadmin_defined`. Manage row content from the <a href="index.php?page=common_fields">Common Fields</a> page.
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if (!$fieldSupportsCommonRows || (!$commonRowsSupported && !$fieldIsCommonRow)): ?><div class="hint">Common rows support only: <?= e(implode(', ', $commonSupportedTypes)); ?>, and only in segments with zero or one category.</div><?php endif; ?>
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
                                <?php if (asset_filter_index_field_is_trackable($field)): ?>
                                    <form method="post" action="index.php" class="inline-form">
                                        <?= csrf_input(); ?>
                                        <input type="hidden" name="action" value="rebuild_asset_filter_index_field">
                                        <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
                                        <input type="hidden" name="field_id" value="<?= e((string)$field['id']); ?>">
                                        <button type="submit" class="btn-small">Rebuild Filter Index</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <div class="hint">Use one line per dropdown option. File rules apply only when type is set to file. Conditional fields save two linked dropdown columns together.</div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="modal-backdrop" id="number-rule-help-modal" aria-hidden="true">
    <div class="modal-card field-help-modal-card" role="dialog" aria-modal="true" aria-labelledby="number-rule-help-title">
        <div class="flash-modal-head">
            <h3 id="number-rule-help-title">Number Format Rules</h3>
            <button type="button" class="welcome-modal-close modal-close" data-close="number-rule-help-modal" aria-label="Close">×</button>
        </div>
        <div class="field-help-content">
            <div id="number-rule-help-body"></div>
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-close" data-close="number-rule-help-modal">Close</button>
        </div>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
