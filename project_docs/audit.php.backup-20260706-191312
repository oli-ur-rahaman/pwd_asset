<?php
require __DIR__ . '/header.php';

$user = current_user();
$auditViewScope = 'my_office';
$auditChoices = asset_audit_level_choices($user, $auditViewScope);
$auditValueMap = asset_audit_level_value_map($user, $auditViewScope);
$selectedAuditLevel = request_str('audit_level', 'count');
if (!isset($auditChoices[$selectedAuditLevel])) {
    $selectedAuditLevel = 'count';
}
$auditSegments = asset_audit_segments($user, $auditViewScope);
$selectedAuditLabel = (string)($auditChoices[$selectedAuditLevel] ?? 'Row_count');
?>

<section class="card">
    <h2>Audit</h2>
    <p class="hint">Review how much data has been entered in each segment field using row count, office count, or the declared Level_1 items.</p>
</section>

<section class="card">
    <div class="hero-row audit-page-topbar">
        <div class="segments audit-choice-bar">
            <?php foreach ($auditChoices as $auditKey => $auditLabel): ?>
                <a
                    href="index.php?page=audit&audit_level=<?= urlencode((string)$auditKey); ?>"
                    class="segment <?= $selectedAuditLevel === $auditKey ? 'is-active' : ''; ?>"
                ><?= e((string)$auditLabel); ?></a>
            <?php endforeach; ?>
        </div>
        <div class="action-row">
            <a class="button-link" href="index.php?<?= e(http_build_query(['page' => 'audit_export', 'scope' => 'page', 'format' => 'excel', 'audit_level' => $selectedAuditLevel])); ?>">Excel</a>
            <a class="button-link" href="index.php?<?= e(http_build_query(['page' => 'audit_export', 'scope' => 'page', 'format' => 'pdf', 'audit_level' => $selectedAuditLevel])); ?>">PDF</a>
        </div>
    </div>
</section>

<section class="audit-grid">
    <?php foreach ($auditSegments as $auditSegment): ?>
        <?php
            $segment = $auditSegment['segment'];
            $segmentId = (int)$segment['id'];
            $fields = $auditSegment['fields'];
            $assets = $auditSegment['assets'];
        ?>
        <section class="card">
            <h3><?= e((string)$segment['segment_name']); ?></h3>
            <div class="table-wrap">
                <table class="audit-summary-table">
                    <thead>
                        <tr>
                            <th class="audit-col-sl">SL No</th>
                            <th>Field Name</th>
                            <th class="audit-col-count">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$fields): ?>
                            <tr>
                                <td colspan="3" class="muted">No active fields found in this segment.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fields as $index => $field): ?>
                                <tr
                                    class="audit-detail-row <?= $selectedAuditLevel === 'count' ? 'is-disabled-audit-row' : ''; ?>"
                                    data-audit-row="<?= $selectedAuditLevel === 'count' ? '0' : '1'; ?>"
                                    data-segment-id="<?= $segmentId; ?>"
                                    data-field-key="<?= e((string)($field['field_key'] ?? '')); ?>"
                                    data-field-label="<?= e((string)($field['label'] ?? '')); ?>"
                                    <?= $selectedAuditLevel === 'count' ? '' : 'tabindex="0"'; ?>
                                >
                                    <td><?= $index + 1; ?></td>
                                    <td><?= e((string)($field['label'] ?? '')); ?></td>
                                    <td><?= e(asset_audit_count_cell($assets, $field, $selectedAuditLevel, $auditValueMap, $segmentId)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endforeach; ?>
</section>

<div class="modal-backdrop" id="auditDetailModal" aria-hidden="true">
    <div class="modal-card audit-detail-modal-card">
        <button type="button" class="welcome-modal-close audit-detail-close" id="auditDetailClose" aria-label="Close">&times;</button>
        <div class="flash-modal-head audit-detail-head">
            <div>
                <h3 id="auditDetailTitle">Audit Details</h3>
                <p class="hint" id="auditDetailSubtitle">Items counted under <?= e($selectedAuditLabel); ?> will appear here.</p>
            </div>
        </div>
        <div class="audit-detail-toolbar">
            <div class="segments audit-detail-toggle-bar">
                <button type="button" class="segment is-active" data-audit-mode="all">All</button>
                <button type="button" class="segment" data-audit-mode="provided">Provided</button>
                <button type="button" class="segment" data-audit-mode="not_provided">Not Provided</button>
            </div>
            <div class="audit-detail-meta">
                <span class="audit-detail-count" id="auditDetailCount">Count: 0</span>
                <div class="action-row">
                    <a class="button-link" id="auditDetailExcel" href="#" target="_blank" rel="noopener">Excel</a>
                    <a class="button-link" id="auditDetailPdf" href="#" target="_blank" rel="noopener">PDF</a>
                </div>
            </div>
        </div>
        <div id="auditDetailContent" class="audit-detail-content muted">Select a row to see the counted items.</div>
    </div>
</div>

<script>
(function () {
    var selectedAuditLevel = <?= json_encode($selectedAuditLevel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    var selectedAuditLabel = <?= json_encode($selectedAuditLabel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    var modal = document.getElementById('auditDetailModal');
    var closeButton = document.getElementById('auditDetailClose');
    var title = document.getElementById('auditDetailTitle');
    var subtitle = document.getElementById('auditDetailSubtitle');
    var content = document.getElementById('auditDetailContent');
    var countBox = document.getElementById('auditDetailCount');
    var excelLink = document.getElementById('auditDetailExcel');
    var pdfLink = document.getElementById('auditDetailPdf');
    var modeButtons = Array.prototype.slice.call(document.querySelectorAll('[data-audit-mode]'));
    var rowNodes = Array.prototype.slice.call(document.querySelectorAll('[data-audit-row]'));
    var state = {
        mode: 'all',
        payload: null,
        segmentId: '',
        fieldKey: '',
        fieldLabel: ''
    };

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function openModal() {
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    }

    function hierarchyHasMatch(node, mode) {
        var children = Array.isArray(node.children) ? node.children : [];
        var status = node.applicable === false ? 'NA' : (node.provided ? 'Provided' : 'Not Provided');
        if (mode === 'all') {
            return true;
        }
        var ownMatch = mode === 'provided' ? status === 'Provided' : status === 'Not Provided';
        if (ownMatch) {
            return true;
        }
        return children.some(function (child) {
            return hierarchyHasMatch(child, mode);
        });
    }

    function countHierarchyNodes(nodes, mode) {
        if (!Array.isArray(nodes) || !nodes.length) {
            return 0;
        }
        var total = 0;
        nodes.forEach(function (node) {
            if (!hierarchyHasMatch(node, mode)) {
                return;
            }
            var status = node.applicable === false ? 'NA' : (node.provided ? 'Provided' : 'Not Provided');
            if (mode === 'all' || (mode === 'provided' && status === 'Provided') || (mode === 'not_provided' && status === 'Not Provided')) {
                total += 1;
            }
            total += countHierarchyNodes(Array.isArray(node.children) ? node.children : [], mode);
        });
        return total;
    }

    function countListItems(items, mode) {
        if (!Array.isArray(items)) {
            return 0;
        }
        return items.filter(function (item) {
            if (mode === 'provided') {
                return !!item.provided;
            }
            if (mode === 'not_provided') {
                return !item.provided;
            }
            return true;
        }).length;
    }

    function summarizeOfficeHierarchy(nodes, mode, counts) {
        if (!Array.isArray(nodes)) {
            return;
        }
        nodes.forEach(function (node) {
            if (!node || !hierarchyHasMatch(node, mode)) {
                return;
            }
            var status = node.applicable === false ? 'NA' : (node.provided ? 'Provided' : 'Not Provided');
            var officeType = parseInt(node.office_type || 0, 10);
            var hasUser = node.has_user !== false;
            if (officeType > 0 && node.applicable !== false && hasUser) {
                if (mode === 'all' || (mode === 'provided' && status === 'Provided') || (mode === 'not_provided' && status === 'Not Provided')) {
                    counts[officeType] = (counts[officeType] || 0) + 1;
                }
            }
            summarizeOfficeHierarchy(Array.isArray(node.children) ? node.children : [], mode, counts);
        });
    }

    function summarizeConditionalHierarchy(nodes, mode) {
        var summary = { primary: 0, secondary: 0 };
        if (!Array.isArray(nodes)) {
            return summary;
        }
        nodes.forEach(function (primaryNode) {
            if (!primaryNode || !hierarchyHasMatch(primaryNode, mode)) {
                return;
            }
            var primaryProvided = !!primaryNode.provided;
            if (mode === 'all' || (mode === 'provided' && primaryProvided) || (mode === 'not_provided' && !primaryProvided)) {
                summary.primary += 1;
            }
            var children = Array.isArray(primaryNode.children) ? primaryNode.children : [];
            children.forEach(function (secondaryNode) {
                if (!secondaryNode || !hierarchyHasMatch(secondaryNode, mode)) {
                    return;
                }
                var secondaryProvided = !!secondaryNode.provided;
                if (mode === 'all' || (mode === 'provided' && secondaryProvided) || (mode === 'not_provided' && !secondaryProvided)) {
                    summary.secondary += 1;
                }
            });
        });
        return summary;
    }

    function updateCount() {
        if (!state.payload) {
            countBox.textContent = 'Count: 0';
            return;
        }
        if (state.payload.kind === 'hierarchy' && state.payload.level_label === 'Office') {
            var officeCounts = {};
            summarizeOfficeHierarchy(state.payload.tree || [], state.mode, officeCounts);
            var officeLabels = {
                2: 'Zone',
                3: 'Circle',
                4: 'Division',
                5: 'Sub-division'
            };
            var parts = [];
            [2, 3, 4, 5].forEach(function (officeType) {
                if ((officeCounts[officeType] || 0) > 0) {
                    parts.push(officeLabels[officeType] + ' - ' + officeCounts[officeType]);
                }
            });
            countBox.textContent = parts.length ? parts.join(', ') : 'Count: 0';
            return;
        }
        if (state.payload.kind === 'hierarchy') {
            var conditionalCounts = summarizeConditionalHierarchy(state.payload.tree || [], state.mode);
            var primaryLabel = state.payload.primary_label || 'Primary Field';
            var secondaryLabel = state.payload.secondary_label || 'Secondary Field';
            countBox.textContent = primaryLabel + ' - ' + conditionalCounts.primary + ', ' + secondaryLabel + ' - ' + conditionalCounts.secondary;
            return;
        }
        var count = state.payload.kind === 'hierarchy'
            ? countHierarchyNodes(state.payload.tree || [], state.mode)
            : countListItems(state.payload.items || [], state.mode);
        countBox.textContent = 'Count: ' + count;
    }

    function updateExportLinks() {
        if (!state.segmentId || !state.fieldKey) {
            excelLink.setAttribute('href', '#');
            pdfLink.setAttribute('href', '#');
            return;
        }
        var query = new URLSearchParams({
            page: 'audit_export',
            scope: 'detail',
            format: 'excel',
            audit_level: selectedAuditLevel,
            segment_id: state.segmentId,
            field_key: state.fieldKey,
            detail_mode: state.mode
        });
        excelLink.setAttribute('href', 'index.php?' + query.toString());
        query.set('format', 'pdf');
        pdfLink.setAttribute('href', 'index.php?' + query.toString());
    }

    function setMode(mode) {
        state.mode = mode;
        modeButtons.forEach(function (button) {
            button.classList.toggle('is-active', button.getAttribute('data-audit-mode') === mode);
        });
        renderPayload();
        updateExportLinks();
    }

    function renderHierarchyNodes(nodes, mode, depth) {
        if (!Array.isArray(nodes) || !nodes.length) {
            return '';
        }
        var html = '';
        nodes.forEach(function (node) {
            if (!hierarchyHasMatch(node, mode)) {
                return;
            }
            var children = Array.isArray(node.children) ? node.children : [];
            var stateClass = node.applicable === false ? 'is-na' : (node.provided ? 'is-provided' : 'is-missing');
            var childrenHtml = renderHierarchyNodes(children, mode, depth + 1);
            html += '<div class="download-filter-tree-node depth-' + depth + ' audit-detail-tree-node ' + stateClass + '">';
            html += '<div class="audit-detail-node-label">' + escapeHtml(node.label) + '</div>';
            if (childrenHtml) {
                html += '<div class="download-filter-tree-children">' + childrenHtml + '</div>';
            }
            html += '</div>';
        });
        return html;
    }

    function renderListItems(items, mode) {
        if (!Array.isArray(items) || !items.length) {
            return '<p class="muted">No items found for this selection.</p>';
        }
        var filtered = items.filter(function (item) {
            if (mode === 'provided') {
                return !!item.provided;
            }
            if (mode === 'not_provided') {
                return !item.provided;
            }
            return true;
        });
        if (!filtered.length) {
            return '<p class="muted">No items match the selected view.</p>';
        }
        return '<div class="audit-detail-list">' + filtered.map(function (item) {
            return '<div class="audit-detail-list-item ' + (item.provided ? 'is-provided' : 'is-missing') + '">' + escapeHtml(item.label) + '</div>';
        }).join('') + '</div>';
    }

    function renderPayload() {
        if (!state.payload) {
            content.innerHTML = '<p class="muted">Select a row to see the counted items.</p>';
            updateCount();
            return;
        }
        title.textContent = state.payload.field_label + ' - ' + state.payload.level_label;
        subtitle.textContent = state.payload.segment_name + ' segment';
        if (state.payload.kind === 'hierarchy') {
            var treeHtml = renderHierarchyNodes(state.payload.tree || [], state.mode, 1);
            content.innerHTML = treeHtml
                ? '<div class="download-filter-tree audit-detail-tree">' + treeHtml + '</div>'
                : '<p class="muted">No items match the selected view.</p>';
            updateCount();
            return;
        }
        content.innerHTML = renderListItems(state.payload.items || [], state.mode);
        updateCount();
    }

    function loadDetail(row) {
        var segmentId = row.getAttribute('data-segment-id') || '';
        var fieldKey = row.getAttribute('data-field-key') || '';
        state.segmentId = segmentId;
        state.fieldKey = fieldKey;
        state.fieldLabel = row.getAttribute('data-field-label') || '';
        rowNodes.forEach(function (node) {
            node.classList.remove('is-active-audit-row');
        });
        row.classList.add('is-active-audit-row');
        title.textContent = state.fieldLabel + ' - ' + selectedAuditLabel;
        subtitle.textContent = 'Loading counted items...';
        countBox.textContent = 'Count: 0';
        updateExportLinks();
        content.innerHTML = '<div class="audit-detail-loading"><span class="spinner small"></span><span>Loading details...</span></div>';
        openModal();
        fetch('index.php?page=audit_detail&segment_id=' + encodeURIComponent(segmentId) + '&field_key=' + encodeURIComponent(fieldKey) + '&audit_level=' + encodeURIComponent(selectedAuditLevel), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok || !data.ok) {
                        throw new Error((data && data.message) ? data.message : 'Unable to load audit details.');
                    }
                    return data;
                });
            })
            .then(function (data) {
                state.payload = data;
                renderPayload();
            })
            .catch(function (error) {
                state.payload = null;
                subtitle.textContent = 'Unable to load audit details';
                countBox.textContent = 'Count: 0';
                content.innerHTML = '<div class="alert error">' + escapeHtml(error.message || 'Unable to load audit details.') + '</div>';
            });
    }

    modeButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            setMode(button.getAttribute('data-audit-mode') || 'all');
        });
    });

    rowNodes.forEach(function (row) {
        if (row.getAttribute('data-audit-row') !== '1') {
            return;
        }
        row.addEventListener('click', function () {
            loadDetail(row);
        });
        row.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                loadDetail(row);
            }
        });
    });

    closeButton.addEventListener('click', closeModal);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('open')) {
            closeModal();
        }
    });
    updateExportLinks();
})();
</script>

<?php require __DIR__ . '/footer.php'; ?>
