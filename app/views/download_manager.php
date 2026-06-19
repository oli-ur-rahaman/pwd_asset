<?php
require __DIR__ . '/header.php';

$commonLabels = asset_download_common_label_candidates();
$selectedLevel1Labels = asset_download_selected_level1_labels();
$downloadNamingTemplate = asset_download_filename_template();
$downloadNamingTokens = array_values(array_unique(array_merge(
    asset_download_available_naming_tokens(),
    asset_download_dynamic_naming_tokens()
)));
$segments = get_asset_segments(false);
$commonLookup = array_fill_keys($commonLabels, true);
?>

<section class="card">
    <h2>Download Manager</h2>
    <p class="hint">This page follows the revised 3-page structure. Use the top tabs to move across `Level_1`, `Level_2`, and `Level_3` inside one surface.</p>
</section>

<section class="card">
    <div class="download-manager-topbar" role="tablist" aria-label="Download Manager Pages">
        <button type="button" class="segment is-active" data-download-page-tab="level1">Level_1</button>
        <button type="button" class="segment" data-download-page-tab="level2">Level_2</button>
        <button type="button" class="segment" data-download-page-tab="level3">Level_3</button>
    </div>

    <section class="download-manager-page" data-download-page="level1">
        <h2>Level_1</h2>
        <p class="hint">Common fields across all active segments and `Office` belong here. This page defines the future common/grouping logic of the report.</p>

        <div class="download-manager-info-grid">
            <article class="download-manager-info-card">
                <h3>Level_1 Field</h3>
                <p class="hint">User will declare one of these as the Level_1 grouping field. `Office` is included as a special common option.</p>
                <form method="post" action="index.php" class="grid">
                    <?= csrf_input(); ?>
                    <input type="hidden" name="action" value="save_download_manager_level1">
                    <?php if (!$commonLabels): ?>
                        <p class="muted">No common fields were found across all active segments.</p>
                    <?php else: ?>
                        <div class="download-level1-grid">
                            <label class="inline-check">
                                <input type="checkbox" value="__office__" disabled>
                                <span>Office <small class="muted">(planning target)</small></span>
                            </label>
                            <?php foreach ($commonLabels as $label): ?>
                                <label class="inline-check">
                                    <input type="checkbox" name="level1_labels[]" value="<?= e($label); ?>" <?= in_array($label, $selectedLevel1Labels, true) ? 'checked' : ''; ?>>
                                    <span><?= e($label); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit">Save Level_1 Candidate Fields</button>
                    <?php endif; ?>
                </form>
            </article>

            <article class="download-manager-info-card">
                <h3>Future Common Columns</h3>
                <p class="hint">The remaining common fields will later be ordered here and shown as shared/common table columns. The chosen Level_1 field should not repeat here.</p>
                <?php if (!$commonLabels): ?>
                    <p class="muted">No common fields available.</p>
                <?php else: ?>
                    <div class="download-preview-list">
                        <?php foreach ($commonLabels as $position => $label): ?>
                            <div class="download-preview-row">
                                <span class="download-preview-serial"><?= $position + 1; ?></span>
                                <span class="download-preview-label"><?= e($label); ?></span>
                                <span class="muted">future sequence</span>
                            </div>
                        <?php endforeach; ?>
                        <div class="download-preview-row">
                            <span class="download-preview-serial">*</span>
                            <span class="download-preview-label">Office</span>
                            <span class="muted">future common option</span>
                        </div>
                    </div>
                <?php endif; ?>
            </article>

            <article class="download-manager-info-card">
                <h3>Future Common Sorting</h3>
                <p class="hint">Sorting of the common fields, except the chosen Level_1 field itself, will later be controlled from this page with ascending/descending sequence.</p>
                <div class="download-manager-placeholder">
                    <div class="download-placeholder-line">Field_1 -> ASC</div>
                    <div class="download-placeholder-line">Field_2 -> DESC</div>
                    <div class="download-placeholder-line">Field_3 -> ASC</div>
                </div>
            </article>
        </div>
    </section>

    <section class="download-manager-page hidden" data-download-page="level2">
        <h2>Level_2</h2>
        <p class="hint">This page reflects the segment-wise report fields. Common fields are excluded here because they belong to `Level_1`.</p>

        <div class="download-manager-segment-grid">
            <?php foreach ($segments as $segment): ?>
                <?php
                    $segmentId = (int)$segment['id'];
                    $fields = array_values(array_filter(
                        get_asset_fields(false, $segmentId),
                        static fn(array $field): bool => !isset($commonLookup[trim((string)($field['label'] ?? ''))])
                    ));
                ?>
                <article class="download-manager-info-card">
                    <h3><?= e((string)$segment['segment_name']); ?></h3>
                    <p class="hint">Segment-specific fields only. Common fields are intentionally excluded.</p>
                    <?php if (!$fields): ?>
                        <p class="muted">No segment-specific fields remain after removing common fields.</p>
                    <?php else: ?>
                        <div class="download-level2-field-list">
                            <?php foreach ($fields as $field): ?>
                                <label class="inline-check">
                                    <input type="checkbox" checked disabled>
                                    <span><?= e((string)$field['label']); ?><?php if ((string)($field['data_type'] ?? '') === 'file'): ?> <small class="muted">(file)</small><?php endif; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="download-manager-page hidden" data-download-page="level3">
        <h2>Level_3</h2>
        <p class="hint">This page now manages only filter fields per segment for download filtering. Sorting is no longer managed here.</p>

        <article class="download-manager-info-card">
            <h3>ZIP / File Naming Structure</h3>
            <p class="hint">This template is used for generated download file names. Default tokens are <code>{office_name}</code>, <code>{sub-division}</code>, <code>{division}</code>, <code>{circle}</code>, <code>{zone}</code>, <code>{segment}</code>, <code>{field_name}</code>, <code>{office_type}</code>, and <code>{asset_number}</code>. Common fields and superadmin-declared token fields are added below as dynamic tokens.</p>
            <form method="post" action="index.php" class="grid">
                <?= csrf_input(); ?>
                <input type="hidden" name="action" value="save_download_manager_naming_template">
                <label>Naming template
                    <input type="text" name="download_naming_template" value="<?= e($downloadNamingTemplate); ?>">
                </label>
                <div class="download-preview-list download-token-helper-grid">
                    <?php foreach ($downloadNamingTokens as $token): ?>
                        <div class="download-preview-row">
                            <span class="download-preview-label"><code>{<?= e($token); ?>}</code></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit">Save Naming Template</button>
            </form>
        </article>

        <form method="post" action="index.php" class="grid" id="download-manager-matrix-form">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="save_download_manager_matrix">
            <div class="table-wrap">
                <table class="download-manager-table">
                    <thead>
                        <tr>
                            <th>Segment</th>
                            <th>Filter (Non Level_1)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($segments as $segment): ?>
                            <?php
                                $segmentId = (int)$segment['id'];
                                $fields = asset_download_segment_matrix_fields($segmentId, 'filter');
                                $selectedFilterIds = array_flip(asset_download_segment_selected_field_ids($segmentId, 'filter'));
                            ?>
                            <tr>
                                <td><strong><?= e((string)$segment['segment_name']); ?></strong></td>
                                <?php foreach (['filter' => $selectedFilterIds] as $mode => $selectedLookup): ?>
                                    <td>
                                        <div class="download-manager-cell" data-download-cell>
                                            <div class="download-manager-chip-list">
                                                <?php foreach ($fields as $field): ?>
                                                    <?php
                                                        $fieldId = (int)$field['id'];
                                                        $isChecked = isset($selectedLookup[$fieldId]);
                                                        $inputId = 'download-' . $mode . '-' . $segmentId . '-' . $fieldId;
                                                    ?>
                                                    <label class="download-chip<?= $isChecked ? ' is-active' : ' is-removed'; ?>" data-download-chip>
                                                        <input
                                                            type="checkbox"
                                                            id="<?= e($inputId); ?>"
                                                            name="download_matrix[<?= $segmentId; ?>][<?= e($mode); ?>][]"
                                                            value="<?= $fieldId; ?>"
                                                            <?= $isChecked ? 'checked' : ''; ?>
                                                            hidden
                                                            data-download-chip-input>
                                                        <span class="download-chip-label"><?= e((string)$field['label']); ?></span>
                                                        <span class="download-chip-action">
                                                            <button type="button" class="download-chip-remove" data-download-chip-remove aria-label="Remove <?= e((string)$field['label']); ?> from <?= e($mode); ?>">x</button>
                                                        </span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="toolbar-row">
                                                <button type="button" class="btn-small button-link" data-download-cell-refresh>Refresh</button>
                                                <button type="button" class="btn-small btn-danger" data-download-cell-disable-all>Disable All</button>
                                            </div>
                                        </div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="submit">Save Filter Fields</button>
        </form>
    </section>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var storageKey = 'download_manager_active_tab';
    var tabs = Array.from(document.querySelectorAll('[data-download-page-tab]'));
    var pages = Array.from(document.querySelectorAll('[data-download-page]'));
    var activatePage = function (pageKey) {
        tabs.forEach(function (tab) {
            var isActive = tab.getAttribute('data-download-page-tab') === pageKey;
            tab.classList.toggle('is-active', isActive);
        });
        pages.forEach(function (page) {
            var isActive = page.getAttribute('data-download-page') === pageKey;
            page.classList.toggle('hidden', !isActive);
        });
        try {
            window.sessionStorage.setItem(storageKey, pageKey);
        } catch (error) {
        }
    };
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            activatePage(tab.getAttribute('data-download-page-tab'));
        });
    });
    var initialPage = 'level1';
    try {
        var storedPage = window.sessionStorage.getItem(storageKey);
        if (storedPage && pages.some(function (page) { return page.getAttribute('data-download-page') === storedPage; })) {
            initialPage = storedPage;
        }
    } catch (error) {
    }
    activatePage(initialPage);
});
</script>

<?php require __DIR__ . '/footer.php'; ?>
