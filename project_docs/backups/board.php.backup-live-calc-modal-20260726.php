<?php
$assetEmbedBoard = defined('ASSET_EMBED_BOARD') && ASSET_EMBED_BOARD === true;
$assetComparisonTableOnly = defined('ASSET_COMPARISON_TABLE_ONLY') && ASSET_COMPARISON_TABLE_ONLY === true;
$assetBoardFiltersOnly = defined('ASSET_BOARD_FILTERS_ONLY') && ASSET_BOARD_FILTERS_ONLY === true;
if (!$assetEmbedBoard && !$assetBoardFiltersOnly) {
    require __DIR__ . '/header.php';
} elseif ($assetEmbedBoard) {
    $embedInfo = get_info_row();
    $embedThemeKey = asset_normalize_theme_key((string)($embedInfo['ui_theme_key'] ?? ''));
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e((string)($embedInfo['site_name'] ?? 'PWD Asset Management System')); ?></title>
        <link rel="stylesheet" href="<?= e(asset_url('public/assets/style.css')); ?>">
    </head>
    <body class="theme-<?= e($embedThemeKey); ?> comparison-embed-body">
    <main class="container board-embed-shell<?= $assetComparisonTableOnly ? ' board-embed-table-only' : ''; ?>">
    <?php
}

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
$scopeVisibility = is_superadmin()
    ? ['show_my_office' => true, 'show_office_under_me' => false]
    : asset_scope_visibility_for_office_type((int)($user['office_type'] ?? 0), $activeSegmentId);
$canUseMyOfficeScope = is_superadmin() || !empty($scopeVisibility['show_my_office']);
$canUseUnderMeScope = !is_superadmin() && $hasUnderMeScope && !empty($scopeVisibility['show_office_under_me']);
$scopeAccessAvailable = is_superadmin() || $canUseMyOfficeScope || $canUseUnderMeScope;
if (!$canUseUnderMeScope && $currentOfficeViewScope === 'office_under_me') {
    $currentOfficeViewScope = $canUseMyOfficeScope ? 'my_office' : 'office_under_me';
}
if (!$canUseMyOfficeScope && $canUseUnderMeScope) {
    $currentOfficeViewScope = 'office_under_me';
}
if (!$hasUnderMeScope) {
    $currentOfficeViewScope = 'my_office';
}
$isUnderMeView = !is_superadmin() && $canUseUnderMeScope && $currentOfficeViewScope === 'office_under_me';
$showScopeSwitchCard = !is_superadmin() && $canUseMyOfficeScope && $canUseUnderMeScope;
$showBoardContent = is_superadmin() || $scopeAccessAvailable;
$showFilterCard = $showBoardContent && (is_superadmin()
    ? asset_filter_card_enabled_for_superadmin($activeSegmentId)
    : asset_filter_card_enabled_for_users($activeSegmentId));
$bulkImportEnabled = asset_bulk_import_enabled($activeSegmentId);
$showAssetNumber = is_superadmin() || asset_number_visible_to_users($activeSegmentId);
$showActionColumn = !is_superadmin() && $canModifyAssets && !$isUnderMeView;
$fieldMap = asset_field_map_for_segment(false, $activeSegmentId);
$fields = get_asset_fields(false, $activeSegmentId);
$categories = get_asset_categories(false, $activeSegmentId);
$categorySelectionEnabled = asset_category_selection_enabled($activeSegmentId);
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
$parseBoardDateTime = static function (string $raw): ?int {
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }
    $timestamp = strtotime($raw);
    return $timestamp === false ? null : $timestamp;
};
$formatBoardDateTimeInput = static function (string $raw) use ($parseBoardDateTime): string {
    $timestamp = $parseBoardDateTime($raw);
    return $timestamp === null ? '' : date('Y-m-d\TH:i', $timestamp);
};
$timeFilterCategoryId = (int)request_str('time_filter_category_id', '0');
$timeFilterFieldKey = request_str('time_filter_field', '');
$timeFilterStartRaw = request_str('time_filter_start', '');
$timeFilterEndRaw = request_str('time_filter_end', '');
$timeFilterFromBeginning = request_str('time_filter_from_beginning', '') === '1';
$timeFilterTillNow = request_str('time_filter_till_now', '') === '1';
$timeFilterStartTimestamp = $timeFilterFromBeginning ? null : $parseBoardDateTime($timeFilterStartRaw);
$timeFilterEndTimestamp = $timeFilterTillNow ? null : $parseBoardDateTime($timeFilterEndRaw);
$timeFilterActive = $timeFilterCategoryId > 0
    && $timeFilterFieldKey !== ''
    && ($timeFilterFromBeginning || $timeFilterTillNow || $timeFilterStartTimestamp !== null || $timeFilterEndTimestamp !== null);
asset_board_perf_mark('board.filters_prepared', [
    'segment_id' => $activeSegmentId,
    'time_filter_active' => $timeFilterActive ? 1 : 0,
]);
$baseScopeAssets = [];
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
$downloadModuleReady = !empty(asset_download_level1_fields()) && !empty(get_asset_segments(false));
$downloadModalUrl = 'index.php?' . http_build_query([
    'page' => 'download_modal_fragment',
    'segment_id' => $activeSegmentId,
    'office_view_scope' => $currentOfficeViewScope,
]);

$zones = db()->query('SELECT id, office_name FROM zones ORDER BY office_name')->fetchAll();
$circles = db()->query('SELECT id, office_name, zone_id FROM circles ORDER BY office_name')->fetchAll();
$divisions = db()->query('SELECT id, office_name, zone_id, circle_id FROM divisions ORDER BY office_name')->fetchAll();
$subdivisions = db()->query('SELECT id, office_name, zone_id, circle_id, division_id FROM subdivisions ORDER BY office_name')->fetchAll();
$uiFieldLabels = [];
$fieldHelpMeta = [];
foreach ($fields as $field) {
    $rawLabel = trim((string)($field['label'] ?? ''));
    $parts = preg_split('/\s*\/\s*/u', $rawLabel);
    $uiFieldLabels[$field['field_key']] = trim((string)($parts[0] ?? $rawLabel));
    $fieldHelpMeta[$field['field_key']] = [
        'label' => trim((string)($parts[0] ?? $rawLabel)),
        'information' => (string)($field['field_information'] ?? ''),
        'tutorial_url' => (string)($field['video_tutorial_url'] ?? ''),
        'tutorial_embed_url' => (string)(asset_youtube_embed_url((string)($field['video_tutorial_url'] ?? '')) ?? ''),
        'hosted_tutorial_url' => (string)(asset_field_hosted_tutorial_stream_url($field) ?? ''),
        'hosted_tutorial_name' => (string)($field['hosted_tutorial_video_original_name'] ?? $field['hosted_tutorial_video_path'] ?? ''),
        'has_help' => asset_field_has_help($field),
    ];
}
if ($timeFilterFieldKey !== '') {
    $timeFilterField = $fieldMap[$timeFilterFieldKey] ?? null;
    if (!$timeFilterField || (int)($timeFilterField['active_status'] ?? 0) !== 1 || (int)($timeFilterField['is_displayed'] ?? 0) !== 1) {
        $timeFilterFieldKey = '';
        $timeFilterActive = false;
        $timeFilterCategoryId = 0;
    }
}
$bimhPickerData = asset_bimh_picker_rows($user);
$bimhPickerScope = asset_bimh_picker_scope($user);
$availableTableColumns = asset_table_available_columns($fields, $uiFieldLabels, $currentOfficeViewScope, $activeSegmentId);
$columnPreferenceMap = get_asset_table_column_preferences((int)$user['id'], $activeSegmentId);
$filterCatalog = ['categories' => [], 'subcategories' => [], 'zones' => [], 'circles' => [], 'divisions' => [], 'subdivisions' => [], 'fields' => []];
$visibleFilterFields = [];
$activeFilterCatalog = $filterCatalog;
$showCategoryFilter = false;
$showSubcategoryFilter = false;
$showZoneFilter = is_superadmin();
$showCircleFilter = is_superadmin() || ($isUnderMeView && (int)($user['office_type'] ?? 0) === 2);
$showDivisionFilter = is_superadmin() || ($isUnderMeView && in_array((int)($user['office_type'] ?? 0), [2, 3], true));
$showSubdivisionFilter = is_superadmin() || ($isUnderMeView && in_array((int)($user['office_type'] ?? 0), [2, 3, 4], true));
$fieldFilterSelections = [];
$hasDynamicFieldFilters = false;
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
        if ($filters[$filterKey . '_from'] !== '' || $filters[$filterKey . '_to'] !== '') {
            $hasDynamicFieldFilters = true;
        }
    }
    if ($fieldFilterSelections[$fieldKey] !== '') {
        $hasDynamicFieldFilters = true;
    }
}
$hasBoardSelections = $selectedZone > 0
    || $selectedCircle > 0
    || $selectedDivision > 0
    || $selectedSubdivision > 0
    || (int)($filters['category_id'] ?? 0) > 0
    || (int)($filters['subcategory_id'] ?? 0) > 0
    || $hasDynamicFieldFilters;
$plainBoardState = !$timeFilterActive && $sortColumn === '' && !$hasBoardSelections;
$precomputedVisibleFilterFields = $showFilterCard ? asset_filter_visible_fields($fields, [], $activeSegmentId) : [];
$filterCatalogNeedsFiles = false;
if ($precomputedVisibleFilterFields) {
    foreach ($fields as $field) {
        $fieldKey = (string)($field['field_key'] ?? '');
        if (empty($precomputedVisibleFilterFields[$fieldKey])) {
            continue;
        }
        if ((string)($field['data_type'] ?? '') === 'file') {
            $filterCatalogNeedsFiles = true;
            break;
        }
    }
}
if ($scopeAccessAvailable && ($assetBoardFiltersOnly || $showFilterCard || $plainBoardState)) {
    $baseScopeAssets = get_assets(
        ['office_view_scope' => $currentOfficeViewScope, 'segment_id' => $activeSegmentId],
        $user,
        false,
        [
            'include_files' => $assetBoardFiltersOnly ? $filterCatalogNeedsFiles : true,
            'include_timestamps' => false,
            'include_office_labels' => true,
            'skip_sort' => true,
        ]
    );
    if ($plainBoardState && $baseScopeAssets !== []) {
        $baseScopeAssets = asset_apply_superadmin_common_default_order($baseScopeAssets);
    }
    asset_board_perf_mark('board.base_scope_loaded', ['assets' => count($baseScopeAssets)]);
}
if ($showFilterCard) {
    $filterCatalog = build_asset_filter_catalog($baseScopeAssets, $fields, $activeSegmentId, true, $plainBoardState && is_superadmin());
    asset_board_perf_mark('board.filter_catalog_built', [
        'categories' => count($filterCatalog['categories'] ?? []),
        'fields' => count($filterCatalog['fields'] ?? []),
    ]);
    $visibleFilterFields = $precomputedVisibleFilterFields;
    $showCategoryFilter = $categorySelectionEnabled && count($filterCatalog['categories']) > 1;
    $showSubcategoryFilter = $subcategoryEnabled && count($filterCatalog['subcategories']) > 0;
}
$filteredBoardAssets = [];
if ($scopeAccessAvailable) {
    if ($plainBoardState && $baseScopeAssets !== []) {
        $filteredBoardAssets = $baseScopeAssets;
    } elseif ($plainBoardState && !$assetBoardFiltersOnly) {
        $filteredBoardAssets = get_assets(
            $filters,
            $user,
            false,
            [
                'include_files' => true,
                'include_timestamps' => false,
                'include_office_labels' => true,
                'skip_sort' => true,
            ]
        );
        if ($filteredBoardAssets !== []) {
            $filteredBoardAssets = asset_apply_superadmin_common_default_order($filteredBoardAssets);
        }
    } else {
        $filteredBoardAssets = get_assets(
            $filters,
            $user,
            false,
            [
                'include_files' => true,
                'include_timestamps' => $timeFilterActive,
                'include_office_labels' => true,
            ]
        );
    }
}
asset_board_perf_mark('board.filtered_assets_loaded', ['assets' => count($filteredBoardAssets)]);
$groupedAssets = $scopeAccessAvailable
    ? asset_group_assets_by_category(
        $filteredBoardAssets,
        $plainBoardState ? ['segment_id' => $activeSegmentId] : $filters,
        $activeSegmentId
    )
    : [];
asset_board_perf_mark('board.assets_grouped', ['groups' => count($groupedAssets)]);
if ($timeFilterActive) {
    foreach ($groupedAssets as &$group) {
        $categoryId = (int)($group['category']['id'] ?? 0);
        if ($categoryId !== $timeFilterCategoryId) {
            continue;
        }
        $group['assets'] = asset_filter_rows_by_field_time(
            (array)($group['assets'] ?? []),
            $timeFilterFieldKey,
            $timeFilterStartTimestamp,
            $timeFilterEndTimestamp
        );
    }
    unset($group);
}
$displayedAssets = [];
foreach ($groupedAssets as $group) {
    foreach (($group['assets'] ?? []) as $assetRow) {
        $displayedAssets[] = $assetRow;
    }
}
if ($showFilterCard) {
    if ($plainBoardState) {
        $activeFilterCatalog = $filterCatalog;
    } else {
        $activeFilterCatalog = build_asset_filter_catalog($displayedAssets, $fields, $activeSegmentId);
        asset_board_perf_mark('board.active_filter_catalog_built', ['assets' => count($displayedAssets)]);
    }
}
$paginationEnabled = asset_board_pagination_enabled($filters, $fields, $activeSegmentId, $timeFilterActive);
$paginationBatchSize = asset_board_pagination_batch_size();
if ($assetComparisonTableOnly) {
    $showFilterCard = false;
    $showActionColumn = false;
    $paginationEnabled = false;
}
$categoryNameById = [];
foreach ($categories as $category) {
    $categoryNameById[(int)$category['id']] = (string)$category['name'];
}
$importFieldDefs = [];
$uniqueValueMap = asset_unique_existing_values_map($activeSegmentId);
foreach ($fields as $field) {
    if (!asset_field_is_import_template_visible($field)) {
        continue;
    }
    $conditionalMap = asset_is_conditional_primary($field) ? asset_decode_conditional_map($field) : [];
    $importFieldDefs[] = [
        'field_key' => (string)$field['field_key'],
        'label' => (string)($uiFieldLabels[$field['field_key']] ?? $field['label']),
        'data_type' => (string)$field['data_type'],
        'required' => $field['data_type'] === 'calculation' ? false : asset_is_input_required($field),
        'is_unique' => (int)($field['is_unique'] ?? 0) === 1,
        'number_format_rule' => (string)($field['number_format_rule'] ?? ''),
        'text_max_length' => (int)($field['text_max_length'] ?? 0),
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
$defaultCategoryId = !$editingAsset && count($categories) === 1 ? (int)$categories[0]['id'] : 0;
$commonProfilesForBoard = asset_common_profiles_by_category_map($activeSegmentId, true);
$manualAddAllowedCategoryIds = [];
foreach ($categories as $category) {
    if (asset_common_category_allows_manual_rows($activeSegmentId, (int)$category['id'])) {
        $manualAddAllowedCategoryIds[(int)$category['id']] = true;
    }
}
$hasManualAddCategory = !empty($manualAddAllowedCategoryIds);
$defaultCategoryAllowsManualAdd = $defaultCategoryId > 0 && !empty($manualAddAllowedCategoryIds[$defaultCategoryId]);
$showAddInfoButton = $canModifyAssets && !$isUnderMeView && ($editingAsset || $hasManualAddCategory);
$editingAssetIsGenerated = $editingAsset ? asset_common_asset_is_generated($editingAsset) : false;
$editingAssetLockedFieldKeys = $editingAsset ? array_fill_keys(asset_common_locked_field_keys_for_asset($editingAsset), true) : [];
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
$fieldMandatoryMarker = static function (array $field): string {
    if (asset_is_input_required($field)) {
        return '<span class="mandatory-marker mandatory-marker-input">*</span>';
    }
    if (asset_is_final_submission_required($field)) {
        return '<span class="mandatory-marker mandatory-marker-final">*</span>';
    }
    return '';
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
$renderFieldHelpButton = static function (?array $meta, string $buttonClass = 'field-help-button', string $title = 'Field information'): void {
    if (!$meta || empty($meta['has_help'])) {
        return;
    }
    ?>
    <button
        type="button"
        class="<?= e($buttonClass); ?>"
        data-field-help
        data-help-label="<?= e((string)($meta['label'] ?? 'Field')); ?>"
        data-help-information="<?= e((string)($meta['information'] ?? '')); ?>"
        data-help-url="<?= e((string)($meta['tutorial_url'] ?? '')); ?>"
        data-help-embed-url="<?= e((string)($meta['tutorial_embed_url'] ?? '')); ?>"
        data-help-hosted-url="<?= e((string)($meta['hosted_tutorial_url'] ?? '')); ?>"
        data-help-hosted-name="<?= e((string)($meta['hosted_tutorial_name'] ?? '')); ?>"
        title="<?= e($title); ?>"
        aria-label="<?= e($title); ?>"
    >i</button>
    <?php
};
$renderBimhFieldControl = static function (
    string $inputName,
    string $fieldKey,
    string $value = '',
    string $estName = '',
    bool $isRequired = false,
    string $inputClass = '',
    array $inputAttributes = []
): void {
    $inputClasses = trim('bimh-id-input ' . $inputClass);
    $isNotFound = trim($estName) === 'BIMH ID is not in the Database.';
    $attributes = '';
    foreach ($inputAttributes as $attrName => $attrValue) {
        if ($attrValue === null || $attrValue === '') {
            continue;
        }
        $attributes .= ' ' . $attrName . '="' . e((string)$attrValue) . '"';
    }
    ?>
    <div class="bimh-field" data-bimh-field>
        <div class="bimh-input-row">
            <input
                type="text"
                name="<?= e($inputName); ?>"
                value="<?= e($value); ?>"
                class="<?= e($inputClasses); ?>"
                data-bimh-id-input
                data-bimh-field-key="<?= e($fieldKey); ?>"
                data-field-key="<?= e($fieldKey); ?>"
                <?= $isRequired ? 'required' : ''; ?><?= $attributes; ?>>
            <button type="button" class="icon-only-button bimh-picker-button" data-bimh-picker-open title="Pick establishment" aria-label="Pick establishment">&#x1F50D;</button>
        </div>
        <div class="bimh-suggestion-menu" data-bimh-suggestions hidden></div>
        <div class="bimh-est-name-box<?= $isNotFound ? ' is-not-found' : ''; ?>" data-bimh-est-name><?= e($estName); ?></div>
    </div>
    <?php
};
$renderBimhEstNameTableCell = static function (string $estName): void {
    $text = trim($estName);
    if ($text === 'BIMH ID is not in the Database.') {
        ?><span class="bimh-est-name-box bimh-est-name-inline is-not-found"><?= e($text); ?></span><?php
        return;
    }
    echo e($text);
};
$renderFilterPicker = static function (string $name, string $label, array $options, string|int $selectedValue = '', array $attributes = [], ?array $helpMeta = null) use ($filterPickerValue, $renderFieldHelpButton): void {
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
    <label>
        <span class="field-label-row">
            <span><?= $label; ?></span>
            <?php $renderFieldHelpButton($helpMeta, 'field-help-button field-help-inline', 'Field information'); ?>
        </span>
        <div class="filter-picker" id="<?= e($pickerId); ?>" data-filter-picker<?= $wrapperAttrs; ?>>
            <input type="hidden" name="<?= e($name); ?>" value="<?= e($normalizedSelectedValue); ?>" data-filter-picker-value>
            <input type="text" value="<?= e($selectedText); ?>" placeholder="All" autocomplete="off" data-filter-picker-input>
            <div class="filter-picker-menu" data-filter-picker-menu>
                <button type="button" class="filter-picker-option" data-option-value="" data-option-label="All">All</button>
                <?php foreach ($options as $option): ?>
                    <button
                        type="button"
                        class="filter-picker-option<?= !empty($option['disabled']) ? ' is-disabled' : ''; ?>"
                        data-option-value="<?= e((string)($option['value'] ?? '')); ?>"
                        data-option-label="<?= e((string)($option['label'] ?? '')); ?>"
                        <?php if (!empty($option['disabled'])): ?>
                            data-option-disabled="1"
                            title="Not present in the table."
                            aria-disabled="true"
                        <?php endif; ?>
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
$buildSelectableFilterOptions = static function (array $allOptions, array $activeValues, bool $includeBlank = false): array {
    $options = [];
    foreach ($allOptions as $option) {
        $value = (string)($option['value'] ?? '');
        if ($value === '') {
            continue;
        }
        $option['disabled'] = !isset($activeValues[$value]);
        $options[] = $option;
    }
    if ($includeBlank) {
        $options[] = [
            'value' => '__blank__',
            'label' => 'Blank',
            'disabled' => !isset($activeValues['__blank__']),
        ];
    }
    return $options;
};
$scopeOfficeOptions = [
    'zones' => [],
    'circles' => [],
    'divisions' => [],
    'subdivisions' => [],
];
if (is_superadmin()) {
    foreach ($zones as $zone) {
        $scopeOfficeOptions['zones'][] = ['value' => (string)$zone['id'], 'label' => (string)$zone['office_name']];
    }
    foreach ($circles as $circle) {
        $scopeOfficeOptions['circles'][] = ['value' => (string)$circle['id'], 'label' => (string)$circle['office_name'], 'meta' => ['zone' => (string)$circle['zone_id']]];
    }
    foreach ($divisions as $division) {
        $scopeOfficeOptions['divisions'][] = ['value' => (string)$division['id'], 'label' => (string)$division['office_name'], 'meta' => ['zone' => (string)$division['zone_id'], 'circle' => (string)$division['circle_id']]];
    }
    foreach ($subdivisions as $subdivision) {
        $scopeOfficeOptions['subdivisions'][] = ['value' => (string)$subdivision['id'], 'label' => (string)$subdivision['office_name'], 'meta' => ['zone' => (string)$subdivision['zone_id'], 'circle' => (string)$subdivision['circle_id'], 'division' => (string)$subdivision['division_id']]];
    }
} elseif ($isUnderMeView) {
    $officeType = (int)($user['office_type'] ?? 0);
    foreach ($circles as $circle) {
        if ($officeType === 2 && (int)$circle['zone_id'] === (int)($user['zone_id'] ?? 0)) {
            $scopeOfficeOptions['circles'][] = ['value' => (string)$circle['id'], 'label' => (string)$circle['office_name'], 'meta' => ['zone' => (string)$circle['zone_id']]];
        }
    }
    foreach ($divisions as $division) {
        if (($officeType === 2 && (int)$division['zone_id'] === (int)($user['zone_id'] ?? 0)) || ($officeType === 3 && (int)$division['circle_id'] === (int)($user['circle_id'] ?? 0))) {
            $scopeOfficeOptions['divisions'][] = ['value' => (string)$division['id'], 'label' => (string)$division['office_name'], 'meta' => ['zone' => (string)$division['zone_id'], 'circle' => (string)$division['circle_id']]];
        }
    }
    foreach ($subdivisions as $subdivision) {
        if (
            ($officeType === 2 && (int)$subdivision['zone_id'] === (int)($user['zone_id'] ?? 0))
            || ($officeType === 3 && (int)$subdivision['circle_id'] === (int)($user['circle_id'] ?? 0))
            || ($officeType === 4 && (int)$subdivision['division_id'] === (int)($user['division_id'] ?? 0))
        ) {
            $scopeOfficeOptions['subdivisions'][] = ['value' => (string)$subdivision['id'], 'label' => (string)$subdivision['office_name'], 'meta' => ['zone' => (string)$subdivision['zone_id'], 'circle' => (string)$subdivision['circle_id'], 'division' => (string)$subdivision['division_id']]];
        }
    }
}
?>
<?php if (!$assetComparisonTableOnly && !$assetBoardFiltersOnly): ?>
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
                <?php if ($canModifyAssets && $canUseMyOfficeScope): ?>
                    <form method="post" action="index.php" class="inline-form">
                        <?= csrf_input(); ?>
                        <input type="hidden" name="action" value="asset_declare">
                        <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
                        <button type="submit" class="hero-declare-button" <?= !empty($declaration['declared_status']) ? 'disabled' : ''; ?>>Declare as Completed</button>
                    </form>
                <?php elseif (!$canUseMyOfficeScope): ?>
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
<?php endif; ?>

<?php if (!$assetComparisonTableOnly && !$assetBoardFiltersOnly && $showSegmentSelector): ?>
<section class="card segment-switch-card">
    <div class="toolbar-row scope-switch-row">
        <?php foreach ($segments as $segment): ?>
            <?php
                $segmentParams = $_GET;
                unset(
                    $segmentParams['page'],
                    $segmentParams['segment_id'],
                    $segmentParams['edit_asset'],
                    $segmentParams['asset_history']
                );
                $segmentParams['page'] = 'board';
                $segmentParams['segment_id'] = (int)$segment['id'];
                if (!is_superadmin()) {
                    $segmentParams['office_view_scope'] = $currentOfficeViewScope;
                }
            ?>
            <a href="index.php?<?= e(http_build_query($segmentParams)); ?>" class="button-link<?= $activeSegmentId === (int)$segment['id'] ? ' is-active' : ''; ?>">
                <?= e((string)$segment['segment_name']); ?>
                <?php if (asset_segment_is_new_batch($segment)): ?>
                    <span class="segment-new-badge">New</span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!$assetComparisonTableOnly && !$assetBoardFiltersOnly && $showScopeSwitchCard): ?>
<section class="card">
    <div class="toolbar-row scope-switch-row">
        <a href="index.php?<?= e(http_build_query(['page' => 'board', 'office_view_scope' => 'my_office', 'segment_id' => $activeSegmentId])); ?>" class="button-link<?= !$isUnderMeView ? ' is-active' : ''; ?>">My Office</a>
        <a href="index.php?<?= e(http_build_query(['page' => 'board', 'office_view_scope' => 'office_under_me', 'segment_id' => $activeSegmentId])); ?>" class="button-link<?= $isUnderMeView ? ' is-active' : ''; ?>">Office Under Me</a>
    </div>
</section>
<?php endif; ?>

<?php if (!$assetComparisonTableOnly && !$assetBoardFiltersOnly && !is_superadmin() && !$scopeAccessAvailable): ?>
<section class="card">
    <p class="hint">This segment is currently hidden for your office type by superadmin settings.</p>
</section>
<?php endif; ?>

<?php if (false && is_superadmin()): ?>
<?php if (!$assetComparisonTableOnly && $showFilterCard): ?>
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
<?php endif; ?>

<?php if ($showFilterCard): ?>
<section class="card">
    <h2>Filters</h2>
    <form method="get" action="index.php" id="asset-filters" class="grid board-filters-grid">
        <input type="hidden" name="page" value="board">
        <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
        <?php if (!is_superadmin()): ?><input type="hidden" name="office_view_scope" value="<?= e($currentOfficeViewScope); ?>"><?php endif; ?>
        <?php if ($showCategoryFilter): ?>
            <?php
                $activeCategoryValues = [];
                foreach (array_keys($activeFilterCatalog['categories']) as $categoryId) {
                    $activeCategoryValues[(string)$categoryId] = true;
                }
                $categoryPickerOptions = $buildSelectableFilterOptions(array_map(
                    static fn(array $category): array => ['value' => (string)$category['id'], 'label' => (string)$category['name']],
                    array_values($filterCatalog['categories'])
                ), $activeCategoryValues);
                $renderFilterPicker('category_id', 'Category', $categoryPickerOptions, (string)($filters['category_id'] ?? '0'));
            ?>
        <?php endif; ?>
        <?php if ($showSubcategoryFilter): ?>
            <?php
                $activeSubcategoryValues = [];
                foreach (array_keys($activeFilterCatalog['subcategories']) as $subcategoryId) {
                    $activeSubcategoryValues[(string)$subcategoryId] = true;
                }
                $subcategoryPickerOptions = $buildSelectableFilterOptions(array_map(
                    static fn(array $subcategory): array => [
                        'value' => (string)$subcategory['id'],
                        'label' => (string)$subcategory['name'],
                        'meta' => ['category' => (string)$subcategory['category_id']],
                    ],
                    array_values($filterCatalog['subcategories'])
                ), $activeSubcategoryValues);
                $renderFilterPicker('subcategory_id', 'Sub-category', $subcategoryPickerOptions, (string)($filters['subcategory_id'] ?? '0'));
            ?>
        <?php endif; ?>
        <?php if ($showZoneFilter): ?>
            <?php
                $activeZoneValues = [];
                foreach (array_keys($activeFilterCatalog['zones']) as $zoneId) {
                    $activeZoneValues[(string)$zoneId] = true;
                }
                $zonePickerOptions = $buildSelectableFilterOptions($scopeOfficeOptions['zones'], $activeZoneValues);
                $renderFilterPicker('zone_id', 'Zone', $zonePickerOptions, (string)$selectedZone);
            ?>
        <?php endif; ?>
        <?php if ($showCircleFilter): ?>
            <?php
                $activeCircleValues = [];
                foreach (array_keys($activeFilterCatalog['circles']) as $circleId) {
                    $activeCircleValues[(string)$circleId] = true;
                }
                $circlePickerOptions = $buildSelectableFilterOptions($scopeOfficeOptions['circles'], $activeCircleValues);
                $renderFilterPicker('circle_id', 'Circle', $circlePickerOptions, (string)$selectedCircle);
            ?>
        <?php endif; ?>
        <?php if ($showDivisionFilter): ?>
            <?php
                $activeDivisionValues = [];
                foreach (array_keys($activeFilterCatalog['divisions']) as $divisionId) {
                    $activeDivisionValues[(string)$divisionId] = true;
                }
                $divisionPickerOptions = $buildSelectableFilterOptions($scopeOfficeOptions['divisions'], $activeDivisionValues);
                $renderFilterPicker('division_id', 'Division', $divisionPickerOptions, (string)$selectedDivision);
            ?>
        <?php endif; ?>
        <?php if ($showSubdivisionFilter): ?>
            <?php
                $activeSubdivisionValues = [];
                foreach (array_keys($activeFilterCatalog['subdivisions']) as $subdivisionId) {
                    $activeSubdivisionValues[(string)$subdivisionId] = true;
                }
                $subdivisionPickerOptions = $buildSelectableFilterOptions($scopeOfficeOptions['subdivisions'], $activeSubdivisionValues);
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
                $fieldLabel = e((string)($uiFieldLabels[$fieldKey] ?? $field['label'])) . $fieldMandatoryMarker($field);
                $filterKey = 'field_filter_' . $fieldKey;
                $catalogField = $filterCatalog['fields'][$fieldKey] ?? null;
                $activeCatalogField = $activeFilterCatalog['fields'][$fieldKey] ?? null;
            ?>
            <?php if ($fieldType === 'date'): ?>
                <label>
                    <span class="field-label-row">
                        <span><?= $fieldLabel; ?> From</span>
                        <?php $renderFieldHelpButton($fieldHelpMeta[$fieldKey] ?? null, 'field-help-button field-help-inline', 'Field information'); ?>
                    </span>
                    <input type="date" name="<?= e($filterKey . '_from'); ?>" value="<?= e((string)($filters[$filterKey . '_from'] ?? '')); ?>">
                </label>
                <label>
                    <span class="field-label-row">
                        <span><?= $fieldLabel; ?> To</span>
                        <?php $renderFieldHelpButton($fieldHelpMeta[$fieldKey] ?? null, 'field-help-button field-help-inline', 'Field information'); ?>
                    </span>
                    <input type="date" name="<?= e($filterKey . '_to'); ?>" value="<?= e((string)($filters[$filterKey . '_to'] ?? '')); ?>">
                </label>
            <?php elseif ($fieldType === 'conditional'): ?>
                <?php $childField = get_asset_conditional_child_field((int)$field['id']); ?>
                <?php
                    $conditionalPrimaryOptions = [];
                    $activePrimaryValues = [];
                    $fullConditionalMap = asset_decode_conditional_map($field);
                    foreach (array_keys((array)($activeCatalogField['options'] ?? [])) as $optionValue) {
                        $activePrimaryValues[(string)$optionValue] = true;
                    }
                    foreach ($fullConditionalMap as $optionValue => $childValues) {
                        $conditionalPrimaryOptions[] = ['value' => (string)$optionValue, 'label' => (string)$optionValue, 'disabled' => !isset($activePrimaryValues[(string)$optionValue])];
                    }
                    if (!empty($activeCatalogField['has_blank'])) {
                        $activePrimaryValues['__blank__'] = true;
                    }
                    if (!empty($catalogField['has_blank']) || !empty($activeCatalogField['has_blank'])) {
                        $conditionalPrimaryOptions[] = ['value' => '__blank__', 'label' => 'Blank', 'disabled' => empty($activePrimaryValues['__blank__'])];
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
                        ],
                        $fieldHelpMeta[$fieldKey] ?? null
                    );
                ?>
                <?php if ($childField): ?>
                    <?php $childFilterKey = 'field_filter_' . $childField['field_key']; ?>
                    <?php
                        $childActiveField = $activeFilterCatalog['fields'][$childField['field_key']] ?? null;
                        $childOptions = [];
                        $seenChildOptions = [];
                        foreach ((array)$fullConditionalMap as $primaryValue => $children) {
                            foreach ((array)$children as $childValue) {
                                if (isset($seenChildOptions[$childValue])) {
                                    continue;
                                }
                                $seenChildOptions[$childValue] = true;
                                $childOptions[] = [
                                    'value' => (string)$childValue,
                                    'label' => (string)$childValue,
                                    'disabled' => !isset(($childActiveField['options'] ?? [])[(string)$childValue]),
                                    'meta' => ['primary' => (string)$primaryValue],
                                ];
                            }
                        }
                        if (!empty($childActiveField['has_blank'])) {
                            $childOptions[] = ['value' => '__blank__', 'label' => 'Blank', 'disabled' => false];
                        }
                        $renderFilterPicker(
                            $childFilterKey,
                            e((string)($uiFieldLabels[$childField['field_key']] ?? $childField['label'])) . $fieldMandatoryMarker($childField),
                            $childOptions,
                            (string)($filters[$childFilterKey] ?? ''),
                            ['data-filter-conditional-secondary' => (string)$fieldKey],
                            $fieldHelpMeta[$childField['field_key']] ?? null
                        );
                    ?>
                <?php endif; ?>
            <?php elseif (!empty($catalogField['options'])): ?>
                <?php
                    $fieldPickerOptions = [];
                    $activeFieldValues = [];
                    $fullFieldOptions = [];
                    foreach (array_keys((array)($activeCatalogField['options'] ?? [])) as $optionValue) {
                        $activeFieldValues[(string)$optionValue] = true;
                    }
                    if (in_array($fieldType, ['dropdown', 'yes_no'], true)) {
                        foreach (get_asset_field_options((int)$field['id']) as $option) {
                            $fullFieldOptions[(string)$option['option_value']] = (string)$option['option_value'];
                        }
                    }
                    if (!$fullFieldOptions) {
                        $fullFieldOptions = (array)($catalogField['options'] ?? []);
                    }
                    foreach ($fullFieldOptions as $optionValue => $optionLabel) {
                        $fieldPickerOptions[] = ['value' => (string)$optionValue, 'label' => (string)$optionLabel, 'disabled' => !isset($activeFieldValues[(string)$optionValue])];
                    }
                    if (!empty($catalogField['has_blank']) || !empty($activeCatalogField['has_blank'])) {
                        $fieldPickerOptions[] = ['value' => '__blank__', 'label' => 'Blank', 'disabled' => empty($activeCatalogField['has_blank'])];
                    }
                    $renderFilterPicker($filterKey, $fieldLabel, $fieldPickerOptions, (string)($filters[$filterKey] ?? ''), [], $fieldHelpMeta[$fieldKey] ?? null);
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
<?php endif; ?>

<?php if (!$assetBoardFiltersOnly && !$assetComparisonTableOnly && !is_superadmin() && $showBoardContent): ?>
<section class="card">
    <div class="toolbar-row">
        <?php if ($showAddInfoButton): ?>
            <button type="button" data-modal="asset-modal">+Add Info</button>
        <?php endif; ?>
        <?php if ($canModifyAssets && !$isUnderMeView && $bulkImportEnabled): ?><button type="button" data-modal="import-modal">Bulk Entry</button><?php endif; ?>
        <?php if (!$isUnderMeView && $bulkImportEnabled): ?>
            <a href="asset_template.php?<?= e(http_build_query(['segment_id' => $activeSegmentId])); ?>" class="button-link">Excel Template</a>
        <?php endif; ?>
        <a href="index.php?<?= e(http_build_query(['page' => 'download_user_scope_excel', 'office_view_scope' => $currentOfficeViewScope])); ?>" class="button-link btn-secondary">Download Data</a>
    </div>
    <?php if ($canModifyAssets && !$isUnderMeView && !$showAddInfoButton): ?>
        <p class="hint">This segment currently has only fixed common-row categories. New manual rows are blocked here.</p>
    <?php endif; ?>
</section>
<?php elseif (!$assetBoardFiltersOnly): ?>
<section class="card">
    <div class="toolbar-row">
        <a href="asset_template.php?<?= e(http_build_query(['segment_id' => $activeSegmentId])); ?>" class="button-link">Excel Template</a>
        <button type="button" data-modal="download-data-modal" class="btn-secondary" <?= $downloadModuleReady ? '' : 'disabled'; ?>>Download Data</button>
    </div>
    <?php if (!$downloadModuleReady): ?>
        <p class="hint">Set Level 1 fields in Download Manager before using downloads.</p>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php if (!$assetComparisonTableOnly && !$assetBoardFiltersOnly && !is_superadmin() && $showBoardContent && $canModifyAssets && !$isUnderMeView): ?>
<form method="post" action="index.php" class="asset-delete-form" id="asset-delete-form">
    <?= csrf_input(); ?>
    <input type="hidden" name="action" value="asset_bulk_delete">
    <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
    <input type="hidden" name="office_view_scope" value="<?= e($currentOfficeViewScope); ?>">
</form>
<?php endif; ?>
<?php if (!$assetBoardFiltersOnly && $showBoardContent): ?>
    <section class="board-grid asset-category-grid">
        <?php foreach ($groupedAssets as $group): ?>
            <?php
                $category = $group['category'];
                $assets = $group['assets'];
                $totalAssetCount = count($assets);
                $paginatedAssets = $paginationEnabled ? array_slice($assets, 0, $paginationBatchSize) : $assets;
                $paginationHasMore = $paginationEnabled && $totalAssetCount > count($paginatedAssets);
                $prefCategoryId = asset_table_preference_category_id((int)$category['id'], $currentOfficeViewScope);
                $visibleColumnKeys = resolve_asset_table_visible_column_keys($prefCategoryId, $availableTableColumns, $columnPreferenceMap);
                $timeFilterForCategory = $timeFilterActive && (int)$category['id'] === $timeFilterCategoryId;
                $refreshParams = array_diff_key(
                    array_merge($_GET, ['page' => 'board', 'office_view_scope' => $currentOfficeViewScope, 'segment_id' => $activeSegmentId]),
                    [
                        'sort_col' => true,
                        'sort_dir' => true,
                        'time_filter_category_id' => true,
                        'time_filter_field' => true,
                        'time_filter_start' => true,
                        'time_filter_end' => true,
                        'time_filter_from_beginning' => true,
                        'time_filter_till_now' => true,
                    ]
                );
                $timeFilterFieldsForCategory = [];
                foreach ($fields as $field) {
                    if ((int)($field['active_status'] ?? 0) !== 1 || (int)($field['is_displayed'] ?? 0) !== 1) {
                        continue;
                    }
                    if (empty($visibleColumnKeys[$field['field_key']])) {
                        continue;
                    }
                    $timeFilterFieldsForCategory[] = $field;
                }
                $visibleFieldCount = 0;
                foreach ($fields as $field) {
                    if ((int)$field['is_displayed'] === 1 && (int)$field['active_status'] === 1 && !empty($visibleColumnKeys[$field['field_key']])) {
                        $visibleFieldCount++;
                        if ((string)($field['data_type'] ?? '') === 'bimh' && !empty($visibleColumnKeys[$field['field_key'] . '__est_name'])) {
                            $visibleFieldCount++;
                        }
                    }
                }
                $categoryTotalRowEnabled = asset_category_total_row_enabled((int)$category['id'], $activeSegmentId);
                $visibleNumberFields = array_values(array_filter($fields, static function (array $field) use ($visibleColumnKeys): bool {
                    return (int)($field['active_status'] ?? 0) === 1
                        && (int)($field['is_displayed'] ?? 0) === 1
                        && !empty($visibleColumnKeys[(string)($field['field_key'] ?? '')])
                        && (string)($field['data_type'] ?? '') === 'number';
                }));
                $totalLabelFieldKey = '';
                foreach ($fields as $field) {
                    $fieldKey = (string)($field['field_key'] ?? '');
                    if ((int)($field['active_status'] ?? 0) !== 1 || (int)($field['is_displayed'] ?? 0) !== 1 || empty($visibleColumnKeys[$fieldKey])) {
                        continue;
                    }
                    if ((string)($field['data_type'] ?? '') === 'number') {
                        continue;
                    }
                    $totalLabelFieldKey = $fieldKey;
                    break;
                }
                $categoryNumberTotals = $categoryTotalRowEnabled && $visibleNumberFields !== []
                    ? get_asset_number_totals_for_assets(array_map(static fn(array $asset): int => (int)$asset['id'], $group['assets']), $visibleNumberFields)
                    : [];
            ?>
            <section class="card operational-budget-card">
                <div class="card-head">
                    <h2><?= e($category['name']); ?></h2>
                    <?php if (!$assetComparisonTableOnly): ?>
                        <div class="card-head-actions">
                            <div class="muted"><?= $totalAssetCount; ?> row(s)</div>
                            <a href="index.php?<?= e(http_build_query($refreshParams)); ?>" class="btn-small button-link">Refresh</a>
                            <button type="button" class="btn-small" data-modal="columns-modal-<?= (int)$category['id']; ?>">Columns</button>
                            <button type="button" class="btn-small<?= $timeFilterForCategory ? ' btn-secondary' : ''; ?>" data-modal="time-filter-modal-<?= (int)$category['id']; ?>">Time Filter</button>
                        </div>
                    <?php endif; ?>
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
                                    $timeFilterIndicator = static function (string $columnKey) use ($timeFilterForCategory, $timeFilterFieldKey): string {
                                        if (!$timeFilterForCategory || $timeFilterFieldKey !== $columnKey) {
                                            return '';
                                        }
                                        return ' ⏱';
                                    };
                                    $headerCellClass = static function (string $columnKey) use ($sortColumn, $timeFilterForCategory, $timeFilterFieldKey): string {
                                        return ($sortColumn === $columnKey || ($timeFilterForCategory && $timeFilterFieldKey === $columnKey)) ? 'sort-active' : '';
                                    };
                                ?>
                                <th class="<?= $headerCellClass('__sl'); ?>"><a href="<?= e($headerSortUrl('__sl')); ?>" class="<?= trim($sortClass('__sl')); ?>">SL No<?= e($sortIndicator('__sl')); ?></a></th>
                                <?php if ($showAssetNumber && !empty($visibleColumnKeys['asset_number'])): ?><th class="<?= $headerCellClass('asset_number'); ?>"><a href="<?= e($headerSortUrl('asset_number')); ?>" class="<?= trim($sortClass('asset_number')); ?>">Asset Number<?= e($sortIndicator('asset_number')); ?></a></th><?php endif; ?>
                                <?php if ((is_superadmin() || $isUnderMeView) && !empty($visibleColumnKeys['office_name'])): ?><th class="<?= $headerCellClass('office_name'); ?>"><a href="<?= e($headerSortUrl('office_name')); ?>" class="<?= trim($sortClass('office_name')); ?>">Office<?= e($sortIndicator('office_name')); ?></a></th><?php endif; ?>
                                <?php if ($subcategoryEnabled && !empty($visibleColumnKeys['subcategory_name'])): ?><th class="<?= $headerCellClass('subcategory_name'); ?>"><a href="<?= e($headerSortUrl('subcategory_name')); ?>" class="<?= trim($sortClass('subcategory_name')); ?>">Sub-category<?= e($sortIndicator('subcategory_name')); ?></a></th><?php endif; ?>
                                <?php if (!empty($visibleColumnKeys['data_provider'])): ?><th class="<?= $headerCellClass('data_provider'); ?>"><a href="<?= e($headerSortUrl('data_provider')); ?>" class="<?= trim($sortClass('data_provider')); ?>">Data Provider<?= e($sortIndicator('data_provider')); ?></a></th><?php endif; ?>
                                <?php foreach ($fields as $field): ?>
                                    <?php if ((int)$field['is_displayed'] === 1 && (int)$field['active_status'] === 1 && !empty($visibleColumnKeys[$field['field_key']])): ?>
                                        <?php $fieldSortKey = (string)$field['field_key']; ?>
                                        <?php $fieldFillColor = asset_normalize_field_fill_color((string)($field['fill_color'] ?? '')); ?>
                                        <?php $fieldHeaderStyle = $fieldFillColor !== '' ? ' style="background:' . e($fieldFillColor) . ';"' : ''; ?>
                                        <th class="<?= $headerCellClass($fieldSortKey); ?>"<?= $fieldHeaderStyle; ?>>
                                            <div class="field-head-row">
                                                <a href="<?= e($headerSortUrl($fieldSortKey)); ?>" class="<?= trim($sortClass($fieldSortKey)); ?>"><?= e((string)($uiFieldLabels[$field['field_key']] ?? $field['label'])); ?><?= $fieldMandatoryMarker($field); ?><?= e($sortIndicator($fieldSortKey)); ?><?= e($timeFilterIndicator($fieldSortKey)); ?></a>
                                                <?php $renderFieldHelpButton($fieldHelpMeta[$field['field_key']] ?? null, 'field-help-button field-help-table', 'Field information'); ?>
                                            </div>
                                        </th>
                                        <?php if ((string)($field['data_type'] ?? '') === 'bimh' && !empty($visibleColumnKeys[$field['field_key'] . '__est_name'])): ?>
                                            <th class="<?= $headerCellClass($fieldSortKey . '__est_name'); ?>"<?= $fieldHeaderStyle; ?>>
                                                <a href="<?= e($headerSortUrl($fieldSortKey . '__est_name')); ?>" class="<?= trim($sortClass($fieldSortKey . '__est_name')); ?>">Est Name<?= e($sortIndicator($fieldSortKey . '__est_name')); ?></a>
                                            </th>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php if ($showActionColumn): ?><th>Action</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody
                            data-asset-table-body
                            data-category-id="<?= (int)$category['id']; ?>"
                            data-loaded-count="<?= count($paginatedAssets); ?>"
                            data-total-count="<?= $totalAssetCount; ?>"
                        >
                            <?php
                                $assets = $paginatedAssets;
                                $rowNumberOffset = 0;
                                $renderEmptyState = true;
                                require __DIR__ . '/board_table_rows_fragment.php';
                                $assets = $group['assets'];
                            ?>
                        </tbody>
                        <?php if ($categoryTotalRowEnabled && $visibleNumberFields !== []): ?>
                            <tfoot>
                                <tr class="asset-total-row">
                                    <?php if (!is_superadmin() && $canModifyAssets && !$isUnderMeView): ?><td>Total</td><?php else: ?><td>Total</td><?php endif; ?>
                                    <?php if ($showAssetNumber && !empty($visibleColumnKeys['asset_number'])): ?><td></td><?php endif; ?>
                                    <?php if ((is_superadmin() || $isUnderMeView) && !empty($visibleColumnKeys['office_name'])): ?><td></td><?php endif; ?>
                                    <?php if ($subcategoryEnabled && !empty($visibleColumnKeys['subcategory_name'])): ?><td></td><?php endif; ?>
                                    <?php if (!empty($visibleColumnKeys['data_provider'])): ?><td></td><?php endif; ?>
                                    <?php foreach ($fields as $field): ?>
                                        <?php if ((int)$field['is_displayed'] === 1 && (int)$field['active_status'] === 1 && !empty($visibleColumnKeys[$field['field_key']])): ?>
                                            <?php $fieldKey = (string)$field['field_key']; ?>
                                            <?php if ((string)($field['data_type'] ?? '') === 'number'): ?>
                                                <td><?= e((string)($categoryNumberTotals[$fieldKey] ?? '0')); ?></td>
                                            <?php else: ?>
                                                <td></td>
                                            <?php endif; ?>
                                            <?php if ((string)($field['data_type'] ?? '') === 'bimh' && !empty($visibleColumnKeys[$field['field_key'] . '__est_name'])): ?>
                                                <td></td>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <?php if ($showActionColumn): ?><td></td><?php endif; ?>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
                <?php if (!$assetComparisonTableOnly && $paginationHasMore): ?>
                    <?php
                        $loadMoreParams = ['page' => 'asset_table_rows', 'segment_id' => $activeSegmentId, 'category_id' => (int)$category['id']];
                        if (!is_superadmin()) {
                            $loadMoreParams['office_view_scope'] = $currentOfficeViewScope;
                        }
                    ?>
                    <div class="table-load-more-footer" data-table-pagination-footer>
                        <div class="table-load-more-fade"></div>
                        <button
                            type="button"
                            class="btn-small table-load-more-button"
                            data-load-more-table
                            data-load-url="<?= e('index.php?' . http_build_query($loadMoreParams)); ?>"
                            data-batch-size="<?= $paginationBatchSize; ?>"
                        >Load More</button>
                    </div>
                <?php endif; ?>
            </section>
            <?php if (!$assetComparisonTableOnly): ?>
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
                            <?php if (count($categories) > 1): ?>
                                <button type="submit" name="apply_to_all" value="1" class="btn-secondary">Apply Visibility to All Tables</button>
                            <?php endif; ?>
                            <button type="button" class="modal-close" data-close="columns-modal-<?= (int)$category['id']; ?>">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-backdrop" id="time-filter-modal-<?= (int)$category['id']; ?>" aria-hidden="true">
                <div class="modal-card time-filter-modal-card" role="dialog" aria-modal="true" aria-labelledby="time-filter-title-<?= (int)$category['id']; ?>">
                    <div class="flash-modal-head">
                        <h3 id="time-filter-title-<?= (int)$category['id']; ?>">Time Filter: <?= e($category['name']); ?></h3>
                        <button type="button" class="welcome-modal-close modal-close" data-close="time-filter-modal-<?= (int)$category['id']; ?>" aria-label="Close">×</button>
                    </div>
                    <form method="get" action="index.php" class="grid time-filter-form">
                        <input type="hidden" name="page" value="board">
                        <?php foreach ($_GET as $paramKey => $paramValue): ?>
                            <?php if (in_array((string)$paramKey, ['page', 'time_filter_category_id', 'time_filter_field', 'time_filter_start', 'time_filter_end', 'time_filter_from_beginning', 'time_filter_till_now'], true)): ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <?php if (is_array($paramValue)): ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <input type="hidden" name="<?= e((string)$paramKey); ?>" value="<?= e((string)$paramValue); ?>">
                        <?php endforeach; ?>
                        <input type="hidden" name="time_filter_category_id" value="<?= (int)$category['id']; ?>">
                        <label>Field Name
                            <select name="time_filter_field" required>
                                <option value="">Select field</option>
                                <?php foreach ($timeFilterFieldsForCategory as $timeField): ?>
                                    <?php $timeFieldKey = (string)$timeField['field_key']; ?>
                                    <option value="<?= e($timeFieldKey); ?>" <?= $timeFilterForCategory && $timeFilterFieldKey === $timeFieldKey ? 'selected' : ''; ?>>
                                        <?= e((string)($uiFieldLabels[$timeFieldKey] ?? $timeField['label'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <div class="time-filter-datetime-row">
                            <label>Start Time
                                <input type="datetime-local" name="time_filter_start" value="<?= e($timeFilterForCategory ? $formatBoardDateTimeInput($timeFilterStartRaw) : ''); ?>" <?= $timeFilterForCategory && $timeFilterFromBeginning ? 'disabled' : ''; ?> data-time-filter-start>
                            </label>
                            <label class="inline-check">
                                <input type="checkbox" name="time_filter_from_beginning" value="1" <?= $timeFilterForCategory && $timeFilterFromBeginning ? 'checked' : ''; ?> data-time-filter-from-beginning>
                                <span>From the beginning</span>
                            </label>
                        </div>
                        <div class="time-filter-datetime-row">
                            <label>End Time
                                <input type="datetime-local" name="time_filter_end" value="<?= e($timeFilterForCategory ? $formatBoardDateTimeInput($timeFilterEndRaw) : ''); ?>" <?= $timeFilterForCategory && $timeFilterTillNow ? 'disabled' : ''; ?> data-time-filter-end>
                            </label>
                            <label class="inline-check">
                                <input type="checkbox" name="time_filter_till_now" value="1" <?= $timeFilterForCategory && $timeFilterTillNow ? 'checked' : ''; ?> data-time-filter-till-now>
                                <span>Till now</span>
                            </label>
                        </div>
                        <div class="modal-actions">
                            <button type="submit">Apply Filter</button>
                            <button type="button" class="modal-close" data-close="time-filter-modal-<?= (int)$category['id']; ?>">Close</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
<?php if (!is_superadmin() && $showBoardContent && $canModifyAssets && !$isUnderMeView): ?>
        <div class="bulk-actions">
            <button type="button" class="btn-danger" data-modal="asset-delete-confirm-modal">Soft Delete Selected</button>
        </div>
    <?php endif; ?>

<?php if (!is_superadmin() && $showBoardContent && $canModifyAssets && !$isUnderMeView): ?>
<div class="modal-backdrop" id="asset-delete-confirm-modal" aria-hidden="true">
    <div class="modal-card asset-delete-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="asset-delete-confirm-title">
        <h3 id="asset-delete-confirm-title">Confirm Soft Delete</h3>
        <p>Selected row/rows are going to be deleted. If you accidentally delete anything, contact developer to reinstate the rows.</p>
        <p>Now will I proceed to deletion?</p>
        <div class="modal-actions">
            <button type="button" class="btn-danger" id="asset-delete-confirm-proceed">Proceed</button>
            <button type="button" class="modal-close" data-close="asset-delete-confirm-modal">Cancel</button>
        </div>
    </div>
</div>

<div class="modal-backdrop<?= $editingAsset ? ' open' : ''; ?>" id="asset-modal" aria-hidden="<?= $editingAsset ? 'false' : 'true'; ?>">
    <div class="modal-card asset-entry-modal" role="dialog" aria-modal="true" aria-labelledby="asset-modal-title">
        <h3 id="asset-modal-title"><?= $editingAsset ? 'Edit Asset' : 'Add Asset'; ?></h3>
        <form method="post" action="index.php" class="grid" enctype="multipart/form-data">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="asset_save">
            <input type="hidden" name="asset_id" value="<?= e((string)($editingAsset['id'] ?? '0')); ?>">
            <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
            <?php if ($editingAssetIsGenerated): ?>
                <p class="hint">This is a generated common row. Common source-driven fields are locked here and stay synchronized from their parent source.</p>
            <?php elseif (!$editingAsset && !$hasManualAddCategory): ?>
                <p class="hint">No category in this segment allows manual row creation right now.</p>
            <?php endif; ?>
            <?php if (!$categorySelectionEnabled && $defaultCategoryId > 0): ?>
                <input type="hidden" name="category_id" value="<?= e((string)$defaultCategoryId); ?>">
            <?php endif; ?>
            <?php if ($categorySelectionEnabled): ?>
            <label>Category *
                <select name="category_id" id="asset-category-select" required <?= $editingAssetIsGenerated ? 'disabled' : ''; ?>>
                    <option value="">Select</option>
                    <?php foreach ($categories as $category): ?>
                        <?php
                            $categoryId = (int)$category['id'];
                            $categoryManualAllowed = !empty($manualAddAllowedCategoryIds[$categoryId]);
                            $optionDisabled = !$editingAsset && !$categoryManualAllowed;
                        ?>
                        <option
                            value="<?= e((string)$categoryId); ?>"
                            <?= ((int)($editingAsset['category_id'] ?? $defaultCategoryId) === $categoryId) ? 'selected' : ''; ?>
                            <?= $optionDisabled ? 'disabled' : ''; ?>
                        ><?= e($category['name']); ?><?= $optionDisabled ? ' [Fixed]' : ''; ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($editingAssetIsGenerated): ?>
                    <input type="hidden" name="category_id" value="<?= e((string)($editingAsset['category_id'] ?? 0)); ?>">
                <?php endif; ?>
            </label>
            <?php endif; ?>
            <?php if ($subcategoryEnabled): ?>
                <label>Sub-category *
                    <select name="subcategory_id" id="asset-subcategory-select" required <?= $editingAssetIsGenerated ? 'disabled' : ''; ?>>
                        <option value="">Select</option>
                        <?php foreach ($subcategories as $subcategory): ?>
                            <option value="<?= e((string)$subcategory['id']); ?>" data-category="<?= e((string)$subcategory['category_id']); ?>" <?= (int)($editingAsset['subcategory_id'] ?? 0) === (int)$subcategory['id'] ? 'selected' : ''; ?>><?= e($subcategory['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($editingAssetIsGenerated): ?>
                        <input type="hidden" name="subcategory_id" value="<?= e((string)($editingAsset['subcategory_id'] ?? 0)); ?>">
                    <?php endif; ?>
                </label>
            <?php endif; ?>
            <?php foreach ($fields as $field): ?>
                <?php if ((int)$field['active_status'] !== 1) { continue; } ?>
                <?php $fieldKey = (string)$field['field_key']; ?>
                <?php $isLockedCommonField = $editingAsset && !empty($editingAssetLockedFieldKeys[$fieldKey]); ?>
                <label>
                    <span class="field-label-row">
                        <span><?= e((string)($uiFieldLabels[$fieldKey] ?? $field['label'])); ?><?= $fieldMandatoryMarker($field); ?><?= $isLockedCommonField ? ' [Locked]' : ''; ?></span>
                        <?php $renderFieldHelpButton($fieldHelpMeta[$fieldKey] ?? null, 'field-help-button field-help-inline', 'Field information'); ?>
                    </span>
                    <?php $value = (string)($editValues[$fieldKey] ?? ''); ?>
                    <?php $textMaxLength = (int)($field['text_max_length'] ?? 0); ?>
                    <?php if ($isLockedCommonField): ?>
                        <input type="hidden" name="fields[<?= e($fieldKey); ?>]" value="<?= e($value); ?>">
                        <input type="text" value="<?= e($value); ?>" readonly>
                        <span class="hint">This value is controlled by the linked common row source.</span>
                    <?php elseif ($field['data_type'] === 'calculation'): ?>
                        <input type="text" value="<?= e($value); ?>" readonly>
                        <span class="hint">This value is calculated automatically.</span>
                    <?php elseif ($field['data_type'] === 'file'): ?>
                        <?php
                            $fileRule = get_asset_field_file_rule((int)$field['id']);
                            $fieldFiles = $editFiles[$fieldKey] ?? [];
                            $accept = implode(',', array_map(static fn(string $ext): string => '.' . $ext, asset_parse_extensions_string((string)$fileRule['allowed_extensions'])));
                            $showRemoveCheckbox = (int)$fileRule['is_multiple'] === 1 || !asset_is_input_required($field);
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
                        <input type="date" name="fields[<?= e($field['field_key']); ?>]" value="<?= e($value); ?>" <?= asset_is_input_required($field) ? 'required' : ''; ?>>
                    <?php elseif ($field['data_type'] === 'number'): ?>
                        <input type="number" step="0.01" name="fields[<?= e($field['field_key']); ?>]" value="<?= e($value); ?>" <?= asset_is_input_required($field) ? 'required' : ''; ?>>
                    <?php elseif ($field['data_type'] === 'bimh'): ?>
                        <?php
                            $estNameValue = (string)($editValues[$field['field_key'] . '__est_name'] ?? asset_bimh_est_name_for_id($value));
                            $renderBimhFieldControl(
                                'fields[' . $field['field_key'] . ']',
                                (string)$field['field_key'],
                                $value,
                                $estNameValue,
                                asset_is_input_required($field)
                            );
                        ?>
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
                            <?= asset_is_input_required($field) ? 'required' : ''; ?>
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
                                <option value="<?= e($option['option_value']); ?>" <?= asset_option_values_match($value, (string)$option['option_value']) ? 'selected' : ''; ?>><?= e($option['option_label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($field['data_type'] === 'yes_no'): ?>
                        <select name="fields[<?= e($field['field_key']); ?>]" <?= asset_is_input_required($field) ? 'required' : ''; ?>>
                            <option value="">Select</option>
                            <option value="Yes" <?= $value === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                            <option value="No" <?= $value === 'No' ? 'selected' : ''; ?>>No</option>
                        </select>
                    <?php elseif ($field['field_key'] === 'remarks'): ?>
                        <textarea name="fields[<?= e($field['field_key']); ?>]" rows="3" <?= $textMaxLength > 0 ? 'maxlength="' . e((string)$textMaxLength) . '" data-char-limit="' . e((string)$textMaxLength) . '"' : ''; ?>><?= e($value); ?></textarea>
                        <?php if ($textMaxLength > 0): ?><span class="hint char-count-hint" data-char-count-target></span><?php endif; ?>
                    <?php else: ?>
                        <input type="text" name="fields[<?= e($field['field_key']); ?>]" value="<?= e($value); ?>" <?= asset_is_input_required($field) ? 'required' : ''; ?> <?= ($field['data_type'] === 'text' && $textMaxLength > 0) ? 'maxlength="' . e((string)$textMaxLength) . '" data-char-limit="' . e((string)$textMaxLength) . '"' : ''; ?>>
                        <?php if ((string)$field['data_type'] === 'text' && $textMaxLength > 0): ?><span class="hint char-count-hint" data-char-count-target></span><?php endif; ?>
                    <?php endif; ?>
                </label>
            <?php endforeach; ?>
            <div class="modal-actions">
                <?php if ($editingAsset || $hasManualAddCategory): ?>
                    <button type="submit">Save</button>
                <?php endif; ?>
                <button type="button" class="modal-close" data-close="asset-modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php if ($bulkImportEnabled): ?>
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
                <?php if ($hasManualAddCategory): ?>
                    <button type="button" id="import-review-add-row">+Add Row</button>
                <?php endif; ?>
                <button type="submit">Save Validated Rows</button>
            </div>
            <p class="import-review-summary" id="import-review-summary">Number of Rows need attention - 0</p>
            <div class="table-wrap">
                <table class="audit-table">
                    <thead>
                        <tr>
                            <th>Row</th>
                            <?php if ($categorySelectionEnabled): ?><th>Category</th><?php endif; ?>
                            <?php if ($subcategoryEnabled): ?><th>Sub-category</th><?php endif; ?>
                            <?php foreach ($fields as $field): ?>
                                <?php if (asset_field_is_import_template_visible($field)): ?>
                                    <th>
                                        <div class="field-head-row">
                                            <span><?= e((string)($uiFieldLabels[$field['field_key']] ?? $field['label'])); ?><?= $fieldMandatoryMarker($field); ?></span>
                                            <?php $renderFieldHelpButton($fieldHelpMeta[$field['field_key']] ?? null, 'field-help-button field-help-table', 'Field information'); ?>
                                        </div>
                                    </th>
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
                                    <input type="hidden" name="rows[<?= $rowIndex; ?>][target_asset_id]" value="<?= e((string)($row['target_asset_id'] ?? 0)); ?>">
                                </td>
                                <?php if ($categorySelectionEnabled): ?>
                                <td class="<?= !empty($row['errors']['category_id']) ? 'cell-error' : 'cell-valid'; ?>">
                                    <select class="review-input" data-review-role="category" name="rows[<?= $rowIndex; ?>][category]">
                                        <option value="">Select</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= e($category['name']); ?>" <?= strcasecmp((string)$row['category'], (string)$category['name']) === 0 ? 'selected' : ''; ?>><?= e($category['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <?php endif; ?>
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
                                    <?php if (asset_field_is_import_template_visible($field)): ?>
                                        <?php $fieldError = $row['errors'][$field['field_key']] ?? null; ?>
                                        <td class="<?= $fieldError ? 'cell-error' : 'cell-valid'; ?>">
                                            <?php $fieldValue = (string)($row['fields'][$field['field_key']] ?? ''); ?>
                                            <?php if (in_array($field['data_type'], ['dropdown', 'yes_no', 'conditional'], true)): ?>
                                                <select
                                                    class="review-input"
                                                    data-review-role="field"
                                                    data-field-key="<?= e($field['field_key']); ?>"
                                                    data-field-type="<?= e($field['data_type']); ?>"
                                                    data-required="<?= asset_is_input_required($field) ? '1' : '0'; ?>"
                                                    data-number-format-rule="<?= e((string)($field['number_format_rule'] ?? '')); ?>"
                                                    data-text-max-length="<?= e((string)((int)($field['text_max_length'] ?? 0))); ?>"
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
                                                        <option value="<?= e((string)$option['option_value']); ?>" <?= asset_option_values_match($fieldValue, (string)$option['option_value']) ? 'selected' : ''; ?>><?= e((string)$option['option_label']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php elseif ($field['data_type'] === 'calculation'): ?>
                                                <input
                                                    type="text"
                                                    class="review-input"
                                                    data-review-role="field"
                                                    data-field-key="<?= e($field['field_key']); ?>"
                                                    data-field-type="<?= e($field['data_type']); ?>"
                                                    readonly
                                                    name="rows[<?= $rowIndex; ?>][fields][<?= e($field['field_key']); ?>]"
                                                    value="<?= e($fieldValue); ?>">
                                            <?php elseif ($field['data_type'] === 'text'): ?>
                                                <textarea
                                                    class="review-input review-textarea"
                                                    data-review-role="field"
                                                    data-field-key="<?= e($field['field_key']); ?>"
                                                    data-field-type="<?= e($field['data_type']); ?>"
                                                    data-required="<?= asset_is_input_required($field) ? '1' : '0'; ?>"
                                                    data-number-format-rule="<?= e((string)($field['number_format_rule'] ?? '')); ?>"
                                                    data-text-max-length="<?= e((string)((int)($field['text_max_length'] ?? 0))); ?>"
                                                    name="rows[<?= $rowIndex; ?>][fields][<?= e($field['field_key']); ?>]"
                                                    <?= (int)($field['text_max_length'] ?? 0) > 0 ? 'maxlength="' . e((string)((int)$field['text_max_length'])) . '"' : ''; ?>
                                                    rows="3"
                                                ><?= e($fieldValue); ?></textarea>
                                            <?php elseif ($field['data_type'] === 'bimh'): ?>
                                                <?php
                                                    $renderBimhFieldControl(
                                                        'rows[' . $rowIndex . '][fields][' . $field['field_key'] . ']',
                                                        (string)$field['field_key'],
                                                        $fieldValue,
                                                        asset_bimh_est_name_for_id($fieldValue),
                                                        asset_is_input_required($field),
                                                        'review-input',
                                                        [
                                                            'data-review-role' => 'field',
                                                            'data-field-type' => (string)$field['data_type'],
                                                            'data-required' => asset_is_input_required($field) ? '1' : '0',
                                                            'data-number-format-rule' => (string)($field['number_format_rule'] ?? ''),
                                                            'data-text-max-length' => (string)((int)($field['text_max_length'] ?? 0)),
                                                        ]
                                                    );
                                                ?>
                                            <?php else: ?>
                                                <input
                                                    type="<?= $field['data_type'] === 'number' ? 'number' : ($field['data_type'] === 'date' ? 'date' : 'text'); ?>"
                                                    <?= $field['data_type'] === 'number' ? 'step="0.01"' : ''; ?>
                                                    class="review-input"
                                                    data-review-role="field"
                                                    data-field-key="<?= e($field['field_key']); ?>"
                                                    data-field-type="<?= e($field['data_type']); ?>"
                                                    data-required="<?= asset_is_input_required($field) ? '1' : '0'; ?>"
                                                    data-number-format-rule="<?= e((string)($field['number_format_rule'] ?? '')); ?>"
                                                    data-text-max-length="<?= e((string)((int)($field['text_max_length'] ?? 0))); ?>"
                                                    name="rows[<?= $rowIndex; ?>][fields][<?= e($field['field_key']); ?>]"
                                                    <?= ((string)$field['data_type'] === 'text' && (int)($field['text_max_length'] ?? 0) > 0) ? 'maxlength="' . e((string)((int)$field['text_max_length'])) . '"' : ''; ?>
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

<?php if (false): ?>
<div class="modal-backdrop" id="download-data-modal" aria-hidden="true">
    <div class="modal-card modal-wide download-data-modal-card" role="dialog" aria-modal="true" aria-labelledby="download-data-title">
        <div class="flash-modal-head">
            <h3 id="download-data-title">Download Data</h3>
            <button type="button" class="welcome-modal-close modal-close" data-close="download-data-modal" aria-label="Close">×</button>
        </div>
        <?php
            $renderDownloadChoiceGroup = static function (string $name, array $options, array $selected = [], string $blankLabel = ''): void {
                $selectedLookup = array_flip(array_map('strval', $selected));
                if (!$options) {
                    echo '<p class="muted">No options available.</p>';
                    return;
                }
                echo '<div class="download-choice-grid">';
                foreach ($options as $value => $label) {
                    $value = (string)$value;
                    $label = (string)$label;
                    $isBlank = $value === '__blank__';
                    echo '<label class="inline-check download-inline-check">';
                    echo '<input type="checkbox" name="' . e($name) . '[]" value="' . e($value) . '"' . (isset($selectedLookup[$value]) ? ' checked' : '') . '>';
                    echo '<span>' . e($isBlank && $blankLabel !== '' ? $blankLabel : $label) . '</span>';
                    echo '</label>';
                }
                echo '</div>';
            };
            $renderDownloadFilterControl = static function (string $inputBase, array $filterMeta, string $mode = 'segment') use ($renderDownloadChoiceGroup): void {
                $type = (string)($filterMeta['data_type'] ?? 'text');
                if ($type === 'office') {
                    $zones = (array)($filterMeta['zones'] ?? []);
                    $circles = (array)($filterMeta['circles'] ?? []);
                    $divisions = (array)($filterMeta['divisions'] ?? []);
                    $subdivisions = (array)($filterMeta['subdivisions'] ?? []);
                    if (!$zones && !$circles && !$divisions && !$subdivisions) {
                        echo '<p class="muted">No options available.</p>';
                        return;
                    }
                    echo '<div class="download-office-bulk-controls download-choice-grid">';
                    echo '<label class="inline-check download-inline-check">';
                    echo '<input type="checkbox" data-office-bulk-toggle="zone">';
                    echo '<span>all zones</span>';
                    echo '</label>';
                    echo '<label class="inline-check download-inline-check">';
                    echo '<input type="checkbox" data-office-bulk-toggle="circle">';
                    echo '<span>all circles</span>';
                    echo '</label>';
                    echo '<label class="inline-check download-inline-check">';
                    echo '<input type="checkbox" data-office-bulk-toggle="division">';
                    echo '<span>all divisions</span>';
                    echo '</label>';
                    echo '<label class="inline-check download-inline-check">';
                    echo '<input type="checkbox" data-office-bulk-toggle="subdivision">';
                    echo '<span>all sub-divisions</span>';
                    echo '</label>';
                    echo '</div>';
                    echo '<div class="download-filter-tree download-office-tree">';
                    foreach ($zones as $zoneId => $zoneMeta) {
                        echo '<div class="download-filter-tree-node depth-1">';
                        echo '<label class="download-tree-check">';
                        echo '<input type="checkbox" data-office-level="zone" name="' . e($inputBase . '[zone_ids][]') . '" value="' . e((string)$zoneId) . '">';
                        echo '<span>' . e((string)($zoneMeta['name'] ?? '')) . '</span>';
                        echo '</label>';
                        $zoneHasChild = false;
                        foreach ($circles as $circleId => $circleMeta) {
                            if ((string)($circleMeta['zone_id'] ?? '') !== (string)$zoneId) {
                                continue;
                            }
                            if (!$zoneHasChild) {
                                echo '<div class="download-filter-tree-children">';
                                $zoneHasChild = true;
                            }
                            echo '<div class="download-filter-tree-node depth-2">';
                            echo '<label class="download-tree-check">';
                            echo '<input type="checkbox" data-office-level="circle" name="' . e($inputBase . '[circle_ids][]') . '" value="' . e((string)$circleId) . '">';
                            echo '<span>' . e((string)($circleMeta['name'] ?? '')) . '</span>';
                            echo '</label>';
                            $circleHasChild = false;
                            foreach ($divisions as $divisionId => $divisionMeta) {
                                if ((string)($divisionMeta['circle_id'] ?? '') !== (string)$circleId) {
                                    continue;
                                }
                                if (!$circleHasChild) {
                                    echo '<div class="download-filter-tree-children">';
                                    $circleHasChild = true;
                                }
                                echo '<div class="download-filter-tree-node depth-3">';
                                echo '<label class="download-tree-check">';
                                echo '<input type="checkbox" data-office-level="division" name="' . e($inputBase . '[division_ids][]') . '" value="' . e((string)$divisionId) . '">';
                                echo '<span>' . e((string)($divisionMeta['name'] ?? '')) . '</span>';
                                echo '</label>';
                                $divisionHasChild = false;
                                foreach ($subdivisions as $subdivisionId => $subdivisionMeta) {
                                    if ((string)($subdivisionMeta['division_id'] ?? '') !== (string)$divisionId) {
                                        continue;
                                    }
                                    if (!$divisionHasChild) {
                                        echo '<div class="download-filter-tree-children">';
                                        $divisionHasChild = true;
                                    }
                                    echo '<div class="download-filter-tree-node depth-4">';
                                    echo '<label class="download-tree-check">';
                                    echo '<input type="checkbox" data-office-level="subdivision" name="' . e($inputBase . '[subdivision_ids][]') . '" value="' . e((string)$subdivisionId) . '">';
                                    echo '<span>' . e((string)($subdivisionMeta['name'] ?? '')) . '</span>';
                                    echo '</label>';
                                    echo '</div>';
                                }
                                if ($divisionHasChild) {
                                    echo '</div>';
                                }
                                echo '</div>';
                            }
                            if ($circleHasChild) {
                                echo '</div>';
                            }
                            echo '</div>';
                        }
                        if ($zoneHasChild) {
                            echo '</div>';
                        }
                        echo '</div>';
                    }
                    echo '</div>';
                    return;
                }
                if ($type === 'conditional') {
                    $childKey = (string)($filterMeta['child_key'] ?? '');
                    $childLabel = (string)($filterMeta['child_label'] ?? '');
                    if ($mode === 'common' && $childKey === '') {
                        $childKey = (string)($filterMeta['child_identifier'] ?? '');
                        $childLabel = (string)($filterMeta['child_label'] ?? '');
                    }
                    $childBase = $childKey !== '' ? preg_replace('/\[[^\]]+\]$/', '[' . $childKey . ']', $inputBase) : '';
                    $primaryOptions = (array)($filterMeta['options'] ?? []);
                    $secondaryMap = (array)($filterMeta['secondary_options_map'] ?? []);
                    if (!$primaryOptions && empty($filterMeta['has_blank'])) {
                        echo '<p class="muted">No options available.</p>';
                        return;
                    }
                    echo '<div class="download-filter-tree download-conditional-tree">';
                    foreach ($primaryOptions as $primaryValue => $primaryLabel) {
                        $children = (array)($secondaryMap[(string)$primaryValue] ?? []);
                        echo '<div class="download-filter-tree-node depth-1">';
                        echo '<label class="download-tree-check">';
                        echo '<input type="checkbox" name="' . e($inputBase . '[values][]') . '" value="' . e((string)$primaryValue) . '" data-conditional-primary-checkbox>';
                        echo '<span>' . e((string)$primaryLabel) . '</span>';
                        echo '</label>';
                        if ($children) {
                            echo '<div class="download-filter-tree-children">';
                            foreach ($children as $childValue => $childOptionLabel) {
                                echo '<div class="download-filter-tree-node depth-2">';
                                echo '<label class="download-tree-check">';
                                echo '<input type="checkbox" name="' . e($childBase . '[values][]') . '" value="' . e((string)$childValue) . '">';
                                echo '<span>' . e((string)$childOptionLabel) . '</span>';
                                echo '</label>';
                                echo '</div>';
                            }
                            echo '</div>';
                        }
                        echo '</div>';
                    }
                    if (!empty($filterMeta['has_blank'])) {
                        echo '<div class="download-filter-tree-node depth-1">';
                        echo '<label class="download-tree-check">';
                        echo '<input type="checkbox" name="' . e($inputBase . '[values][]') . '" value="__blank__">';
                        echo '<span>Blank</span>';
                        echo '</label>';
                        echo '</div>';
                    }
                    echo '</div>';
                    return;
                }
                if ($type === 'date') {
                    echo '<div class="download-filter-body download-filter-range">';
                    echo '<div class="download-date-range">';
                    echo '<label>From<input type="date" name="' . e($inputBase . '[from]') . '"></label>';
                    echo '<label>To<input type="date" name="' . e($inputBase . '[to]') . '"></label>';
                    echo '</div>';
                    echo '<label class="inline-check download-inline-check"><input type="checkbox" name="' . e($inputBase . '[blank]') . '" value="1"><span>Blank</span></label>';
                    echo '</div>';
                    return;
                }
                if ($type === 'number') {
                    echo '<div class="download-filter-body download-filter-range">';
                    echo '<div class="download-date-range">';
                    echo '<label>From<input type="number" step="0.01" name="' . e($inputBase . '[from]') . '"></label>';
                    echo '<label>To<input type="number" step="0.01" name="' . e($inputBase . '[to]') . '"></label>';
                    echo '</div>';
                    echo '<label class="inline-check download-inline-check"><input type="checkbox" name="' . e($inputBase . '[blank]') . '" value="1"><span>Blank</span></label>';
                    echo '</div>';
                    return;
                }
                $optionMap = [];
                if ($type === 'file') {
                    $optionMap = [
                        '__has_file__' => 'Have file',
                        '__no_file__' => 'No file',
                    ];
                    echo '<div class="download-filter-body">';
                    $renderDownloadChoiceGroup($inputBase . '[values]', $optionMap);
                    echo '</div>';
                    return;
                }
                if ($type === 'yes_no') {
                    foreach (['Yes', 'No'] as $yesNoValue) {
                        if (isset($filterMeta['options'][$yesNoValue])) {
                            $optionMap[$yesNoValue] = $yesNoValue;
                        }
                    }
                    if (!empty($filterMeta['has_blank'])) {
                        $optionMap['__blank__'] = 'Blank';
                    }
                    echo '<div class="download-filter-body">';
                    $renderDownloadChoiceGroup($inputBase . '[values]', $optionMap, [], 'Blank');
                    echo '</div>';
                    return;
                }
                foreach ((array)($filterMeta['options'] ?? []) as $optionValue => $optionLabel) {
                    $optionMap[(string)$optionValue] = (string)$optionLabel;
                }
                if (!empty($filterMeta['has_blank'])) {
                    $optionMap['__blank__'] = 'Blank';
                }
                echo '<div class="download-filter-body">';
                $renderDownloadChoiceGroup($inputBase . '[values]', $optionMap, [], 'Blank');
                echo '</div>';
            };
            $downloadLevel1DefaultLabel = array_key_first($downloadLevel1Catalog);
            $downloadDefaultSegmentIds = array_map(static fn(array $segment): int => (int)$segment['id'], $downloadAvailableSegments);
            $downloadCommonColumnDefaults = [];
            foreach ($downloadCommonOptionMap as $optionKey => $optionLabel) {
                if (($downloadLevel1DefaultLabel === 'Office' && $optionKey === '__office__') || $optionKey === $downloadLevel1DefaultLabel) {
                    continue;
                }
                $downloadCommonColumnDefaults[] = $optionKey;
            }
        ?>
        <form method="post" action="index.php" class="grid download-data-form" id="download-data-form">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="asset_download_data">
            <input type="hidden" name="office_view_scope" value="<?= e($currentOfficeViewScope); ?>">

            <div class="download-modal-topbar" role="tablist" aria-label="Download Pages">
                <button type="button" class="segment is-active" data-download-modal-page-tab="level1">Level_1</button>
                <button type="button" class="segment" data-download-modal-page-tab="level2">Level_2</button>
                <button type="button" class="segment" data-download-modal-page-tab="level3">Level_3</button>
            </div>

            <div class="download-modal-pages">
            <section class="download-modal-page" data-download-modal-page="level1">
            <section class="download-layer-card">
                <div class="download-layer-head">
                    <h4>Select File Format</h4>
                    <p class="hint">Choose PDF (data only), Excel (data only), or ZIP (file only).</p>
                </div>
                <div class="download-modal-row download-modal-row-single">
                    <label>File Format
                        <select name="download_output" id="download-output-select" data-download-output-select>
                            <option value="pdf">PDF (data only)</option>
                            <option value="excel" selected>Excel (data only)</option>
                            <option value="zip">ZIP (file only)</option>
                        </select>
                    </label>
                </div>
                <div class="download-modal-row download-modal-row-single">
                    <label class="inline-check download-inline-check hidden" data-download-zip-only>
                        <input type="checkbox" name="download_zip_use_hierarchy" value="1" checked>
                        <span>Folder hierarchy for ZIP ON/OFF</span>
                    </label>
                </div>
            </section>
            <section class="download-layer-card hidden" data-download-zip-only>
                <div class="download-layer-head">
                    <h4>ZIP Folder Structure</h4>
                    <p class="hint">For ZIP, define the folder path directly using tokens like <code>{division} &gt; {upazilla} &gt; {office_name}</code>. If hierarchy is off, all files go into one folder.</p>
                </div>
                <div class="download-modal-row download-modal-row-single">
                    <label>Folder hierarchy template
                        <input type="text" name="download_zip_folder_template" value="{division} > {office_name}">
                    </label>
                </div>
                <div class="download-preview-list download-token-helper-grid">
                    <?php foreach ($downloadNamingTokens as $token): ?>
                        <div class="download-preview-row">
                            <span class="download-preview-label" title="<?= e('{' . $token . '}'); ?>"><code>{<?= e($token); ?>}</code></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <section class="download-layer-card" data-download-level1-card>
                <div class="download-layer-head">
                    <h4>Level 1 Setup</h4>
                    <p class="hint">For PDF and Excel, choose one field as `is_level`. For ZIP, this table stays available only for field visibility and common filtering.</p>
                </div>
                <?php if (!$downloadLevel1Catalog): ?>
                    <p class="muted">No Level 1 field is available. Configure Download Manager first.</p>
                <?php else: ?>
                    <input type="hidden" name="download_level1_label" id="download-level1-label" value="<?= e((string)$downloadLevel1DefaultLabel); ?>">
                    <div class="table-wrap">
                        <table class="download-manager-table download-level1-table">
                            <thead>
                                <tr>
                                    <th data-download-level1-col="field">Field Name</th>
                                    <th data-download-level1-col="show">Show</th>
                                    <th data-download-level1-col="is_level">Is_Level1</th>
                                    <th data-download-level1-col="serial">Serial</th>
                                    <th data-download-level1-col="filter">Filter</th>
                                    <th data-download-level1-col="sorting">Sorting</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $level1RowSerial = 1; ?>
                                <?php foreach ($downloadLevel1Catalog as $label => $values): ?>
                                    <?php
                                        $commonKey = $label === 'Office' ? '__office__' : $label;
                                        $isSelectedLevel = $label === $downloadLevel1DefaultLabel;
                                    ?>
                                    <tr data-download-level1-row data-level1-label="<?= e((string)$label); ?>" data-common-field="<?= e((string)$commonKey); ?>" data-common-filter-identifier="<?= e((string)$commonKey); ?>" data-is-office="<?= $commonKey === '__office__' ? '1' : '0'; ?>">
                                        <td data-download-level1-col="field"><?= e((string)$label); ?></td>
                                        <td data-download-level1-col="show">
                                            <label class="inline-check download-inline-check">
                                                <input
                                                    type="checkbox"
                                                    <?= $isSelectedLevel ? 'checked disabled' : 'checked'; ?>
                                                    data-download-level1-visible>
                                                <span></span>
                                            </label>
                                        </td>
                                        <td data-download-level1-col="is_level">
                                            <label class="inline-check download-inline-check">
                                                <input
                                                    type="radio"
                                                    name="download_level1_choice"
                                                    value="<?= e((string)$label); ?>"
                                                    <?= $isSelectedLevel ? 'checked' : ''; ?>
                                                    data-download-level1-choice>
                                                <span></span>
                                            </label>
                                        </td>
                                        <td data-download-level1-col="serial">
                                            <input
                                                type="number"
                                                min="1"
                                                value="<?= $level1RowSerial; ?>"
                                                data-download-level1-serial
                                                <?= $isSelectedLevel ? 'disabled' : ''; ?>>
                                            <input type="checkbox" name="download_common_columns[]" value="<?= e((string)$commonKey); ?>" <?= $isSelectedLevel ? '' : 'checked'; ?> hidden data-download-common-column-input>
                                            <input type="hidden" name="download_common_column_order[<?= e((string)$commonKey); ?>]" value="<?= $level1RowSerial; ?>" data-download-common-column-order>
                                            <input type="checkbox" name="download_common_sort[<?= e((string)$commonKey); ?>][enabled]" value="1" <?= $isSelectedLevel ? '' : 'checked'; ?> hidden data-download-common-sort-enabled>
                                            <input type="hidden" name="download_common_sort[<?= e((string)$commonKey); ?>][order]" value="<?= $level1RowSerial; ?>" data-download-common-sort-order>
                                        </td>
                                        <td data-download-level1-col="filter">
                                            <button type="button" class="btn-small button-link" data-common-filter-open="<?= e((string)$commonKey); ?>">Filter</button>
                                        </td>
                                        <td data-download-level1-col="sorting">
                                            <select name="download_common_sort[<?= e((string)$commonKey); ?>][dir]" data-download-sort-direction <?= $commonKey === '__office__' ? 'disabled' : ''; ?>>
                                                <?php if ($commonKey === '__office__'): ?>
                                                    <option value="asc" selected>Default hierarchy</option>
                                                <?php else: ?>
                                                    <option value="asc">ASC</option>
                                                    <option value="desc">DESC</option>
                                                <?php endif; ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php $level1RowSerial++; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            </section>

            <section class="download-modal-page hidden" data-download-modal-page="level2">
            <section class="download-layer-card">
                <div class="download-layer-head">
                    <h4>Level 2 Segment And Field Selection</h4>
                    <p class="hint">For ZIP, only segments with file fields and only file fields should remain visible. For PDF and Excel, all segment fields are available. File fields in data output show summaries like 1 dwg, 4 pdf.</p>
                </div>
                <div class="download-subhead-row">
                    <strong>Segments</strong>
                    <button type="button" class="btn-small button-link" data-download-select-all-segments>Select All</button>
                </div>
                <div class="download-choice-grid">
                    <?php foreach ($downloadAvailableSegments as $downloadSegment): ?>
                        <?php
                            $downloadSegmentId = (int)$downloadSegment['id'];
                            $downloadHasFileField = false;
                            foreach (($downloadSegmentConfigs[$downloadSegmentId]['fields'] ?? []) as $segmentFieldMeta) {
                                if ((string)($segmentFieldMeta['data_type'] ?? '') === 'file') {
                                    $downloadHasFileField = true;
                                    break;
                                }
                            }
                        ?>
                        <label class="inline-check download-inline-check" data-download-segment-option="<?= $downloadSegmentId; ?>" data-has-file-field="<?= $downloadHasFileField ? '1' : '0'; ?>">
                            <input type="checkbox" name="download_segments[]" value="<?= $downloadSegmentId; ?>" data-download-segment-toggle="<?= $downloadSegmentId; ?>" <?= in_array($downloadSegmentId, $downloadDefaultSegmentIds, true) ? 'checked' : ''; ?>>
                            <span><?= e((string)$downloadSegment['segment_name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <?php foreach ($downloadSegmentConfigs as $downloadSegmentId => $downloadSegmentConfig): ?>
                    <?php
                        $downloadSegmentHasFileField = false;
                        foreach ($downloadSegmentConfig['fields'] as $segmentFieldMeta) {
                            if ((string)($segmentFieldMeta['data_type'] ?? '') === 'file') {
                                $downloadSegmentHasFileField = true;
                                break;
                            }
                        }
                    ?>
                    <section class="download-segment-block" data-download-segment-block="<?= $downloadSegmentId; ?>" data-has-file-field="<?= $downloadSegmentHasFileField ? '1' : '0'; ?>">
                        <div class="download-subhead-row">
                            <strong><?= e((string)$downloadSegmentConfig['segment']['segment_name']); ?></strong>
                            <button type="button" class="btn-small button-link" data-download-select-all-fields="<?= $downloadSegmentId; ?>">Select All Fields</button>
                        </div>
                        <div class="download-choice-grid">
                            <?php foreach ($downloadSegmentConfig['segment_fields_only'] as $downloadField): ?>
                                <label class="inline-check download-inline-check" data-download-field-item data-field-type="<?= e((string)($downloadField['data_type'] ?? 'text')); ?>" data-field-label="<?= e((string)$downloadField['label']); ?>">
                                    <input type="checkbox" name="download_selected_fields[<?= $downloadSegmentId; ?>][]" value="<?= e((string)$downloadField['field_key']); ?>" <?= (int)($downloadField['active_status'] ?? 0) === 1 ? 'checked' : ''; ?> data-download-field-checkbox="<?= $downloadSegmentId; ?>">
                                    <span><?= e((string)$downloadField['label']); ?><?php if ((string)($downloadField['data_type'] ?? '') === 'file'): ?> <small class="muted">(file)</small><?php endif; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                    </section>
                <?php endforeach; ?>
            </section>
            </section>

            <section class="download-modal-page hidden" data-download-modal-page="level3">
                <section class="download-layer-card" data-download-level1-filter-section>
                    <div class="download-layer-head">
                        <h4>Level 3 Filters</h4>
                        <p class="hint">Only filters are shown here. Level_1 filters are separate, and segment filters appear only for segments and fields currently checked in Level_2.</p>
                    </div>
                    <div class="download-subhead-row">
                        <strong>Level 1 Fields</strong>
                    </div>
                    <?php
                        $downloadConditionalChildLabels = [];
                        foreach ($downloadCommonFilterCatalog as $downloadCommonMeta) {
                            if ((string)($downloadCommonMeta['data_type'] ?? '') === 'conditional' && !empty($downloadCommonMeta['child_label'])) {
                                $downloadConditionalChildLabels[(string)$downloadCommonMeta['child_label']] = true;
                            }
                        }
                    ?>
                    <div class="download-choice-grid">
                        <?php foreach ($downloadLevel1Catalog as $label => $values): ?>
                            <?php
                                if (isset($downloadConditionalChildLabels[(string)$label])) {
                                    continue;
                                }
                                $commonKey = $label === 'Office' ? '__office__' : $label;
                                $commonMeta = $downloadCommonFilterCatalog[$commonKey] ?? null;
                                if ($commonMeta && (string)($commonMeta['data_type'] ?? '') === 'conditional_secondary') {
                                    continue;
                                }
                                $buttonLabel = (string)$label;
                                if ($commonMeta && (string)($commonMeta['data_type'] ?? '') === 'conditional' && !empty($commonMeta['child_label'])) {
                                    $buttonLabel .= ' - ' . (string)$commonMeta['child_label'];
                                }
                            ?>
                            <button type="button" class="btn-small button-link" data-common-filter-open="<?= e((string)$commonKey); ?>"><?= e($buttonLabel); ?></button>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="download-layer-card">
                    <div class="download-layer-head">
                        <h4>Segment Filters</h4>
                        <p class="hint">These filters follow the segments and fields selected in Level_2.</p>
                    </div>
                    <?php foreach ($downloadSegmentConfigs as $downloadSegmentId => $downloadSegmentConfig): ?>
                        <?php
                            $downloadSegmentHasFileField = false;
                            foreach ($downloadSegmentConfig['fields'] as $segmentFieldMeta) {
                                if ((string)($segmentFieldMeta['data_type'] ?? '') === 'file') {
                                    $downloadSegmentHasFileField = true;
                                    break;
                                }
                            }
                        ?>
                        <section class="download-segment-block" data-download-level3-block="<?= $downloadSegmentId; ?>" data-has-file-field="<?= $downloadSegmentHasFileField ? '1' : '0'; ?>">
                            <div class="download-config-box download-filter-box">
                                <h5><?= e((string)$downloadSegmentConfig['segment']['segment_name']); ?></h5>
                                <?php $downloadCatalog = $downloadSegmentConfig['catalog']; ?>
                                <?php foreach ($downloadSegmentConfig['filter_fields'] as $filterFieldKey => $filterField): ?>
                                    <?php
                                        $filterMeta = $downloadCatalog['fields'][$filterFieldKey] ?? null;
                                        if (!$filterMeta) {
                                            continue;
                                        }
                                        if ((int)($filterMeta['secondary_of_field_id'] ?? 0) > 0) {
                                            continue;
                                        }
                                    ?>
                                    <div class="download-filter-group" data-download-filter-field data-field-label="<?= e((string)$filterField['label']); ?>" data-field-key="<?= e((string)$filterFieldKey); ?>">
                                        <h6><?= e((string)$filterField['label']); ?></h6>
                                        <?php $renderDownloadFilterControl('download_filters[' . $downloadSegmentId . '][' . $filterFieldKey . ']', $filterMeta, 'segment'); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
            </section>
            </section>
            </div>

            <div class="modal-actions">
                <button type="submit" class="btn-secondary" <?= $downloadModuleReady ? '' : 'disabled'; ?>>Download</button>
                <button type="button" class="modal-close" data-close="download-data-modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="download-common-filter-modal" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="download-common-filter-title">
        <div class="flash-modal-head">
            <h3 id="download-common-filter-title">Level 1 Field Filter</h3>
            <button type="button" class="welcome-modal-close modal-close" data-close="download-common-filter-modal" aria-label="Close">x</button>
        </div>
        <div id="download-common-filter-panels">
            <?php foreach ($downloadCommonFilterCatalog as $identifier => $filterMeta): ?>
                <?php if ((string)($filterMeta['data_type'] ?? '') === 'conditional_secondary') { continue; } ?>
                <section class="download-common-filter-panel hidden" data-common-filter-panel="<?= e((string)$identifier); ?>">
                    <?php
                        $panelTitle = (string)($filterMeta['label'] ?? $identifier);
                        if ((string)($filterMeta['data_type'] ?? '') === 'conditional' && !empty($filterMeta['child_label'])) {
                            $panelTitle .= ' - ' . (string)$filterMeta['child_label'];
                        }
                    ?>
                    <h4><?= e($panelTitle); ?></h4>
                    <?php $renderDownloadFilterControl('download_common_filters[' . $identifier . ']', $filterMeta, 'common'); ?>
                </section>
            <?php endforeach; ?>
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-close" data-close="download-common-filter-modal">Close</button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="modal-backdrop" id="download-data-modal" aria-hidden="true">
    <div class="modal-card modal-wide download-data-modal-card" role="dialog" aria-modal="true" aria-labelledby="download-data-title">
        <div class="flash-modal-head">
            <h3 id="download-data-title">Download Data</h3>
            <button type="button" class="welcome-modal-close modal-close" data-close="download-data-modal" aria-label="Close">×</button>
        </div>
        <div class="download-modal-async-body" data-download-modal-url="<?= e($downloadModalUrl); ?>" data-download-loaded="0">
            <p class="muted">Loading download options when needed...</p>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="download-common-filter-modal" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="download-common-filter-title">
        <div class="flash-modal-head">
            <h3 id="download-common-filter-title">Level 1 Field Filter</h3>
            <button type="button" class="welcome-modal-close modal-close" data-close="download-common-filter-modal" aria-label="Close">×</button>
        </div>
        <div class="download-common-filter-async-body">
            <p class="muted">Loading filters...</p>
        </div>
    </div>
</div>

<iframe name="download-target-frame" id="download-target-frame" class="download-hidden-frame" title="Download Target"></iframe>

<div class="modal-backdrop" id="download-wait-modal" aria-hidden="true">
    <div class="modal-card download-wait-modal-card" role="dialog" aria-modal="true" aria-labelledby="download-wait-title">
        <div class="flash-modal-head">
            <h3 id="download-wait-title">Preparing Download</h3>
        </div>
        <p id="download-wait-text">The file is being prepared. Large exports may take some time. Please wait.</p>
        <div class="download-wait-spinner" aria-hidden="true">
            <div class="download-wait-spinner-ring"></div>
        </div>
    </div>
</div>

<script type="application/json" id="bimh-picker-meta"><?= json_encode([
    'lookup_url' => 'index.php?page=bimh_lookup',
    'scope' => $bimhPickerScope,
    'rows' => $bimhPickerData,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>

<div class="modal-backdrop" id="bimh-picker-modal" aria-hidden="true">
    <div class="modal-card modal-wide bimh-picker-modal-card" role="dialog" aria-modal="true" aria-labelledby="bimh-picker-title">
        <div class="flash-modal-head">
            <h3 id="bimh-picker-title">Select Establishment</h3>
            <button type="button" class="welcome-modal-close modal-close" data-close="bimh-picker-modal" aria-label="Close">×</button>
        </div>
        <div class="bimh-picker-filters">
            <label>Search
                <input type="text" id="bimh-picker-search" placeholder="Search by BIMH ID or establishment name">
            </label>
            <?php if (!empty($bimhPickerScope['show_circle_filter'])): ?>
                <label>Circle
                    <select id="bimh-picker-circle">
                        <option value="">All</option>
                    </select>
                </label>
            <?php endif; ?>
            <?php if (!empty($bimhPickerScope['show_division_filter'])): ?>
                <label>Division
                    <select id="bimh-picker-division">
                        <option value="">All</option>
                    </select>
                </label>
            <?php endif; ?>
        </div>
        <div class="table-wrap">
            <table class="bimh-picker-table">
                <thead>
                    <tr>
                        <th>BIMH ID</th>
                        <th>Est Name</th>
                        <th>Upazila/Thana</th>
                        <th>District</th>
                        <?php if (!empty($bimhPickerScope['show_circle_filter'])): ?><th>Circle</th><?php endif; ?>
                        <?php if (!empty($bimhPickerScope['show_division_filter'])): ?><th>Division</th><?php endif; ?>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="bimh-picker-body">
                    <tr><td colspan="<?= (!empty($bimhPickerScope['show_circle_filter']) ? 1 : 0) + (!empty($bimhPickerScope['show_division_filter']) ? 1 : 0) + 5; ?>" class="muted">No establishment found.</td></tr>
                </tbody>
            </table>
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-close" data-close="bimh-picker-modal">Close</button>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="field-help-modal" aria-hidden="true">
    <div class="modal-card field-help-modal-card" role="dialog" aria-modal="true" aria-labelledby="field-help-title">
        <div class="flash-modal-head">
            <h3 id="field-help-title">Field Information</h3>
            <button type="button" class="welcome-modal-close modal-close" data-close="field-help-modal" aria-label="Close">×</button>
        </div>
        <div class="field-help-content">
            <p class="field-help-label" id="field-help-label"></p>
            <div class="field-help-body" id="field-help-body"></div>
            <div class="field-help-video hidden" id="field-help-video">
                <video id="field-help-player" class="hidden" controls preload="none"></video>
                <iframe
                    id="field-help-iframe"
                    class="hidden"
                    src=""
                    title="Field tutorial"
                    loading="lazy"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen
                ></iframe>
                <p><a href="#" target="_blank" rel="noopener" id="field-help-link">Open tutorial</a></p>
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-close" data-close="field-help-modal">Close</button>
        </div>
    </div>
</div>

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

<?php if (!$assetComparisonTableOnly && !$assetBoardFiltersOnly): ?>
<script>
window.initializeDownloadModalUi = function () {
    var form = document.getElementById('download-data-form');
    if (!form || form.getAttribute('data-download-ui-bound') === '1') {
        return;
    }
    form.setAttribute('data-download-ui-bound', '1');
    var level1Select = document.getElementById('download-level1-label');
    var level1Choices = Array.from(document.querySelectorAll('[data-download-level1-choice]'));
    var outputSelect = document.getElementById('download-output-select');
    var segmentToggles = Array.from(document.querySelectorAll('[data-download-segment-toggle]'));
    var commonFilterModal = document.getElementById('download-common-filter-modal');
    var modalPageTabs = Array.from(document.querySelectorAll('[data-download-modal-page-tab]'));
    var modalPages = Array.from(document.querySelectorAll('[data-download-modal-page]'));
    var level3AsyncBody = document.querySelector('.download-level3-async-body[data-download-level3-url]');
    var commonFilterAsyncBody = document.querySelector('#download-common-filter-modal .download-common-filter-async-body');
    var waitModal = document.getElementById('download-wait-modal');
    var waitText = document.getElementById('download-wait-text');
    var downloadFrame = document.getElementById('download-target-frame');
    var downloadInFlight = false;
    var level3LoadingPromise = null;
    var downloadPollInterval = null;
    var completionCookieName = '<?= e(asset_download_completion_cookie_name()); ?>';
    var activeDownloadStatusUrl = '';
    var parseJsonResponse = function (response, fallbackMessage) {
        return response.text().then(function (text) {
            var body = String(text || '').trim();
            if (body === '') {
                throw new Error(fallbackMessage);
            }
            try {
                return JSON.parse(body);
            } catch (error) {
                throw new Error(body.substring(0, 600));
            }
        });
    };
    var closeWaitModal = function () {
        if (downloadPollInterval) {
            clearInterval(downloadPollInterval);
            downloadPollInterval = null;
        }
        activeDownloadStatusUrl = '';
        if (waitModal) {
            waitModal.classList.remove('open');
            waitModal.setAttribute('aria-hidden', 'true');
        }
        downloadInFlight = false;
        form.querySelectorAll('[data-download-disabled-by-wait="1"]').forEach(function (field) {
            field.disabled = false;
            field.removeAttribute('data-download-disabled-by-wait');
        });
    };
    var prepareDownloadSubmissionState = function () {
        var touched = [];
        var markDisabled = function (input) {
            if (!input || input.disabled) {
                return;
            }
            input.disabled = true;
            input.setAttribute('data-download-submit-disabled', '1');
            touched.push(input);
        };
        var disableIfAllChecked = function (container) {
            if (!container) {
                return;
            }
            var checkboxes = Array.from(container.querySelectorAll('input[type="checkbox"]')).filter(function (input) {
                return !input.disabled;
            });
            if (!checkboxes.length) {
                return;
            }
            var checkedCount = checkboxes.filter(function (input) { return input.checked; }).length;
            if (checkedCount === checkboxes.length) {
                container.querySelectorAll('input, select, textarea').forEach(markDisabled);
            }
        };
        document.querySelectorAll('[data-common-filter-panel]').forEach(disableIfAllChecked);
        document.querySelectorAll('[data-download-filter-field]').forEach(disableIfAllChecked);
        return function restoreDownloadSubmissionState() {
            touched.forEach(function (input) {
                if (input.getAttribute('data-download-submit-disabled') === '1') {
                    input.disabled = false;
                    input.removeAttribute('data-download-submit-disabled');
                }
            });
        };
    };
    var openWaitModal = function () {
        if (!waitModal) {
            return;
        }
        if (waitText) {
            waitText.textContent = 'Preparing the download in background. Large exports may take some time. Please wait.';
        }
        waitModal.classList.add('open');
        waitModal.setAttribute('aria-hidden', 'false');
    };
    var readCookieValue = function (name) {
        var prefix = name + '=';
        var cookies = document.cookie ? document.cookie.split(';') : [];
        for (var i = 0; i < cookies.length; i++) {
            var cookie = cookies[i].trim();
            if (cookie.indexOf(prefix) === 0) {
                return decodeURIComponent(cookie.substring(prefix.length));
            }
        }
        return '';
    };
    var clearCompletionCookie = function () {
        document.cookie = completionCookieName + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=Lax';
    };
    var startDownloadCompletionPolling = function (token) {
        if (downloadPollInterval) {
            clearInterval(downloadPollInterval);
        }
        downloadPollInterval = window.setInterval(function () {
            if (!downloadInFlight) {
                clearInterval(downloadPollInterval);
                downloadPollInterval = null;
                return;
            }
            if (readCookieValue(completionCookieName) === token) {
                clearCompletionCookie();
                closeWaitModal();
            }
        }, 500);
    };
    var startDownloadJobPolling = function (statusUrl, downloadUrl) {
        if (!statusUrl) {
            throw new Error('Download status URL is missing.');
        }
        activeDownloadStatusUrl = statusUrl;
        if (downloadPollInterval) {
            clearInterval(downloadPollInterval);
        }
        var pollOnce = function () {
            if (!downloadInFlight || !activeDownloadStatusUrl) {
                clearInterval(downloadPollInterval);
                downloadPollInterval = null;
                return;
            }
            fetch(activeDownloadStatusUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
                .then(function (response) { return parseJsonResponse(response, 'Download status failed.'); })
                .then(function (payload) {
                    if (!payload || payload.ok !== true) {
                        throw new Error((payload && payload.message) ? payload.message : 'Download status failed.');
                    }
                    if (payload.status === 'completed') {
                        if (waitText) {
                            waitText.textContent = 'Download is ready. Starting file transfer...';
                        }
                        if (downloadFrame) {
                            downloadFrame.src = String(payload.download_url || downloadUrl || '') + (String(payload.download_url || downloadUrl || '').indexOf('?') === -1 ? '?' : '&') + '_ts=' + Date.now();
                        } else if (payload.download_url || downloadUrl) {
                            window.location.href = String(payload.download_url || downloadUrl);
                        }
                        window.setTimeout(closeWaitModal, 800);
                        return;
                    }
                    if (payload.status === 'failed') {
                        throw new Error(payload.message || 'Download failed.');
                    }
                    if (waitText) {
                        waitText.textContent = payload.status === 'processing'
                            ? 'Generating the file. Please wait...'
                            : 'Preparing the download in queue. Please wait...';
                    }
                })
                .catch(function (error) {
                    closeWaitModal();
                    alert((error && error.message) ? error.message : 'Download failed.');
                });
        };
        pollOnce();
        downloadPollInterval = window.setInterval(pollOnce, 1500);
    };
    var extractDownloadFailureText = function () {
        if (!downloadFrame) {
            return '';
        }
        try {
            var responseDoc = downloadFrame.contentDocument || (downloadFrame.contentWindow ? downloadFrame.contentWindow.document : null);
            if (!responseDoc || !responseDoc.body) {
                return '';
            }
            var flashAlert = responseDoc.querySelector('.flash-modal-alert');
            if (flashAlert) {
                return (flashAlert.textContent || '').replace(/\s+/g, ' ').trim();
            }
            var responseText = (responseDoc.body.textContent || '').replace(/\s+/g, ' ').trim();
            if (responseText === '') {
                return '';
            }
            var failureMarkers = [
                'fatal error',
                'warning',
                'notice',
                'csrf',
                'gateway time-out',
                'internal server error',
                'failed to',
                'runtimeexception',
                'uncaught',
                'exception',
            ];
            var lowerText = responseText.toLowerCase();
            for (var i = 0; i < failureMarkers.length; i++) {
                if (lowerText.indexOf(failureMarkers[i]) !== -1) {
                    return responseText;
                }
            }
        } catch (error) {
            return '';
        }
        return '';
    };
    var loadLevel3Content = function () {
        if (!level3AsyncBody) {
            return Promise.resolve();
        }
        if (level3AsyncBody.getAttribute('data-download-level3-loaded') === '1') {
            return Promise.resolve();
        }
        if (level3LoadingPromise) {
            return level3LoadingPromise;
        }
        var url = level3AsyncBody.getAttribute('data-download-level3-url') || '';
        if (!url) {
            return Promise.resolve();
        }
        level3AsyncBody.setAttribute('data-download-level3-loading', '1');
        level3LoadingPromise = fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.text();
            })
            .then(function (html) {
                var temp = document.createElement('div');
                temp.innerHTML = html;
                var level3Fragment = temp.querySelector('[data-download-fragment-level3]');
                var commonFragment = temp.querySelector('[data-download-fragment-common]');
                if (level3Fragment) {
                    level3AsyncBody.innerHTML = level3Fragment.innerHTML;
                    level3AsyncBody.setAttribute('data-download-level3-loaded', '1');
                } else {
                    level3AsyncBody.innerHTML = '<p class=\"error-text\">Failed to load filters.</p>';
                }
                if (commonFilterAsyncBody && commonFragment) {
                    commonFilterAsyncBody.innerHTML = commonFragment.innerHTML;
                }
                if (typeof window.initializeDownloadModalUi === 'function') {
                    window.initializeDownloadModalUi();
                }
            })
            .catch(function () {
                level3AsyncBody.innerHTML = '<p class=\"error-text\">Failed to load filters.</p>';
            })
            .finally(function () {
                level3AsyncBody.removeAttribute('data-download-level3-loading');
                level3LoadingPromise = null;
            });
        return level3LoadingPromise;
    };
    var activateModalPage = function (pageKey) {
        modalPageTabs.forEach(function (tab) {
            tab.classList.toggle('is-active', tab.getAttribute('data-download-modal-page-tab') === pageKey);
        });
        modalPages.forEach(function (page) {
            page.classList.toggle('hidden', page.getAttribute('data-download-modal-page') !== pageKey);
        });
        if (pageKey === 'level3') {
            loadLevel3Content();
        }
    };
    var selectedLevel1Label = function () {
        var checked = level1Choices.find(function (input) { return input.checked; });
        return checked ? checked.value : (level1Select ? level1Select.value : '');
    };
    var syncLevel1Table = function () {
        var selectedLabel = selectedLevel1Label();
        var isZip = outputSelect && outputSelect.value === 'zip';
        if (level1Select) {
            level1Select.value = selectedLabel;
        }
        var nextSerial = 1;
        document.querySelectorAll('[data-download-level1-row]').forEach(function (row) {
            var isSelected = (row.getAttribute('data-level1-label') || '') === selectedLabel;
            var isOffice = row.getAttribute('data-is-office') === '1';
            var visibleToggle = row.querySelector('[data-download-level1-visible]');
            var serialInput = row.querySelector('[data-download-level1-serial]');
            var directionSelect = row.querySelector('[data-download-sort-direction]');
            var commonColumnInput = row.querySelector('[data-download-common-column-input]');
            var commonSortEnabled = row.querySelector('[data-download-common-sort-enabled]');
            var isVisible = visibleToggle ? visibleToggle.checked : true;
            if (visibleToggle) {
                visibleToggle.disabled = isSelected && !isZip;
                if (isSelected) {
                    visibleToggle.checked = true;
                    isVisible = true;
                }
            }
            if (serialInput) {
                serialInput.disabled = isZip || isSelected || !isVisible;
                serialInput.value = isSelected ? '0' : (isVisible ? String(nextSerial++) : '');
            }
            if (directionSelect) {
                directionSelect.disabled = isZip || isOffice || !isVisible;
            }
            if (commonColumnInput) {
                commonColumnInput.checked = !isSelected && isVisible;
            }
            if (commonSortEnabled) {
                commonSortEnabled.checked = !isZip && !isOffice && isVisible;
            }
        });
    };
    var syncLevel1SerialMirrors = function () {
        document.querySelectorAll('[data-download-level1-row]').forEach(function (row) {
            var serialInput = row.querySelector('[data-download-level1-serial]');
            var commonOrderInput = row.querySelector('[data-download-common-column-order]');
            var sortOrderInput = row.querySelector('[data-download-common-sort-order]');
            if (!serialInput) {
                return;
            }
            var value = serialInput.value || '';
            if (commonOrderInput) {
                commonOrderInput.value = value;
            }
            if (sortOrderInput) {
                sortOrderInput.value = value;
            }
        });
    };
    var syncLevel1FieldUsage = function () {
        var selectedLabel = selectedLevel1Label();
        var isZip = outputSelect && outputSelect.value === 'zip';
        document.querySelectorAll('[data-download-filter-field]').forEach(function (block) {
            var isMatch = (block.getAttribute('data-field-label') || '') === selectedLabel;
            block.classList.toggle('hidden', !isZip && isMatch);
            if (isMatch) {
                if (isZip) {
                    return;
                }
                block.querySelectorAll('input').forEach(function (input) {
                    if (input.type === 'checkbox') {
                        input.checked = false;
                    } else {
                        input.value = '';
                    }
                });
            }
        });
        document.querySelectorAll('[data-download-field-item]').forEach(function (item) {
            var isMatch = (item.getAttribute('data-field-label') || '') === selectedLabel;
            var input = item.querySelector('input[type="checkbox"]');
            if (isZip) {
                if (input && input.getAttribute('data-level1-auto-hidden') === '1') {
                    input.checked = true;
                    input.removeAttribute('data-level1-auto-hidden');
                }
                return;
            }
            item.classList.toggle('hidden', isMatch);
            if (isMatch && input) {
                if (input.checked) {
                    input.setAttribute('data-level1-auto-hidden', '1');
                }
                input.checked = false;
            } else if (input && input.getAttribute('data-level1-auto-hidden') === '1') {
                input.checked = true;
                input.removeAttribute('data-level1-auto-hidden');
            }
        });
    };
    var syncOutputMode = function () {
        var isZip = outputSelect && outputSelect.value === 'zip';
        if (form) {
            form.setAttribute('data-download-output-mode', isZip ? 'zip' : 'data');
        }
        document.querySelectorAll('[data-download-zip-only]').forEach(function (item) {
            item.classList.toggle('hidden', !isZip);
        });
        document.querySelectorAll('[data-download-non-zip-only]').forEach(function (item) {
            item.classList.toggle('hidden', isZip);
        });
        document.querySelectorAll('[data-download-level1-filter-section]').forEach(function (item) {
            item.classList.toggle('hidden', isZip);
        });
        document.querySelectorAll('[data-download-segment-option]').forEach(function (item) {
            var allowed = !isZip || item.getAttribute('data-has-file-field') === '1';
            item.classList.toggle('hidden', !allowed);
            var input = item.querySelector('input[type="checkbox"]');
            if (!allowed && input) {
                input.checked = false;
            }
        });
        document.querySelectorAll('[data-download-segment-block], [data-download-level3-block]').forEach(function (block) {
            var allowed = !isZip || block.getAttribute('data-has-file-field') === '1';
            if (!allowed) {
                block.classList.add('hidden');
                return;
            }
            if (block.hasAttribute('data-download-level3-block')) {
                return;
            }
            block.querySelectorAll('[data-download-field-item]').forEach(function (item) {
                var fieldType = item.getAttribute('data-field-type') || 'text';
                var show = !isZip || fieldType === 'file';
                item.classList.toggle('hidden', !show);
                var input = item.querySelector('input[type="checkbox"]');
                if (!show && input) {
                    input.checked = false;
                }
            });
        });
        syncSegmentBlocks();
    };
    var syncSegmentBlocks = function () {
        var selected = new Set(segmentToggles.filter(function (input) {
            return input.checked;
        }).map(function (input) {
            return input.getAttribute('data-download-segment-toggle');
        }));
        document.querySelectorAll('[data-download-segment-block], [data-download-level3-block]').forEach(function (block) {
            var segmentId = block.getAttribute('data-download-segment-block') || block.getAttribute('data-download-level3-block');
            var zipBlocked = outputSelect && outputSelect.value === 'zip' && block.getAttribute('data-has-file-field') !== '1';
            block.classList.toggle('hidden', zipBlocked || !selected.has(segmentId));
        });
        syncLevel3FieldFilters();
    };
    var syncLevel3FieldFilters = function () {
        document.querySelectorAll('[data-download-level3-block]').forEach(function (block) {
            var segmentId = block.getAttribute('data-download-level3-block');
            var selectedFieldKeys = new Set(
                Array.from(document.querySelectorAll('input[data-download-field-checkbox="' + segmentId + '"]:checked'))
                    .map(function (input) { return input.value; })
            );
            block.querySelectorAll('[data-download-filter-field]').forEach(function (item) {
                var fieldKey = item.getAttribute('data-field-key') || '';
                item.classList.toggle('hidden', !selectedFieldKeys.has(fieldKey));
                item.querySelectorAll('input, select, textarea').forEach(function (input) {
                    input.disabled = !selectedFieldKeys.has(fieldKey);
                });
            });
        });
    };
    var getTreeNodeCheckbox = function (node) {
        var ownLabel = node ? node.querySelector(':scope > .download-tree-check input[type="checkbox"]') : null;
        if (ownLabel) {
            return ownLabel;
        }
        return node ? node.querySelector('.download-tree-check input[type="checkbox"]') : null;
    };
    var isOfficeTreeCheckbox = function (checkbox) {
        return !!(checkbox && checkbox.hasAttribute('data-office-level'));
    };
    var getTreeChildNodes = function (node) {
        if (!node) {
            return [];
        }
        var childContainer = node.querySelector(':scope > .download-filter-tree-children');
        if (!childContainer) {
            return [];
        }
        return Array.from(childContainer.children).filter(function (child) {
            return child.classList && child.classList.contains('download-filter-tree-node');
        });
    };
    var setTreeDescendantsChecked = function (node, checked) {
        getTreeChildNodes(node).forEach(function (childNode) {
            var checkbox = getTreeNodeCheckbox(childNode);
            if (checkbox) {
                checkbox.checked = checked;
                checkbox.indeterminate = false;
                if (isOfficeTreeCheckbox(checkbox)) {
                    checkbox.setAttribute('data-office-explicit', checked ? '1' : '0');
                }
            }
            setTreeDescendantsChecked(childNode, checked);
        });
    };
    var syncTreeNodeState = function (node) {
        var checkbox = getTreeNodeCheckbox(node);
        var childNodes = getTreeChildNodes(node);
        if (!checkbox || !childNodes.length) {
            return checkbox ? { checked: checkbox.checked, indeterminate: false } : { checked: false, indeterminate: false };
        }
        var childStates = childNodes.map(syncTreeNodeState);
        if (isOfficeTreeCheckbox(checkbox)) {
            var explicitChecked = checkbox.getAttribute('data-office-explicit');
            explicitChecked = explicitChecked === null ? checkbox.checked : explicitChecked === '1';
            var anyChildSelected = childStates.some(function (state) { return state.checked || state.indeterminate; });
            checkbox.checked = explicitChecked;
            checkbox.indeterminate = !explicitChecked && anyChildSelected;
            return { checked: explicitChecked || anyChildSelected, indeterminate: checkbox.indeterminate };
        }
        var allChecked = childStates.every(function (state) { return state.checked && !state.indeterminate; });
        var anyChecked = childStates.some(function (state) { return state.checked || state.indeterminate; });
        checkbox.checked = allChecked || anyChecked;
        checkbox.indeterminate = anyChecked && !allChecked;
        return { checked: checkbox.checked, indeterminate: checkbox.indeterminate };
    };
    var syncHierarchyTrees = function () {
        document.querySelectorAll('.download-filter-tree').forEach(function (tree) {
            Array.from(tree.children).forEach(function (child) {
                if (child.classList && child.classList.contains('download-filter-tree-node')) {
                    syncTreeNodeState(child);
                }
            });
        });
        document.querySelectorAll('.download-office-tree').forEach(function (tree) {
            ['zone', 'circle', 'division', 'subdivision'].forEach(function (level) {
                var inputs = Array.from(tree.querySelectorAll('input[type="checkbox"][data-office-level="' + level + '"]'));
                var bulkToggle = tree.parentElement ? tree.parentElement.querySelector('input[type="checkbox"][data-office-bulk-toggle="' + level + '"]') : null;
                if (!bulkToggle) {
                    return;
                }
                if (!inputs.length) {
                    bulkToggle.checked = false;
                    bulkToggle.indeterminate = false;
                    bulkToggle.disabled = true;
                    return;
                }
                bulkToggle.disabled = false;
                var checkedCount = inputs.filter(function (input) {
                    return input.getAttribute('data-office-explicit') === '1';
                }).length;
                bulkToggle.checked = checkedCount === inputs.length;
                bulkToggle.indeterminate = checkedCount > 0 && checkedCount < inputs.length;
            });
        });
    };
    var activeCommonFilterPanel = function () {
        if (!commonFilterModal) {
            return null;
        }
        return commonFilterModal.querySelector('[data-common-filter-panel]:not(.hidden)');
    };
    var setCommonFilterPanelChecked = function (panel, checked) {
        if (!panel) {
            return;
        }
        panel.querySelectorAll('input[type="checkbox"]').forEach(function (input) {
            if (input.disabled) {
                return;
            }
            input.checked = checked;
            input.indeterminate = false;
            if (isOfficeTreeCheckbox(input)) {
                input.setAttribute('data-office-explicit', checked ? '1' : '0');
            }
        });
        syncHierarchyTrees();
    };
    level1Choices.forEach(function (input) {
        input.addEventListener('change', function () {
            syncLevel1Table();
            syncOutputMode();
            syncLevel1FieldUsage();
        });
    });
    document.querySelectorAll('[data-download-level1-serial]').forEach(function (input) {
        input.addEventListener('input', syncLevel1SerialMirrors);
    });
    document.querySelectorAll('[data-download-level1-visible]').forEach(function (input) {
        input.addEventListener('change', function () {
            syncLevel1Table();
            syncLevel1SerialMirrors();
        });
    });
    if (outputSelect) {
        outputSelect.addEventListener('change', function () {
            syncOutputMode();
            syncLevel1FieldUsage();
        });
        syncOutputMode();
        syncLevel1FieldUsage();
    }
    segmentToggles.forEach(function (input) {
        input.addEventListener('change', syncSegmentBlocks);
    });
    syncSegmentBlocks();
    syncLevel1Table();
    syncLevel1SerialMirrors();
    syncOutputMode();
    syncLevel1FieldUsage();
    var selectAllSegmentsButton = document.querySelector('[data-download-select-all-segments]');
    if (selectAllSegmentsButton) {
        selectAllSegmentsButton.addEventListener('click', function () {
            segmentToggles.forEach(function (input) {
                input.checked = true;
            });
            syncSegmentBlocks();
        });
    }
    document.querySelectorAll('[data-download-select-all-fields]').forEach(function (button) {
        button.addEventListener('click', function () {
            var segmentId = button.getAttribute('data-download-select-all-fields');
            var block = document.querySelector('[data-download-segment-block="' + segmentId + '"]');
            if (!block) {
                return;
            }
            block.querySelectorAll('input[name="download_selected_fields[' + segmentId + '][]"]').forEach(function (input) {
                var item = input.closest('[data-download-field-item]');
                if (!item || !item.classList.contains('hidden')) {
                    input.checked = true;
                }
            });
            syncLevel3FieldFilters();
        });
    });
    document.querySelectorAll('[data-download-field-checkbox]').forEach(function (input) {
        input.addEventListener('change', syncLevel3FieldFilters);
    });
    if (downloadFrame && !downloadFrame.getAttribute('data-download-frame-bound')) {
        downloadFrame.setAttribute('data-download-frame-bound', '1');
        downloadFrame.addEventListener('load', function () {
            if (!downloadInFlight) {
                return;
            }
            window.setTimeout(function () {
                var responseText = extractDownloadFailureText();
                if (responseText !== '') {
                    closeWaitModal();
                    alert(responseText.substring(0, 1200));
                }
            }, 300);
        });
    }
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (downloadInFlight) {
            return;
        }
        downloadInFlight = true;
        form.querySelectorAll('button').forEach(function (field) {
            if (field.disabled) {
                return;
            }
            field.disabled = true;
            field.setAttribute('data-download-disabled-by-wait', '1');
        });
        openWaitModal();
        var restoreSubmissionState = prepareDownloadSubmissionState();
        var formData = new FormData(form);
        restoreSubmissionState();
        formData.set('action', 'asset_download_data_queue');
        fetch('index.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(function (response) { return parseJsonResponse(response, 'Download start failed.'); })
            .then(function (payload) {
                if (!payload || payload.ok !== true) {
                    throw new Error((payload && payload.message) ? payload.message : 'Download start failed.');
                }
                if (payload.status === 'completed') {
                    if (downloadFrame && payload.download_url) {
                        downloadFrame.src = String(payload.download_url) + (String(payload.download_url).indexOf('?') === -1 ? '?' : '&') + '_ts=' + Date.now();
                    } else if (payload.download_url) {
                        window.location.href = String(payload.download_url);
                    }
                    window.setTimeout(closeWaitModal, 500);
                    return;
                }
                startDownloadJobPolling(String(payload.status_url || ''), String(payload.download_url || ''));
            })
            .catch(function (error) {
                closeWaitModal();
                alert((error && error.message) ? error.message : 'Download failed.');
            });
    });
    document.addEventListener('click', function (event) {
        var commonFilterButton = event.target.closest('[data-common-filter-open]');
        if (!commonFilterButton) {
            var bulkButton = event.target.closest('[data-common-filter-check-all], [data-common-filter-uncheck-all]');
            if (!bulkButton) {
                return;
            }
            event.preventDefault();
            setCommonFilterPanelChecked(activeCommonFilterPanel(), bulkButton.hasAttribute('data-common-filter-check-all'));
            return;
        }
        event.preventDefault();
        var identifier = commonFilterButton.getAttribute('data-common-filter-open');
        if (!commonFilterModal) {
            return;
        }
        loadLevel3Content().finally(function () {
            commonFilterModal.setAttribute('aria-hidden', 'false');
            commonFilterModal.classList.add('open');
            commonFilterModal.querySelectorAll('[data-common-filter-panel]').forEach(function (panel) {
                panel.classList.toggle('hidden', panel.getAttribute('data-common-filter-panel') !== identifier);
            });
            syncHierarchyTrees();
        });
    });
    document.addEventListener('change', function (event) {
        var target = event.target;
        if (target instanceof HTMLInputElement && target.type === 'checkbox' && target.hasAttribute('data-office-bulk-toggle')) {
            var officeTree = target.closest('[data-common-filter-panel], .download-filter-control, .download-filter-group, .download-common-filter-async-body');
            officeTree = officeTree ? officeTree.querySelector('.download-office-tree') : null;
            if (!officeTree) {
                return;
            }
            officeTree.querySelectorAll('input[type="checkbox"][data-office-level="' + target.getAttribute('data-office-bulk-toggle') + '"]').forEach(function (input) {
                if (input.disabled) {
                    return;
                }
                input.checked = target.checked;
                input.indeterminate = false;
                input.setAttribute('data-office-explicit', target.checked ? '1' : '0');
            });
            syncHierarchyTrees();
            return;
        }
        if (!(target instanceof HTMLInputElement) || target.type !== 'checkbox') {
            return;
        }
        var treeNode = target.closest('.download-filter-tree-node');
        if (!treeNode) {
            return;
        }
        target.indeterminate = false;
        if (isOfficeTreeCheckbox(target)) {
            target.setAttribute('data-office-explicit', target.checked ? '1' : '0');
        }
        setTreeDescendantsChecked(treeNode, target.checked);
        syncHierarchyTrees();
    });
    modalPageTabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            activateModalPage(tab.getAttribute('data-download-modal-page-tab'));
        });
    });
    activateModalPage('level1');
    syncLevel3FieldFilters();
    syncHierarchyTrees();
};

window.initializeBoardTimeFilters = function () {
    document.querySelectorAll('.time-filter-form').forEach(function (form) {
        if (form.getAttribute('data-time-filter-bound') === '1') {
            return;
        }
        form.setAttribute('data-time-filter-bound', '1');
        var fromBeginning = form.querySelector('[data-time-filter-from-beginning]');
        var startInput = form.querySelector('[data-time-filter-start]');
        var tillNow = form.querySelector('[data-time-filter-till-now]');
        var endInput = form.querySelector('[data-time-filter-end]');
        var sync = function () {
            if (fromBeginning && startInput) {
                startInput.disabled = fromBeginning.checked;
            }
            if (tillNow && endInput) {
                endInput.disabled = tillNow.checked;
            }
        };
        if (fromBeginning) {
            fromBeginning.addEventListener('change', sync);
        }
        if (tillNow) {
            tillNow.addEventListener('change', sync);
        }
        sync();
    });
};

document.addEventListener('DOMContentLoaded', function () {
    window.initializeDownloadModalUi();
    window.initializeBoardTimeFilters();
});
</script>
<?php endif; ?>

<?= asset_board_perf_render(); ?>

<?php if (!$assetEmbedBoard && !$assetBoardFiltersOnly): ?>
    <?php require __DIR__ . '/footer.php'; ?>
<?php elseif ($assetBoardFiltersOnly): ?>
<?php elseif (!$assetComparisonTableOnly): ?>
    </main>
    <script src="<?= e(asset_url('public/assets/app.js')); ?>"></script>
    </body>
    </html>
<?php else: ?>
    </main>
    </body>
    </html>
<?php endif; ?>
