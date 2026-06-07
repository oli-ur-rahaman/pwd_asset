<?php
require __DIR__ . '/header.php';

$user = current_user();
$info = get_info_row();
$canModifyAssets = can_modify_office_assets($user);
$canManageSuperadmin = can_manage_superadmin_scope($user);
$activeSegmentId = asset_active_segment_id((int)request_str('segment_id', '0'));
$activeSegment = asset_active_segment($activeSegmentId, true);
$segments = get_asset_segments();
$showSegmentSelector = count($segments) > 1;
$currentOfficeViewScope = request_str('office_view_scope', 'my_office');
$hasUnderMeScope = office_user_has_under_me_scope($user);
if (!$hasUnderMeScope) {
    $currentOfficeViewScope = 'my_office';
}
$isUnderMeView = !is_superadmin() && $currentOfficeViewScope === 'office_under_me';
$showAssetNumber = is_superadmin() || asset_number_visible_to_users();
$showActionColumn = !is_superadmin() && $canModifyAssets && !$isUnderMeView;
$fieldMap = asset_field_map_for_segment(false, $activeSegmentId);
$fields = get_asset_fields(false, $activeSegmentId);
$categories = get_asset_categories(false, $activeSegmentId);
$subcategoryEnabled = asset_subcategory_enabled($activeSegmentId);
$subcategories = $subcategoryEnabled ? get_asset_subcategories(null, true, $activeSegmentId) : [];
$importReviewSubcategories = $subcategoryEnabled ? get_asset_subcategories(null, false, $activeSegmentId) : [];
$subcategoryByCategory = [];
foreach ($subcategories as $subcategory) {
    $subcategoryByCategory[(int)$subcategory['category_id']][] = $subcategory;
}

$selectedZone = (int)request_str('zone_id', '0');
$selectedCircle = (int)request_str('circle_id', '0');
$selectedDivision = (int)request_str('division_id', '0');
$selectedSubdivision = (int)request_str('subdivision_id', '0');
$filters = [
    'segment_id' => $activeSegmentId,
    'zone_id' => $selectedZone,
    'circle_id' => $selectedCircle,
    'division_id' => $selectedDivision,
    'subdivision_id' => $selectedSubdivision,
    'category_id' => (int)request_str('category_id', '0'),
];
if ($subcategoryEnabled) {
    $filters['subcategory_id'] = (int)request_str('subcategory_id', '0');
}
$selectedOfficeType = 0;
$selectedOfficeId = 0;
if ($selectedSubdivision > 0) {
    $selectedOfficeType = 5;
    $selectedOfficeId = $selectedSubdivision;
} elseif ($selectedDivision > 0) {
    $selectedOfficeType = 4;
    $selectedOfficeId = $selectedDivision;
} elseif ($selectedCircle > 0) {
    $selectedOfficeType = 3;
    $selectedOfficeId = $selectedCircle;
} elseif ($selectedZone > 0) {
    $selectedOfficeType = 2;
    $selectedOfficeId = $selectedZone;
}
$filters['office_view_scope'] = $currentOfficeViewScope;
$sortColumn = request_str('sort_col', '');
$sortDirection = strtolower(request_str('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
$filters['sort_col'] = $sortColumn;
$filters['sort_dir'] = $sortDirection;
$baseScopeAssets = get_assets(['office_view_scope' => $currentOfficeViewScope, 'segment_id' => $activeSegmentId], $user);
$declaration = null;
$officeSummary = null;
if (!is_superadmin()) {
    $ctx = current_office_context($user);
    if ($ctx) {
        $declaration = get_asset_declaration($ctx['office_type'], $ctx['office_id'], $activeSegmentId);
        $officeSummary = get_office_activity_summary($ctx['office_type'], $ctx['office_id'], $activeSegmentId);
    }
}

$editAssetId = (int)request_str('edit_asset', '0');
$editingAsset = $editAssetId > 0 ? get_asset($editAssetId, true) : null;
$editingAsset = ($editingAsset && (int)($editingAsset['segment_id'] ?? 0) === $activeSegmentId) ? $editingAsset : null;
$editValues = $editingAsset['values'] ?? [];
$editFiles = $editingAsset['files'] ?? [];
$review = $_SESSION['asset_import_review'] ?? null;

$zones = db()->query('SELECT id, office_name FROM zones ORDER BY office_name')->fetchAll();
$circles = db()->query('SELECT id, office_name, zone_id FROM circles ORDER BY office_name')->fetchAll();
$divisions = db()->query('SELECT id, office_name, zone_id, circle_id FROM divisions ORDER BY office_name')->fetchAll();
$subdivisions = db()->query('SELECT id, office_name, zone_id, circle_id, division_id FROM subdivisions ORDER BY office_name')->fetchAll();
$uiFieldLabels = [];
foreach ($fields as $field) {
    $rawLabel = trim((string)($field['label'] ?? ''));
    $parts = preg_split('/\s*\/\s*/u', $rawLabel);
    $uiFieldLabels[$field['field_key']] = trim((string)($parts[0] ?? $rawLabel));
}
$availableTableColumns = asset_table_available_columns($fields, $uiFieldLabels, $currentOfficeViewScope, $activeSegmentId);
$columnPreferenceMap = get_asset_table_column_preferences((int)$user['id'], $activeSegmentId);
$filterCatalog = build_asset_filter_catalog($baseScopeAssets, $fields, $activeSegmentId);
$visibleFilterFields = asset_filter_visible_fields($fields, $baseScopeAssets);
$showCategoryFilter = count($filterCatalog['categories']) > 1;
$showSubcategoryFilter = $subcategoryEnabled && count($filterCatalog['subcategories']) > 0;
$showZoneFilter = is_superadmin();
$showCircleFilter = is_superadmin() || ($isUnderMeView && (int)($user['office_type'] ?? 0) === 2);
$showDivisionFilter = is_superadmin() || ($isUnderMeView && in_array((int)($user['office_type'] ?? 0), [2, 3], true));
$showSubdivisionFilter = is_superadmin() || ($isUnderMeView && in_array((int)($user['office_type'] ?? 0), [2, 3, 4], true));
$fieldFilterSelections = [];
foreach ($fields as $field) {
    if ((int)($field['active_status'] ?? 0) !== 1) {
        continue;
    }
    $fieldKey = (string)$field['field_key'];
    $filterKey = 'field_filter_' . $fieldKey;
    $filters[$filterKey] = request_str($filterKey, '');
    $fieldFilterSelections[$fieldKey] = (string)$filters[$filterKey];
    if ((string)$field['data_type'] === 'date') {
        $filters[$filterKey . '_from'] = request_str($filterKey . '_from', '');
        $filters[$filterKey . '_to'] = request_str($filterKey . '_to', '');
    }
}
$groupedAssets = get_assets_grouped_by_category($filters, $user);
$categoryNameById = [];
foreach ($categories as $category) {
    $categoryNameById[(int)$category['id']] = (string)$category['name'];
}
$importFieldDefs = [];
$uniqueValueMap = asset_unique_existing_values_map($activeSegmentId);
foreach ($fields as $field) {
    if ((int)$field['is_import_enabled'] !== 1 || (int)$field['active_status'] !== 1) {
        continue;
    }
    $conditionalMap = asset_is_conditional_primary($field) ? asset_decode_conditional_map($field) : [];
    $importFieldDefs[] = [
        'field_key' => (string)$field['field_key'],
        'label' => (string)($uiFieldLabels[$field['field_key']] ?? $field['label']),
        'data_type' => (string)$field['data_type'],
        'required' => (int)$field['is_required'] === 1,
        'is_unique' => (int)($field['is_unique'] ?? 0) === 1,
        'number_format_rule' => (string)($field['number_format_rule'] ?? ''),
        'secondary_of_field_key' => null,
        'conditional_map' => $conditionalMap,
        'options' => array_map(
            static fn(array $option): string => (string)$option['option_value'],
            get_asset_field_options((int)$field['id'])
        ),
        'existing_values' => $uniqueValueMap[(string)$field['field_key']] ?? [],
    ];
    $parentId = (int)($field['secondary_of_field_id'] ?? 0);
    if ($parentId > 0) {
        foreach ($fields as $candidateField) {
            if ((int)$candidateField['id'] === $parentId) {
                $importFieldDefs[count($importFieldDefs) - 1]['secondary_of_field_key'] = (string)$candidateField['field_key'];
                break;
            }
        }
    }
}
$downloadFilters = [
    'segment_id' => $activeSegmentId,
    'office_type' => $selectedOfficeType,
    'office_id' => $selectedOfficeId,
    'category_id' => (int)($filters['category_id'] ?? 0),
    'office_view_scope' => $currentOfficeViewScope,
    'zone_id' => $selectedZone,
    'circle_id' => $selectedCircle,
    'division_id' => $selectedDivision,
    'subdivision_id' => $selectedSubdivision,
];
if ($subcategoryEnabled) {
    $downloadFilters['subcategory_id'] = (int)($filters['subcategory_id'] ?? 0);
}
$downloadFilters = array_merge($downloadFilters, $fieldFilterSelections);
$defaultCategoryId = !$editingAsset && count($categories) === 1 ? (int)$categories[0]['id'] : 0;
$historyAssetId = (int)request_str('asset_history', '0');
$historyAsset = $historyAssetId > 0 ? get_asset($historyAssetId, true) : null;
$historyAsset = ($historyAsset && (int)($historyAsset['segment_id'] ?? 0) === $activeSegmentId) ? $historyAsset : null;
$historyLogs = [];
if ($historyAsset && user_can_view_asset($user, $historyAsset, $currentOfficeViewScope)) {
    $historyLogs = get_asset_activity_logs($historyAssetId);
} else {
    $historyAsset = null;
}
$fileIconMeta = static function (string $originalName): array {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $chipClass = 'is-file';
    $iconText = $ext !== '' ? strtoupper($ext) : 'FILE';
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true)) {
        $chipClass = 'is-image';
        $iconText = 'IMG';
    } elseif ($ext === 'pdf') {
        $chipClass = 'is-pdf';
        $iconText = 'PDF';
    } elseif (in_array($ext, ['doc', 'docx'], true)) {
        $chipClass = 'is-doc';
        $iconText = 'DOC';
    } elseif (in_array($ext, ['xls', 'xlsx'], true)) {
        $chipClass = 'is-sheet';
        $iconText = 'XLS';
    } elseif ($ext === 'txt') {
        $chipClass = 'is-text';
        $iconText = 'TXT';
    }
    return ['class' => $chipClass, 'icon' => $iconText];
};
$filterPickerValue = static function (array $options, string|int $selectedValue): string {
    $lookupKey = (string)$selectedValue;
    foreach ($options as $option) {
        if ((string)($option['value'] ?? '') === $lookupKey) {
            return (string)($option['label'] ?? '');
        }
    }
    return '';
};
$renderFilterPicker = static function (string $name, string $label, array $options, string|int $selectedValue = '', array $attributes = []) use ($filterPickerValue): void {
    $normalizedSelectedValue = ((string)$selectedValue === '0') ? '' : (string)$selectedValue;
    $selectedText = $filterPickerValue($options, $normalizedSelectedValue);
    $pickerId = 'filter-picker-' . preg_replace('/[^a-zA-Z0-9_-]+/', '-', $name);
    $wrapperAttrs = '';
    foreach ($attributes as $attrName => $attrValue) {
        if ($attrValue === null || $attrValue === '') {
            continue;
        }
        $wrapperAttrs .= ' ' . $attrName . '="' . e((string)$attrValue) . '"';
    }
    ?>
    <label><?= e($label); ?>
        <div class="filter-picker" id="<?= e($pickerId); ?>" data-filter-picker<?= $wrapperAttrs; ?>>
            <input type="hidden" name="<?= e($name); ?>" value="<?= e($normalizedSelectedValue); ?>" data-filter-picker-value>
            <input type="text" value="<?= e($selectedText); ?>" placeholder="All" autocomplete="off" data-filter-picker-input>
            <div class="filter-picker-menu" data-filter-picker-menu>
                <button type="button" class="filter-picker-option" data-option-value="" data-option-label="All">All</button>
                <?php foreach ($options as $option): ?>
                    <button
                        type="button"
                        class="filter-picker-option"
                        data-option-value="<?= e((string)($option['value'] ?? '')); ?>"
                        data-option-label="<?= e((string)($option['label'] ?? '')); ?>"
                        <?php foreach (($option['meta'] ?? []) as $metaKey => $metaValue): ?>
                            data-<?= e((string)$metaKey); ?>="<?= e((string)$metaValue); ?>"
                        <?php endforeach; ?>
                    ><?= e((string)($option['label'] ?? '')); ?></button>
                <?php endforeach; ?>
            </div>
        </div>
    </label>
    <?php
};
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
                <?php if ($canModifyAssets): ?>
                    <form method="post" action="index.php" class="inline-form">
                        <?= csrf_input(); ?>
                        <input type="hidden" name="action" value="asset_declare">
                        <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
                        <button type="submit" class="hero-declare-button" <?= !empty($declaration['declared_status']) ? 'disabled' : ''; ?>>Declare as Completed</button>
                    </form>
                <?php else: ?>
                    <span class="muted">View-only access</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php if (!empty($declaration['declared_status'])): ?>
        <p class="hint">Declared at: <?= e((string)$declaration['declared_at']); ?></p>
    <?php endif; ?>
</section>

<?php if ($showSegmentSelector): ?>
<section class="card">
    <div class="toolbar-row scope-switch-row">
        <?php foreach ($segments as $segment): ?>
            <?php
                $segmentParams = ['page' => 'board', 'segment_id' => (int)$segment['id']];
                if (!is_superadmin()) {
                    $segmentParams['office_view_scope'] = $currentOfficeViewScope;
                }
            ?>
            <a href="index.php?<?= e(http_build_query($segmentParams)); ?>" class="button-link<?= $activeSegmentId === (int)$segment['id'] ? ' is-active' : ''; ?>">
                <?= e((string)$segment['segment_name']); ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!is_superadmin()): ?>
<section class="card">
    <div class="toolbar-row scope-switch-row">
        <a href="index.php?<?= e(http_build_query(['page' => 'board', 'office_view_scope' => 'my_office', 'segment_id' => $activeSegmentId])); ?>" class="button-link<?= !$isUnderMeView ? ' is-active' : ''; ?>">My Office</a>
        <?php if ($hasUnderMeScope): ?>
            <a href="index.php?<?= e(http_build_query(['page' => 'board', 'office_view_scope' => 'office_under_me', 'segment_id' => $activeSegmentId])); ?>" class="button-link<?= $isUnderMeView ? ' is-active' : ''; ?>">Office Under Me</a>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php if (false && is_superadmin()): ?>
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
        <label>Sub-division
            <select name="subdivision_id">
                <option value="0">All</option>
                <?php foreach ($subdivisions as $subdivision): ?>
                    <option value="<?= e((string)$subdivision['id']); ?>" data-zone="<?= e((string)$subdivision['zone_id']); ?>" data-circle="<?= e((string)$subdivision['circle_id']); ?>" data-division="<?= e((string)$subdivision['division_id']); ?>" <?= $selectedSubdivision === (int)$subdivision['id'] ? 'selected' : ''; ?>><?= e($subdivision['office_name']); ?></option>
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
        <?php if ($subcategoryEnabled): ?>
            <label>Sub-category
                <select name="subcategory_id">
                    <option value="0">All</option>
                    <?php foreach ($subcategories as $subcategory): ?>
                        <option value="<?= e((string)$subcategory['id']); ?>" data-category="<?= e((string)$subcategory['category_id']); ?>" <?= (int)($filters['subcategory_id'] ?? 0) === (int)$subcategory['id'] ? 'selected' : ''; ?>><?= e($subcategory['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
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

<section class="card">
    <h2>Filters</h2>
    <form method="get" action="index.php" id="asset-filters" class="grid board-filters-grid">
        <input type="hidden" name="page" value="board">
        <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
        <?php if (!is_superadmin()): ?><input type="hidden" name="office_view_scope" value="<?= e($currentOfficeViewScope); ?>"><?php endif; ?>
        <?php if ($showCategoryFilter): ?>
            <?php
                $categoryPickerOptions = array_map(
                    static fn(array $category): array => ['value' => (string)$category['id'], 'label' => (string)$category['name']],
                    array_values($filterCatalog['categories'])
                );
                $renderFilterPicker('category_id', 'Category', $categoryPickerOptions, (string)($filters['category_id'] ?? '0'));
            ?>
        <?php endif; ?>
        <?php if ($showSubcategoryFilter): ?>
            <?php
                $subcategoryPickerOptions = array_map(
                    static fn(array $subcategory): array => [
                        'value' => (string)$subcategory['id'],
                        'label' => (string)$subcategory['name'],
                        'meta' => ['category' => (string)$subcategory['category_id']],
                    ],
                    array_values($filterCatalog['subcategories'])
                );
                $renderFilterPicker('subcategory_id', 'Sub-category', $subcategoryPickerOptions, (string)($filters['subcategory_id'] ?? '0'));
            ?>
        <?php endif; ?>
        <?php if ($showZoneFilter): ?>
            <?php
                $zonePickerOptions = array_map(
                    static fn(array $zone): array => ['value' => (string)$zone['id'], 'label' => (string)$zone['name']],
                    array_values($filterCatalog['zones'])
                );
                $renderFilterPicker('zone_id', 'Zone', $zonePickerOptions, (string)$selectedZone);
            ?>
        <?php endif; ?>
        <?php if ($showCircleFilter): ?>
            <?php
                $circlePickerOptions = array_map(
                    static fn(array $circle): array => [
                        'value' => (string)$circle['id'],
                        'label' => (string)$circle['name'],
                        'meta' => ['zone' => (string)$circle['zone_id']],
                    ],
                    array_values($filterCatalog['circles'])
                );
                $renderFilterPicker('circle_id', 'Circle', $circlePickerOptions, (string)$selectedCircle);
            ?>
        <?php endif; ?>
        <?php if ($showDivisionFilter): ?>
            <?php
                $divisionPickerOptions = array_map(
                    static fn(array $division): array => [
                        'value' => (string)$division['id'],
                        'label' => (string)$division['name'],
                        'meta' => ['zone' => (string)$division['zone_id'], 'circle' => (string)$division['circle_id']],
                    ],
                    array_values($filterCatalog['divisions'])
                );
                $renderFilterPicker('division_id', 'Division', $divisionPickerOptions, (string)$selectedDivision);
            ?>
        <?php endif; ?>
        <?php if ($showSubdivisionFilter): ?>
            <?php
                $subdivisionPickerOptions = array_map(
                    static fn(array $subdivision): array => [
                        'value' => (string)$subdivision['id'],
                        'label' => (string)$subdivision['name'],
                        'meta' => ['zone' => (string)$subdivision['zone_id'], 'circle' => (string)$subdivision['circle_id'], 'division' => (string)$subdivision['division_id']],
                    ],
                    array_values($filterCatalog['subdivisions'])
                );
                $renderFilterPicker('subdivision_id', 'Sub-division', $subdivisionPickerOptions, (string)$selectedSubdivision);
            ?>
        <?php endif; ?>
        <?php foreach ($fields as $field): ?>
            <?php
                if ((int)($field['active_status'] ?? 0) !== 1 || empty($visibleFilterFields[$field['field_key']]) || asset_is_conditional_secondary($field)) {
                    continue;
                }
                $fieldKey = (string)$field['field_key'];
                $fieldType = (string)$field['data_type'];
                $fieldLabel = (string)($uiFieldLabels[$fieldKey] ?? $field['label']);
                $filterKey = 'field_filter_' . $fieldKey;
                $catalogField = $filterCatalog['fields'][$fieldKey] ?? null;
            ?>
            <?php if ($fieldType === 'date'): ?>
                <label><?= e($fieldLabel); ?> From
                    <input type="date" name="<?= e($filterKey . '_from'); ?>" value="<?= e((string)($filters[$filterKey . '_from'] ?? '')); ?>">
                </label>
                <label><?= e($fieldLabel); ?> To
                    <input type="date" name="<?= e($filterKey . '_to'); ?>" value="<?= e((string)($filters[$filterKey . '_to'] ?? '')); ?>">
                </label>
            <?php elseif ($fieldType === 'conditional'): ?>
                <?php $childField = get_asset_conditional_child_field((int)$field['id']); ?>
                <?php
                    $conditionalPrimaryOptions = [];
                    foreach (($catalogField['options'] ?? []) as $optionValue => $optionLabel) {
                        $conditionalPrimaryOptions[] = ['value' => (string)$optionValue, 'label' => (string)$optionLabel];
                    }
                    $renderFilterPicker(
                        $filterKey,
                        $fieldLabel,
                        $conditionalPrimaryOptions,
                        (string)($filters[$filterKey] ?? ''),
                        [
                            'data-filter-conditional-primary' => '1',
                            'data-filter-conditional-child' => (string)($childField['field_key'] ?? ''),
                            'data-filter-conditional-map' => json_encode($catalogField['secondary_options_map'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ]
                    );
                ?>
                <?php if ($childField): ?>
                    <?php $childFilterKey = 'field_filter_' . $childField['field_key']; ?>
                    <?php
                        $renderFilterPicker(
                            $childFilterKey,
                            (string)($uiFieldLabels[$childField['field_key']] ?? $childField['label']),
                            [],
                            (string)($filters[$childFilterKey] ?? ''),
                            ['data-filter-conditional-secondary' => (string)$fieldKey]
                        );
                    ?>
                <?php endif; ?>
            <?php elseif (!empty($catalogField['options'])): ?>
                <?php
                    $fieldPickerOptions = [];
                    foreach ($catalogField['options'] as $optionValue => $optionLabel) {
                        $fieldPickerOptions[] = ['value' => (string)$optionValue, 'label' => (string)$optionLabel];
                    }
                    $renderFilterPicker($filterKey, $fieldLabel, $fieldPickerOptions, (string)($filters[$filterKey] ?? ''));
                ?>
            <?php endif; ?>
        <?php endforeach; ?>
        <button type="submit">Apply</button>
        <a href="index.php?<?= e(http_build_query(array_filter([
            'page' => 'board',
            'segment_id' => $activeSegmentId,
            'office_view_scope' => !is_superadmin() ? $currentOfficeViewScope : null,
        ], static fn($value) => $value !== null && $value !== ''))); ?>" class="button-link">Reset</a>
    </form>
</section>

<?php if (!is_superadmin()): ?>
<section class="card">
    <div class="toolbar-row">
        <?php if ($canModifyAssets && !$isUnderMeView): ?>
            <button type="button" data-modal="asset-modal">+Add Asset</button>
            <button type="button" data-modal="import-modal">Bulk Entry</button>
        <?php endif; ?>
        <?php if (!$isUnderMeView): ?>
            <a href="asset_template.php?<?= e(http_build_query(['segment_id' => $activeSegmentId])); ?>" class="button-link">Excel Template</a>
        <?php endif; ?>
        <form method="post" action="index.php" class="inline-form">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="asset_download_data">
            <input type="hidden" name="office_view_scope" value="<?= e($currentOfficeViewScope); ?>">
            <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
            <button type="submit" class="btn-secondary">Download Data</button>
        </form>
    </div>
</section>
<?php else: ?>
<section class="card">
    <div class="toolbar-row">
        <a href="asset_template.php?<?= e(http_build_query(['segment_id' => $activeSegmentId])); ?>" class="button-link">Excel Template</a>
        <button type="button" data-modal="superadmin-download-modal" class="btn-secondary">Download Data</button>
    </div>
</section>
<?php endif; ?>

<?php if (!is_superadmin() && $canModifyAssets && !$isUnderMeView): ?>
<form method="post" action="index.php" class="asset-delete-form" id="asset-delete-form">
    <?= csrf_input(); ?>
    <input type="hidden" name="action" value="asset_bulk_delete">
    <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
    <input type="hidden" name="office_view_scope" value="<?= e($currentOfficeViewScope); ?>">
</form>
<?php endif; ?>
    <section class="board-grid asset-category-grid">
        <?php foreach ($groupedAssets as $group): ?>
            <?php
                $category = $group['category'];
                $assets = $group['assets'];
                $prefCategoryId = asset_table_preference_category_id((int)$category['id'], $currentOfficeViewScope);
                $visibleColumnKeys = resolve_asset_table_visible_column_keys($prefCategoryId, $availableTableColumns, $columnPreferenceMap);
                $visibleFieldCount = 0;
                foreach ($fields as $field) {
                    if ((int)$field['is_displayed'] === 1 && (int)$field['active_status'] === 1 && !empty($visibleColumnKeys[$field['field_key']])) {
                        $visibleFieldCount++;
                    }
                }
            ?>
            <section class="card operational-budget-card">
                <div class="card-head">
                    <h2><?= e($category['name']); ?></h2>
                    <div class="card-head-actions">
                        <div class="muted"><?= count($assets); ?> asset(s)</div>
                        <a href="index.php?<?= e(http_build_query(array_diff_key(array_merge($_GET, ['page' => 'board', 'office_view_scope' => $currentOfficeViewScope, 'segment_id' => $activeSegmentId]), ['sort_col' => true, 'sort_dir' => true]))); ?>" class="btn-small button-link">Refresh</a>
                        <button type="button" class="btn-small" data-modal="columns-modal-<?= (int)$category['id']; ?>">Columns</button>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <?php if (!is_superadmin() && $canModifyAssets && !$isUnderMeView): ?><th><input type="checkbox" class="select-all"></th><?php endif; ?>
                                <?php
                                    $sortParams = $_GET;
                                    $sortParams['page'] = 'board';
                                    $sortParams['office_view_scope'] = $currentOfficeViewScope;
                                    $sortParams['segment_id'] = $activeSegmentId;
                                    $headerSortUrl = static function (string $columnKey) use ($sortParams, $sortColumn, $sortDirection): string {
                                        $params = $sortParams;
                                        $params['sort_col'] = $columnKey;
                                        $params['sort_dir'] = ($sortColumn === $columnKey && $sortDirection === 'asc') ? 'desc' : 'asc';
                                        return 'index.php?' . http_build_query($params);
                                    };
                                    $sortIndicator = static function (string $columnKey) use ($sortColumn, $sortDirection): string {
                                        if ($sortColumn !== $columnKey) {
                                            return '';
                                        }
                                        return $sortDirection === 'desc' ? ' ↓' : ' ↑';
                                    };
                                    $sortClass = static function (string $columnKey) use ($sortColumn): string {
                                        return $sortColumn === $columnKey ? ' sortable-head is-active' : ' sortable-head';
                                    };
                                ?>
                                <th class="<?= $sortColumn === '__sl' ? 'sort-active' : ''; ?>"><a href="<?= e($headerSortUrl('__sl')); ?>" class="<?= trim($sortClass('__sl')); ?>">SL No<?= e($sortIndicator('__sl')); ?></a></th>
                                <?php if ($showAssetNumber && !empty($visibleColumnKeys['asset_number'])): ?><th class="<?= $sortColumn === 'asset_number' ? 'sort-active' : ''; ?>"><a href="<?= e($headerSortUrl('asset_number')); ?>" class="<?= trim($sortClass('asset_number')); ?>">Asset Number<?= e($sortIndicator('asset_number')); ?></a></th><?php endif; ?>
                                <?php if ((is_superadmin() || $isUnderMeView) && !empty($visibleColumnKeys['office_name'])): ?><th class="<?= $sortColumn === 'office_name' ? 'sort-active' : ''; ?>"><a href="<?= e($headerSortUrl('office_name')); ?>" class="<?= trim($sortClass('office_name')); ?>">Office<?= e($sortIndicator('office_name')); ?></a></th><?php endif; ?>
                                <?php if ($subcategoryEnabled && !empty($visibleColumnKeys['subcategory_name'])): ?><th class="<?= $sortColumn === 'subcategory_name' ? 'sort-active' : ''; ?>"><a href="<?= e($headerSortUrl('subcategory_name')); ?>" class="<?= trim($sortClass('subcategory_name')); ?>">Sub-category<?= e($sortIndicator('subcategory_name')); ?></a></th><?php endif; ?>
                                <?php if (!empty($visibleColumnKeys['data_provider'])): ?><th class="<?= $sortColumn === 'data_provider' ? 'sort-active' : ''; ?>"><a href="<?= e($headerSortUrl('data_provider')); ?>" class="<?= trim($sortClass('data_provider')); ?>">Data Provider<?= e($sortIndicator('data_provider')); ?></a></th><?php endif; ?>
                                <?php foreach ($fields as $field): ?>
                                    <?php if ((int)$field['is_displayed'] === 1 && (int)$field['active_status'] === 1 && !empty($visibleColumnKeys[$field['field_key']])): ?>
                                        <?php $fieldSortKey = (string)$field['field_key']; ?>
                                        <th class="<?= $sortColumn === $fieldSortKey ? 'sort-active' : ''; ?>"><a href="<?= e($headerSortUrl($fieldSortKey)); ?>" class="<?= trim($sortClass($fieldSortKey)); ?>"><?= e((string)($uiFieldLabels[$field['field_key']] ?? $field['label'])); ?><?= e($sortIndicator($fieldSortKey)); ?></a></th>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php if ($showActionColumn): ?><th>Action</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$assets): ?>
                                <?php
                                    $columnCount = 1
                                        + ((!is_superadmin() && $canModifyAssets && !$isUnderMeView) ? 1 : 0)
                                        + (($showAssetNumber && !empty($visibleColumnKeys['asset_number'])) ? 1 : 0)
                                        + (((is_superadmin() || $isUnderMeView) && !empty($visibleColumnKeys['office_name'])) ? 1 : 0)
                                        + (($subcategoryEnabled && !empty($visibleColumnKeys['subcategory_name'])) ? 1 : 0)
                                        + (!empty($visibleColumnKeys['data_provider']) ? 1 : 0)
                                        + $visibleFieldCount
                                        + ($showActionColumn ? 1 : 0);
                                ?>
                                <tr><td colspan="<?= e((string)$columnCount); ?>" class="muted">No assets found.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($assets as $index => $asset): ?>
                                <tr>
                                    <?php if (!is_superadmin() && $canModifyAssets && !$isUnderMeView): ?>
                                        <td><input type="checkbox" name="asset_ids[]" value="<?= e((string)$asset['id']); ?>" form="asset-delete-form"></td>
                                    <?php endif; ?>
                                    <td><?= e((string)($index + 1)); ?></td>
                                    <?php if ($showAssetNumber && !empty($visibleColumnKeys['asset_number'])): ?><td><?= e($asset['asset_number']); ?></td><?php endif; ?>
                                    <?php if ((is_superadmin() || $isUnderMeView) && !empty($visibleColumnKeys['office_name'])): ?><td><?= e($asset['office_type_label'] . ' - ' . $asset['office_name']); ?></td><?php endif; ?>
                                    <?php if ($subcategoryEnabled && !empty($visibleColumnKeys['subcategory_name'])): ?><td><?= e((string)($asset['subcategory_name'] ?? '')); ?></td><?php endif; ?>
                                    <?php if (!empty($visibleColumnKeys['data_provider'])): ?>
                                        <td><?= e(strtok((string)($asset['created_by_email'] ?? ''), '@') ?: ''); ?></td>
                                    <?php endif; ?>
                                    <?php foreach ($fields as $field): ?>
                                        <?php if ((int)$field['is_displayed'] === 1 && (int)$field['active_status'] === 1 && !empty($visibleColumnKeys[$field['field_key']])): ?>
                                            <td>
                                                <?php if ($field['data_type'] === 'file'): ?>
                                                    <?php $assetFiles = $asset['files'][$field['field_key']] ?? []; ?>
                                                    <?php
                                                        $fileRule = get_asset_field_file_rule((int)$field['id']);
                                                        $accept = implode(',', array_map(static fn(string $ext): string => '.' . $ext, asset_parse_extensions_string((string)$fileRule['allowed_extensions'])));
                                                        $formId = 'asset-inline-file-' . (int)$asset['id'] . '-' . preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string)$field['field_key']);
                                                    ?>
                                                    <?php if ($assetFiles): ?>
                                                        <div class="file-link-list">
                                                            <?php foreach ($assetFiles as $fileRow): ?>
                                                                <?php $meta = $fileIconMeta((string)$fileRow['original_name']); ?>
                                                                <a href="index.php?page=asset_file&id=<?= e((string)$fileRow['id']); ?>" class="file-chip file-chip-icon-only <?= e($meta['class']); ?>" target="_blank" rel="noopener" title="<?= e((string)$fileRow['original_name']); ?>">
                                                                    <span class="file-chip-icon"><?= e($meta['icon']); ?></span>
                                                                </a>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php elseif (!$canModifyAssets || is_superadmin() || $isUnderMeView): ?>
                                                        <span class="muted">No file</span>
                                                    <?php endif; ?>
                                                    <?php if ($canModifyAssets && !is_superadmin() && !$isUnderMeView): ?>
                                                        <form method="post" action="index.php" enctype="multipart/form-data" class="inline-file-upload-form" id="<?= $formId; ?>">
                                                            <?= csrf_input(); ?>
                                                            <input type="hidden" name="action" value="asset_upload_field_files">
                                                            <input type="hidden" name="asset_id" value="<?= e((string)$asset['id']); ?>">
                                                            <input type="hidden" name="field_key" value="<?= e($field['field_key']); ?>">
                                                            <input
                                                                type="file"
                                                                name="field_files[<?= e($field['field_key']); ?>][]"
                                                                class="inline-file-input"
                                                                id="<?= $formId; ?>-input"
                                                                <?= (int)$fileRule['is_multiple'] === 1 ? 'multiple' : ''; ?>
                                                                accept="<?= e($accept); ?>"
                                                                data-inline-file-input
                                                                data-label="<?= e((string)($uiFieldLabels[$field['field_key']] ?? $field['label'])); ?>"
                                                                data-allowed-extensions="<?= e((string)$fileRule['allowed_extensions']); ?>"
                                                                data-max-files="<?= e((string)$fileRule['max_files']); ?>"
                                                                data-max-file-size="<?= e((string)$fileRule['max_file_size_bytes']); ?>"
                                                                data-max-total-size="<?= e((string)$fileRule['max_total_size_bytes']); ?>"
                                                                data-is-multiple="<?= e((string)$fileRule['is_multiple']); ?>">
                                                            <label for="<?= $formId; ?>-input" class="btn-small inline-file-add-button"><?= $assetFiles ? 'Add More' : 'Add File'; ?></label>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?= e((string)($asset['values'][$field['field_key']] ?? '')); ?>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <?php if ($showActionColumn): ?>
                                        <td>
                                            <div class="action-icon-row">
                                                <a href="index.php?<?= e(http_build_query(['page' => 'board', 'segment_id' => $activeSegmentId, 'office_view_scope' => $currentOfficeViewScope, 'asset_history' => (int)$asset['id']])); ?>" class="icon-only-button table-action-icon" title="See update history" aria-label="See update history">&#x1F553;</a>
                                                <a href="index.php?<?= e(http_build_query(['page' => 'board', 'segment_id' => $activeSegmentId, 'office_view_scope' => $currentOfficeViewScope, 'edit_asset' => (int)$asset['id']])); ?>" class="icon-only-button table-action-icon" title="Edit asset" aria-label="Edit asset">&#x270E;</a>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <div class="modal-backdrop" id="columns-modal-<?= (int)$category['id']; ?>" aria-hidden="true">
                <div class="modal-card column-visibility-modal" role="dialog" aria-modal="true" aria-labelledby="columns-modal-title-<?= (int)$category['id']; ?>">
                    <h3 id="columns-modal-title-<?= (int)$category['id']; ?>">Column Visibility: <?= e($category['name']); ?></h3>
                    <form method="post" action="index.php" class="grid" id="columns-form-<?= (int)$category['id']; ?>">
                        <?= csrf_input(); ?>
                        <input type="hidden" name="action" value="save_asset_table_visibility">
                        <input type="hidden" name="category_id" value="<?= (int)$category['id']; ?>">
                        <input type="hidden" name="table_scope" value="<?= e($currentOfficeViewScope); ?>">
                        <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
                        <div class="column-visibility-grid">
                            <?php foreach ($availableTableColumns as $column): ?>
                                <label class="inline-check">
                                    <input type="checkbox" name="visible_columns[]" value="<?= e((string)$column['key']); ?>" <?= !empty($visibleColumnKeys[$column['key']]) ? 'checked' : ''; ?>>
                                    <span><?= e((string)$column['label']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn-small" data-show-all-columns="columns-form-<?= (int)$category['id']; ?>">Show All</button>
                            <button type="submit">Save</button>
                            <button type="submit" name="apply_to_all" value="1" class="btn-secondary">Apply Visibility to All Tables</button>
                            <button type="button" class="modal-close" data-close="columns-modal-<?= (int)$category['id']; ?>">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </section>
    <?php if (!is_superadmin() && $canModifyAssets && !$isUnderMeView): ?>
        <div class="bulk-actions">
            <button type="submit" class="btn-danger" form="asset-delete-form">Soft Delete Selected</button>
        </div>
    <?php endif; ?>

<?php if (!is_superadmin() && $canModifyAssets && !$isUnderMeView): ?>
<div class="modal-backdrop<?= $editingAsset ? ' open' : ''; ?>" id="asset-modal" aria-hidden="<?= $editingAsset ? 'false' : 'true'; ?>">
    <div class="modal-card asset-entry-modal" role="dialog" aria-modal="true" aria-labelledby="asset-modal-title">
        <h3 id="asset-modal-title"><?= $editingAsset ? 'Edit Asset' : 'Add Asset'; ?></h3>
        <form method="post" action="index.php" class="grid" enctype="multipart/form-data">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="asset_save">
            <input type="hidden" name="asset_id" value="<?= e((string)($editingAsset['id'] ?? '0')); ?>">
            <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
            <label>Category *
                <select name="category_id" id="asset-category-select" required>
                    <option value="">Select</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e((string)$category['id']); ?>" <?= ((int)($editingAsset['category_id'] ?? $defaultCategoryId) === (int)$category['id']) ? 'selected' : ''; ?>><?= e($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php if ($subcategoryEnabled): ?>
                <label>Sub-category *
                    <select name="subcategory_id" id="asset-subcategory-select" required>
                        <option value="">Select</option>
                        <?php foreach ($subcategories as $subcategory): ?>
                            <option value="<?= e((string)$subcategory['id']); ?>" data-category="<?= e((string)$subcategory['category_id']); ?>" <?= (int)($editingAsset['subcategory_id'] ?? 0) === (int)$subcategory['id'] ? 'selected' : ''; ?>><?= e($subcategory['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>
            <?php foreach ($fields as $field): ?>
                <?php if ((int)$field['active_status'] !== 1) { continue; } ?>
                <label><?= e((string)($uiFieldLabels[$field['field_key']] ?? $field['label'])); ?><?= (int)$field['is_required'] === 1 ? ' *' : ''; ?>
                    <?php $value = (string)($editValues[$field['field_key']] ?? ''); ?>
                    <?php if ($field['data_type'] === 'file'): ?>
                        <?php
                            $fileRule = get_asset_field_file_rule((int)$field['id']);
                            $fieldFiles = $editFiles[$field['field_key']] ?? [];
                            $accept = implode(',', array_map(static fn(string $ext): string => '.' . $ext, asset_parse_extensions_string((string)$fileRule['allowed_extensions'])));
                            $showRemoveCheckbox = (int)$fileRule['is_multiple'] === 1 || (int)$field['is_required'] !== 1;
                        ?>
                        <?php if ($fieldFiles): ?>
                            <div class="file-link-list">
                                <?php foreach ($fieldFiles as $fileRow): ?>
                                    <?php $meta = $fileIconMeta((string)$fileRow['original_name']); ?>
                                    <label class="file-delete-chip">
                                        <a href="index.php?page=asset_file&id=<?= e((string)$fileRow['id']); ?>" class="file-chip file-chip-icon-only <?= e($meta['class']); ?>" target="_blank" rel="noopener" title="<?= e((string)$fileRow['original_name']); ?>">
                                            <span class="file-chip-icon"><?= e($meta['icon']); ?></span>
                                        </a>
                                        <?php if ($showRemoveCheckbox): ?>
                                            <span class="inline-check"><input type="checkbox" name="delete_field_files[<?= e($field['field_key']); ?>][]" value="<?= e((string)$fileRow['id']); ?>"> Remove</span>
                                        <?php endif; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="field_files[<?= e($field['field_key']); ?>][]" <?= (int)$fileRule['is_multiple'] === 1 ? 'multiple' : ''; ?> accept="<?= e($accept); ?>">
                        <span class="hint">
                            Allowed: <?= e((string)$fileRule['allowed_extensions']); ?>.
                            Max files: <?= e((string)$fileRule['max_files']); ?>.
                            <?php if ((int)$fileRule['max_file_size_bytes'] > 0): ?> Per file: <?= e(asset_megabytes_from_bytes((int)$fileRule['max_file_size_bytes'])); ?> MB.<?php endif; ?>
                            <?php if ((int)$fileRule['max_total_size_bytes'] > 0): ?> Total: <?= e(asset_megabytes_from_bytes((int)$fileRule['max_total_size_bytes'])); ?> MB.<?php endif; ?>
                            <?php if (!$showRemoveCheckbox && $fieldFiles): ?> Uploading a new file will automatically replace the existing file.<?php endif; ?>
                        </span>
                    <?php elseif ($field['data_type'] === 'date'): ?>
                        <input type="date" name="fields[<?= e($field['field_key']); ?>]" value="<?= e($value); ?>" <?= (int)$field['is_required'] === 1 ? 'required' : ''; ?>>
                    <?php elseif ($field['data_type'] === 'number'): ?>
                        <input type="number" step="0.01" name="fields[<?= e($field['field_key']); ?>]" value="<?= e($value); ?>" <?= (int)$field['is_required'] === 1 ? 'required' : ''; ?>>
                    <?php elseif ($field['data_type'] === 'dropdown' || $field['data_type'] === 'conditional'): ?>
                        <?php
                            $parentId = (int)($field['secondary_of_field_id'] ?? 0);
                            $parentField = null;
                            foreach ($fields as $candidateField) {
                                if ((int)$candidateField['id'] === $parentId) {
                                    $parentField = $candidateField;
                                    break;
                                }
                            }
                            $conditionalParent = asset_is_conditional_primary($field) ? $field : $parentField;
                            $childOptions = ($parentField && $value !== '')
                                ? asset_conditional_child_options($parentField, (string)($editValues[$parentField['field_key']] ?? ''))
                                : [];
                        ?>
                        <?php $fieldNameAttr = 'fields[' . $field['field_key'] . ']'; ?>
                        <select
                            name="fields[<?= e($field['field_key']); ?>]"
                            data-field-key="<?= e($field['field_key']); ?>"
                            data-field-name="<?= e($fieldNameAttr); ?>"
                            <?= (int)$field['is_required'] === 1 ? 'required' : ''; ?>
                            <?php if (asset_is_conditional_primary($field)): ?>
                                data-conditional-primary="1"
                                data-conditional-map='<?= e(json_encode(asset_decode_conditional_map($field), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>'
                                <?php $conditionalChildField = get_asset_conditional_child_field((int)$field['id']); ?>
                                data-conditional-child="<?= e((string)($conditionalChildField['field_key'] ?? '')); ?>"
                            <?php elseif ($parentField): ?>
                                data-conditional-secondary="<?= e((string)$parentField['field_key']); ?>"
                            <?php endif; ?>
                        >
                            <option value="">Select</option>
                            <?php
                                $fieldOptions = get_asset_field_options((int)$field['id']);
                                if ($parentField) {
                                    $fieldOptions = array_map(static fn(string $option): array => ['option_value' => $option, 'option_label' => $option], $childOptions);
                                }
                            ?>
                            <?php foreach ($fieldOptions as $option): ?>
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
            <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
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
            ], get_asset_categories(false, $activeSegmentId)),
            'subcategories' => $subcategoryEnabled ? array_map(static fn(array $subcategory): array => [
                'id' => (int)$subcategory['id'],
                'category_id' => (int)$subcategory['category_id'],
                'name' => (string)$subcategory['name'],
            ], $importReviewSubcategories) : [],
            'fields' => $importFieldDefs,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
        <form method="post" action="index.php" class="grid">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="asset_import_save">
            <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
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
                            <?php if ($subcategoryEnabled): ?><th>Sub-category</th><?php endif; ?>
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
                                <?php if ($subcategoryEnabled): ?>
                                    <td class="<?= !empty($row['errors']['subcategory_id']) ? 'cell-error' : 'cell-valid'; ?>">
                                        <select class="review-input" data-review-role="subcategory" name="rows[<?= $rowIndex; ?>][subcategory]">
                                            <option value="">Select</option>
                                            <?php foreach ($importReviewSubcategories as $subcategory): ?>
                                                <option value="<?= e($subcategory['name']); ?>" data-category-name="<?= e((string)($categoryNameById[(int)$subcategory['category_id']] ?? '')); ?>" data-category-id="<?= e((string)$subcategory['category_id']); ?>" <?= strcasecmp((string)$row['subcategory'], (string)$subcategory['name']) === 0 ? 'selected' : ''; ?>><?= e($subcategory['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                <?php endif; ?>
                                <?php foreach ($fields as $field): ?>
                                    <?php if ((int)$field['is_import_enabled'] === 1 && (int)$field['active_status'] === 1): ?>
                                        <?php $fieldError = $row['errors'][$field['field_key']] ?? null; ?>
                                        <td class="<?= $fieldError ? 'cell-error' : 'cell-valid'; ?>">
                                            <?php $fieldValue = (string)($row['fields'][$field['field_key']] ?? ''); ?>
                                            <?php if (in_array($field['data_type'], ['dropdown', 'yes_no', 'conditional'], true)): ?>
                                                <select
                                                    class="review-input"
                                                    data-review-role="field"
                                                    data-field-key="<?= e($field['field_key']); ?>"
                                                    data-field-type="<?= e($field['data_type']); ?>"
                                                    data-required="<?= (int)$field['is_required']; ?>"
                                                    data-number-format-rule="<?= e((string)($field['number_format_rule'] ?? '')); ?>"
                                                    <?php
                                                        $parentId = (int)($field['secondary_of_field_id'] ?? 0);
                                                        $parentField = null;
                                                        foreach ($fields as $candidateField) {
                                                            if ((int)$candidateField['id'] === $parentId) {
                                                                $parentField = $candidateField;
                                                                break;
                                                            }
                                                        }
                                                    ?>
                                                    <?php if (asset_is_conditional_primary($field)): ?>
                                                        data-conditional-primary="1"
                                                        data-conditional-map='<?= e(json_encode(asset_decode_conditional_map($field), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>'
                                                        data-conditional-child="<?= e((string)(get_asset_conditional_child_field((int)$field['id'])['field_key'] ?? '')); ?>"
                                                    <?php elseif ($parentField): ?>
                                                        data-conditional-secondary="<?= e((string)$parentField['field_key']); ?>"
                                                    <?php endif; ?>
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
                                            <?php elseif ($field['data_type'] === 'text'): ?>
                                                <textarea
                                                    class="review-input review-textarea"
                                                    data-review-role="field"
                                                    data-field-key="<?= e($field['field_key']); ?>"
                                                    data-field-type="<?= e($field['data_type']); ?>"
                                                    data-required="<?= (int)$field['is_required']; ?>"
                                                    data-number-format-rule="<?= e((string)($field['number_format_rule'] ?? '')); ?>"
                                                    name="rows[<?= $rowIndex; ?>][fields][<?= e($field['field_key']); ?>]"
                                                    rows="3"
                                                ><?= e($fieldValue); ?></textarea>
                                            <?php else: ?>
                                                <input
                                                    type="<?= $field['data_type'] === 'number' ? 'number' : ($field['data_type'] === 'date' ? 'date' : 'text'); ?>"
                                                    <?= $field['data_type'] === 'number' ? 'step="0.01"' : ''; ?>
                                                    class="review-input"
                                                    data-review-role="field"
                                                    data-field-key="<?= e($field['field_key']); ?>"
                                                    data-field-type="<?= e($field['data_type']); ?>"
                                                    data-required="<?= (int)$field['is_required']; ?>"
                                                    data-number-format-rule="<?= e((string)($field['number_format_rule'] ?? '')); ?>"
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
            <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
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
            } elseif ($downloadFilters['office_type'] === 5) {
                $downloadScope = 'subdivision';
            }
        ?>
        <form method="post" action="index.php" class="grid" id="superadmin-download-form">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="asset_download_data">
            <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
            <input type="hidden" name="office_scope" id="download-office-scope" value="<?= e($downloadScope); ?>">
            <div class="download-modal-scope-row">
                <div class="segmented-control" id="download-scope-toggle" role="tablist" aria-label="Office Level">
                    <button type="button" class="segment<?= $downloadScope === 'zone' ? ' is-active' : ''; ?>" data-download-scope="zone">Zone</button>
                    <button type="button" class="segment<?= $downloadScope === 'circle' ? ' is-active' : ''; ?>" data-download-scope="circle">Circle</button>
                    <button type="button" class="segment<?= $downloadScope === 'division' ? ' is-active' : ''; ?>" data-download-scope="division">Division</button>
                    <button type="button" class="segment<?= $downloadScope === 'subdivision' ? ' is-active' : ''; ?>" data-download-scope="subdivision">Sub-division</button>
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
                <label data-download-level="subdivision">Sub-division
                    <select name="subdivision_id" id="download-subdivision-select">
                        <option value="0">All</option>
                        <?php foreach ($subdivisions as $subdivision): ?>
                            <option value="<?= e((string)$subdivision['id']); ?>" data-zone="<?= e((string)$subdivision['zone_id']); ?>" data-circle="<?= e((string)$subdivision['circle_id']); ?>" data-division="<?= e((string)$subdivision['division_id']); ?>" <?= ($selectedSubdivision ?? 0) === (int)$subdivision['id'] ? 'selected' : ''; ?>><?= e($subdivision['office_name']); ?></option>
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
                <?php if ($subcategoryEnabled): ?>
                    <label>Sub-category
                        <select name="subcategory_id" id="download-subcategory-select">
                            <option value="0">All</option>
                            <?php foreach ($subcategories as $subcategory): ?>
                                <option value="<?= e((string)$subcategory['id']); ?>" data-category="<?= e((string)$subcategory['category_id']); ?>" <?= ($downloadFilters['subcategory_id'] ?? 0) === (int)$subcategory['id'] ? 'selected' : ''; ?>><?= e($subcategory['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>
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

<?php if ($historyAsset): ?>
<div class="modal-backdrop open" id="asset-history-modal" aria-hidden="false">
    <div class="modal-card modal-wide asset-history-modal" role="dialog" aria-modal="true" aria-labelledby="asset-history-title">
        <h3 id="asset-history-title">Asset History</h3>
        <div class="asset-history-meta">
            <strong><?= e((string)($historyAsset['asset_number'] ?? '')); ?></strong>
            <span class="muted"><?= e((string)($historyAsset['category_name'] ?? '')); ?></span>
            <span class="muted">Provider: <?= e((string)($historyAsset['created_by_email'] ?? '')); ?></span>
        </div>
        <div class="asset-history-list">
            <?php if (!$historyLogs): ?>
                <p class="muted">No history found for this asset.</p>
            <?php else: ?>
                <?php foreach ($historyLogs as $log): ?>
                    <article class="asset-history-entry">
                        <div class="asset-history-head">
                            <strong><?= e((string)$log['summary']); ?></strong>
                            <span class="muted"><?= e((string)$log['email_id']); ?> · <?= e((string)$log['created_at']); ?></span>
                        </div>
                        <?php if (!empty($log['detail_items'])): ?>
                            <div class="asset-history-details">
                                <?php foreach ($log['detail_items'] as $detail): ?>
                                    <div class="asset-history-detail-row">
                                        <span class="asset-history-detail-label"><?= e((string)($detail['field'] ?? 'Field')); ?>:</span>
                                        <?php if (array_key_exists('from', $detail) || array_key_exists('to', $detail)): ?>
                                            <span><?= e((string)($detail['from'] ?? '')); ?></span>
                                            <span class="muted">→</span>
                                            <span><?= e((string)($detail['to'] ?? '')); ?></span>
                                        <?php else: ?>
                                            <span><?= e((string)($detail['value'] ?? '')); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-close" data-close="asset-history-modal">Close</button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
