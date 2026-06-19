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
?>

<section class="card">
    <h2>Audit</h2>
    <p class="hint">Review how much data has been entered in each segment field using total count, office count, or the declared Level_1 items.</p>
</section>

<section class="card">
    <div class="segments audit-choice-bar">
        <?php foreach ($auditChoices as $auditKey => $auditLabel): ?>
            <a
                href="index.php?page=audit&audit_level=<?= urlencode((string)$auditKey); ?>"
                class="segment <?= $selectedAuditLevel === $auditKey ? 'is-active' : ''; ?>"
            ><?= e((string)$auditLabel); ?></a>
        <?php endforeach; ?>
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
                                <tr>
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

<?php require __DIR__ . '/footer.php'; ?>
