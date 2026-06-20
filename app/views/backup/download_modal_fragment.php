<?php
$user = current_user();
$activeSegmentId = asset_active_segment_id((int)request_str('segment_id', '0'));
$currentOfficeViewScope = request_str('office_view_scope', 'my_office');
$downloadMode = request_str('download_mode', 'initial');

if ($downloadMode === 'level3') {
    $downloadCommonFilterCatalog = asset_download_common_field_catalog($user, $currentOfficeViewScope);
    $downloadCommonLabelCandidates = asset_download_common_label_candidates();
    $downloadAvailableSegments = get_asset_segments(false);
    $downloadSegmentConfigs = [];
    foreach ($downloadAvailableSegments as $downloadSegment) {
        $downloadSegmentId = (int)$downloadSegment['id'];
        $downloadSegmentFields = array_values(array_filter(
            get_asset_fields(false, $downloadSegmentId),
            static fn(array $field): bool => (int)($field['active_status'] ?? 0) === 1
        ));
        $downloadSegmentAssets = asset_download_accessible_assets_for_segment($downloadSegmentId, $user, $currentOfficeViewScope);
        $downloadCatalog = build_asset_filter_catalog($downloadSegmentAssets, $downloadSegmentFields, $downloadSegmentId, false);
        $downloadFilterFieldMap = [];
        foreach (asset_download_effective_filter_fields($downloadSegmentId) as $filterField) {
            $downloadFilterFieldMap[(string)$filterField['field_key']] = $filterField;
        }
        $downloadSegmentConfigs[$downloadSegmentId] = [
            'segment' => $downloadSegment,
            'fields' => $downloadSegmentFields,
            'segment_fields_only' => array_values(array_filter(
                $downloadSegmentFields,
                static fn(array $field): bool => !in_array(trim((string)($field['label'] ?? '')), $downloadCommonLabelCandidates, true)
            )),
            'catalog' => $downloadCatalog,
            'filter_fields' => $downloadFilterFieldMap,
        ];
    }

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
            echo '<div class="download-filter-tree download-office-tree">';
            foreach ($zones as $zoneId => $zoneMeta) {
                echo '<div class="download-filter-tree-node depth-1">';
                echo '<label class="download-tree-check">';
                echo '<input type="checkbox" name="' . e($inputBase . '[zone_ids][]') . '" value="' . e((string)$zoneId) . '">';
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
                    echo '<input type="checkbox" name="' . e($inputBase . '[circle_ids][]') . '" value="' . e((string)$circleId) . '">';
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
                        echo '<input type="checkbox" name="' . e($inputBase . '[division_ids][]') . '" value="' . e((string)$divisionId) . '">';
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
                            echo '<input type="checkbox" name="' . e($inputBase . '[subdivision_ids][]') . '" value="' . e((string)$subdivisionId) . '">';
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
            if ($mode === 'common' && $childKey === '') {
                $childKey = (string)($filterMeta['child_identifier'] ?? '');
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
            $optionMap = ['__has_file__' => 'Have file', '__no_file__' => 'No file'];
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
    ?>
<div data-download-fragment-level3>
    <section class="download-layer-card">
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
            <?php foreach (array_merge(['Office'], asset_download_selected_level1_labels()) as $label): ?>
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
</div>

<div data-download-fragment-common>
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
    <?php
    return;
}

$downloadLevel1Labels = array_values(array_unique(array_merge(['Office'], asset_download_selected_level1_labels())));
$downloadLevel1DefaultLabel = $downloadLevel1Labels[0] ?? 'Office';
$downloadCommonOptionMap = ['__office__' => 'Office'];
foreach ($downloadLevel1Labels as $label) {
    if ($label === 'Office') {
        continue;
    }
    $downloadCommonOptionMap[$label] = $label;
}
$downloadCommonLabelCandidates = asset_download_common_label_candidates();
$downloadNamingTokens = array_values(array_unique(array_merge(
    asset_download_available_naming_tokens(),
    asset_download_dynamic_naming_tokens()
)));
$downloadAvailableSegments = get_asset_segments(false);
$downloadModuleReady = !empty($downloadLevel1Labels) && !empty($downloadAvailableSegments);
$downloadDefaultSegmentIds = array_map(static fn(array $segment): int => (int)$segment['id'], $downloadAvailableSegments);
$downloadSegmentConfigs = [];
foreach ($downloadAvailableSegments as $downloadSegment) {
    $downloadSegmentId = (int)$downloadSegment['id'];
    $downloadSegmentFields = array_values(array_filter(
        get_asset_fields(false, $downloadSegmentId),
        static fn(array $field): bool => (int)($field['active_status'] ?? 0) === 1
    ));
    $downloadSegmentConfigs[$downloadSegmentId] = [
        'segment' => $downloadSegment,
        'fields' => $downloadSegmentFields,
        'segment_fields_only' => array_values(array_filter(
            $downloadSegmentFields,
            static fn(array $field): bool => !in_array(trim((string)($field['label'] ?? '')), $downloadCommonLabelCandidates, true)
        )),
    ];
}
$downloadLevel3Url = 'index.php?' . http_build_query([
    'page' => 'download_modal_fragment',
    'segment_id' => $activeSegmentId,
    'office_view_scope' => $currentOfficeViewScope,
    'download_mode' => 'level3',
]);
?>
<div data-download-fragment-body>
    <form method="post" action="index.php" class="grid download-data-form" id="download-data-form">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="asset_download_data">
        <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
        <input type="hidden" name="office_view_scope" value="<?= e((string)$currentOfficeViewScope); ?>">

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
                                <span class="download-preview-label"><code>{<?= e($token); ?></code></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <section class="download-layer-card" data-download-non-zip-only>
                    <div class="download-layer-head">
                        <h4>Level 1 Setup</h4>
                        <p class="hint">For PDF and Excel, manage all Level_1 fields in one table. Choose one field as `is_level`. The remaining fields keep their sequence and sort direction here.</p>
                    </div>
                    <?php if (!$downloadModuleReady): ?>
                        <p class="muted">No Level 1 field is available. Configure Download Manager first.</p>
                    <?php else: ?>
                        <input type="hidden" name="download_level1_label" id="download-level1-label" value="<?= e((string)$downloadLevel1DefaultLabel); ?>">
                        <div class="table-wrap">
                            <table class="download-manager-table download-level1-table">
                                <thead>
                                    <tr>
                                        <th>Field Name</th>
                                        <th>Show</th>
                                        <th>Is_Level1</th>
                                        <th>Serial</th>
                                        <th>Sorting</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $level1RowSerial = 1; ?>
                                    <?php foreach ($downloadLevel1Labels as $label): ?>
                                        <?php
                                            $commonKey = $label === 'Office' ? '__office__' : $label;
                                            $isSelectedLevel = $label === $downloadLevel1DefaultLabel;
                                        ?>
                                        <tr data-download-level1-row data-level1-label="<?= e((string)$label); ?>" data-common-field="<?= e((string)$commonKey); ?>" data-is-office="<?= $commonKey === '__office__' ? '1' : '0'; ?>">
                                            <td><?= e((string)$label); ?></td>
                                            <td>
                                                <label class="inline-check download-inline-check">
                                                    <input type="checkbox" <?= $isSelectedLevel ? 'checked disabled' : 'checked'; ?> data-download-level1-visible>
                                                    <span></span>
                                                </label>
                                            </td>
                                            <td>
                                                <label class="inline-check download-inline-check">
                                                    <input type="radio" name="download_level1_choice" value="<?= e((string)$label); ?>" <?= $isSelectedLevel ? 'checked' : ''; ?> data-download-level1-choice>
                                                    <span></span>
                                                </label>
                                            </td>
                                            <td>
                                                <input type="number" min="1" value="<?= $level1RowSerial; ?>" data-download-level1-serial <?= $isSelectedLevel ? 'disabled' : ''; ?>>
                                                <input type="checkbox" name="download_common_columns[]" value="<?= e((string)$commonKey); ?>" <?= $isSelectedLevel ? '' : 'checked'; ?> hidden data-download-common-column-input>
                                                <input type="hidden" name="download_common_column_order[<?= e((string)$commonKey); ?>]" value="<?= $level1RowSerial; ?>" data-download-common-column-order>
                                                <input type="checkbox" name="download_common_sort[<?= e((string)$commonKey); ?>][enabled]" value="1" <?= $isSelectedLevel ? '' : 'checked'; ?> hidden data-download-common-sort-enabled>
                                                <input type="hidden" name="download_common_sort[<?= e((string)$commonKey); ?>][order]" value="<?= $level1RowSerial; ?>" data-download-common-sort-order>
                                            </td>
                                            <td>
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
                <div class="download-level3-async-body" data-download-level3-url="<?= e($downloadLevel3Url); ?>" data-download-level3-loaded="0">
                    <p class="muted">Loading filters when needed...</p>
                </div>
            </section>
        </div>

        <div class="modal-actions">
            <button type="submit" class="btn-secondary" <?= $downloadModuleReady ? '' : 'disabled'; ?>>Download</button>
            <button type="button" class="modal-close" data-close="download-data-modal">Cancel</button>
        </div>
    </form>
</div>
