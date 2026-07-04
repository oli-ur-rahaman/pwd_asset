<?php
$rowNumberOffset = isset($rowNumberOffset) ? (int)$rowNumberOffset : 0;
$renderEmptyState = !isset($renderEmptyState) || $renderEmptyState;
$assets = is_array($assets ?? null) ? $assets : [];
$fileIconMetaFragment = static function (string $originalName): array {
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
if ($renderEmptyState && !$assets):
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
        <td><?= e((string)($rowNumberOffset + $index + 1)); ?></td>
        <?php if ($showAssetNumber && !empty($visibleColumnKeys['asset_number'])): ?><td><?= e((string)$asset['asset_number']); ?></td><?php endif; ?>
        <?php if ((is_superadmin() || $isUnderMeView) && !empty($visibleColumnKeys['office_name'])): ?><td><?= e((string)$asset['office_type_label'] . ' - ' . (string)$asset['office_name']); ?></td><?php endif; ?>
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
                            $fileCount = count($assetFiles);
                            $maxFiles = max(1, (int)($fileRule['max_files'] ?? 1));
                            $isMultipleFile = (int)$fileRule['is_multiple'] === 1;
                            $canShowInlineAdd = !$isMultipleFile || $fileCount < $maxFiles;
                            $inlineAddLabel = $isMultipleFile
                                ? ($assetFiles ? 'Add More (max ' . $maxFiles . ')' : 'Add File')
                                : 'Add or Replace';
                        ?>
                        <?php if ($assetFiles): ?>
                            <div class="file-link-list">
                                <?php foreach ($assetFiles as $fileRow): ?>
                                    <?php $meta = $fileIconMetaFragment((string)$fileRow['original_name']); ?>
                                    <a href="index.php?page=asset_file&id=<?= e((string)$fileRow['id']); ?>" class="file-chip file-chip-icon-only <?= e($meta['class']); ?>" target="_blank" rel="noopener" title="<?= e((string)$fileRow['original_name']); ?>">
                                        <span class="file-chip-icon"><?= e($meta['icon']); ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif (!$canModifyAssets || is_superadmin() || $isUnderMeView): ?>
                            <span class="muted">No file</span>
                        <?php endif; ?>
                        <?php if ($canModifyAssets && !is_superadmin() && !$isUnderMeView && $canShowInlineAdd): ?>
                            <form method="post" action="index.php" enctype="multipart/form-data" class="inline-file-upload-form" id="<?= $formId; ?>">
                                <?= csrf_input(); ?>
                                <input type="hidden" name="action" value="asset_upload_field_files">
                                <input type="hidden" name="asset_id" value="<?= e((string)$asset['id']); ?>">
                                <input type="hidden" name="field_key" value="<?= e((string)$field['field_key']); ?>">
                                <input
                                    type="file"
                                    name="field_files[<?= e((string)$field['field_key']); ?>][]"
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
                                <label for="<?= $formId; ?>-input" class="btn-small inline-file-add-button"><?= e($inlineAddLabel); ?></label>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <?= e((string)($asset['values'][$field['field_key']] ?? '')); ?>
                    <?php endif; ?>
                </td>
                <?php if ((string)($field['data_type'] ?? '') === 'bimh' && !empty($visibleColumnKeys[$field['field_key'] . '__est_name'])): ?>
                    <td>
                        <?php $estName = trim((string)($asset['values'][$field['field_key'] . '__est_name'] ?? '')); ?>
                        <?php if ($estName === 'BIMH ID is not in the Database.'): ?>
                            <span class="bimh-est-name-box bimh-est-name-inline is-not-found"><?= e($estName); ?></span>
                        <?php else: ?>
                            <?= e($estName); ?>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
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
