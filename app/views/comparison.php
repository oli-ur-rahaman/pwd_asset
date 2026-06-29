<?php
require __DIR__ . '/header.php';

$user = current_user();
if (!can_manage_superadmin_scope($user)) {
    http_response_code(403);
    exit('Not allowed.');
}

$activeSegmentId = asset_active_segment_id((int)request_str('segment_id', '0'));
$segments = get_asset_segments();
$showSegmentSelector = count($segments) > 1;

$selectedZone = (int)request_str('zone_id', '0');
$selectedCircle = (int)request_str('circle_id', '0');
$selectedDivision = (int)request_str('division_id', '0');
$selectedSubdivision = (int)request_str('subdivision_id', '0');

$zones = db()->query('SELECT id, office_name FROM zones ORDER BY office_name')->fetchAll();
$circles = db()->query('SELECT id, office_name, zone_id FROM circles ORDER BY office_name')->fetchAll();
$divisions = db()->query('SELECT id, office_name, zone_id, circle_id FROM divisions ORDER BY office_name')->fetchAll();
$subdivisions = db()->query('SELECT id, office_name, zone_id, circle_id, division_id FROM subdivisions ORDER BY office_name')->fetchAll();

$previewOfficeType = 0;
$previewOfficeId = 0;
if ($selectedSubdivision > 0) {
    $subdivisionMeta = find_subdivision_with_hierarchy($selectedSubdivision);
    if ($subdivisionMeta) {
        $selectedZone = (int)($subdivisionMeta['zone_id'] ?? 0);
        $selectedCircle = (int)($subdivisionMeta['circle_id'] ?? 0);
        $selectedDivision = (int)($subdivisionMeta['division_id'] ?? 0);
    }
    $previewOfficeType = 5;
    $previewOfficeId = $selectedSubdivision;
} elseif ($selectedDivision > 0) {
    $divisionMeta = find_division_with_hierarchy($selectedDivision);
    if ($divisionMeta) {
        $selectedZone = (int)($divisionMeta['zone_id'] ?? 0);
        $selectedCircle = (int)($divisionMeta['circle_id'] ?? 0);
    }
    $previewOfficeType = 4;
    $previewOfficeId = $selectedDivision;
} elseif ($selectedCircle > 0) {
    $circleMeta = find_circle_with_zone($selectedCircle);
    if ($circleMeta) {
        $selectedZone = (int)($circleMeta['zone_id'] ?? 0);
    }
    $previewOfficeType = 3;
    $previewOfficeId = $selectedCircle;
} elseif ($selectedZone > 0) {
    $previewOfficeType = 2;
    $previewOfficeId = $selectedZone;
}

$previewUser = $previewOfficeType > 0 && $previewOfficeId > 0
    ? asset_build_comparison_preview_user($previewOfficeType, $previewOfficeId)
    : null;
$previewOfficeLabel = $previewUser ? current_office_label($previewUser) : '';
$previewScopeLabel = $previewOfficeType > 0 ? asset_office_type_label($previewOfficeType) : '';

$comparisonBaseParams = [
    'page' => 'comparison',
    'segment_id' => $activeSegmentId,
    'zone_id' => $selectedZone,
    'circle_id' => $selectedCircle,
    'division_id' => $selectedDivision,
    'subdivision_id' => $selectedSubdivision,
];

$superadminFrameUrl = 'index.php?' . http_build_query([
    'page' => 'board_embed',
    'segment_id' => $activeSegmentId,
    'zone_id' => $selectedZone,
    'circle_id' => $selectedCircle,
    'division_id' => $selectedDivision,
    'subdivision_id' => $selectedSubdivision,
    'comparison_table_only' => 1,
]);

$previewFrameUrl = $previewUser
    ? 'index.php?' . http_build_query([
        'page' => 'board_preview',
        'segment_id' => $activeSegmentId,
        'preview_office_type' => $previewOfficeType,
        'preview_office_id' => $previewOfficeId,
        'office_view_scope' => 'my_office',
        'comparison_table_only' => 1,
    ])
    : '';
?>

<section class="card hero-card">
    <div class="hero-row">
        <div class="hero-copy">
            <h2 class="hero-title">Comparison</h2>
            <p class="hero-subtitle">Compare the exact office view and the exact superadmin view for the same segment.</p>
        </div>
    </div>
</section>

<?php if ($showSegmentSelector): ?>
<section class="card segment-switch-card">
    <div class="toolbar-row scope-switch-row">
        <?php foreach ($segments as $segment): ?>
            <?php
                $segmentParams = $comparisonBaseParams;
                $segmentParams['segment_id'] = (int)$segment['id'];
            ?>
            <a href="index.php?<?= e(http_build_query($segmentParams)); ?>" class="button-link<?= $activeSegmentId === (int)$segment['id'] ? ' is-active' : ''; ?>">
                <?= e((string)$segment['segment_name']); ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="card">
    <h2>Office Filter</h2>
    <form method="get" action="index.php" class="grid comparison-filter-grid">
        <input type="hidden" name="page" value="comparison">
        <input type="hidden" name="segment_id" value="<?= e((string)$activeSegmentId); ?>">
        <label>Zone
            <select name="zone_id">
                <option value="0">Select Zone</option>
                <?php foreach ($zones as $zone): ?>
                    <option value="<?= e((string)$zone['id']); ?>" <?= $selectedZone === (int)$zone['id'] ? 'selected' : ''; ?>><?= e((string)$zone['office_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Circle
            <select name="circle_id">
                <option value="0">Select Circle</option>
                <?php foreach ($circles as $circle): ?>
                    <option value="<?= e((string)$circle['id']); ?>" <?= $selectedCircle === (int)$circle['id'] ? 'selected' : ''; ?>><?= e((string)$circle['office_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Division
            <select name="division_id">
                <option value="0">Select Division</option>
                <?php foreach ($divisions as $division): ?>
                    <option value="<?= e((string)$division['id']); ?>" <?= $selectedDivision === (int)$division['id'] ? 'selected' : ''; ?>><?= e((string)$division['office_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Sub-division
            <select name="subdivision_id">
                <option value="0">Select Sub-division</option>
                <?php foreach ($subdivisions as $subdivision): ?>
                    <option value="<?= e((string)$subdivision['id']); ?>" <?= $selectedSubdivision === (int)$subdivision['id'] ? 'selected' : ''; ?>><?= e((string)$subdivision['office_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="comparison-filter-actions">
            <button type="submit">Apply</button>
            <a href="index.php?<?= e(http_build_query(['page' => 'comparison', 'segment_id' => $activeSegmentId])); ?>" class="button-link">Reset</a>
        </div>
    </form>
    <?php if ($previewUser): ?>
        <p class="hint comparison-preview-summary">Previewing <strong><?= e($previewScopeLabel); ?></strong>: <?= e($previewOfficeLabel); ?></p>
    <?php else: ?>
        <p class="hint comparison-preview-summary">Select a zone, circle, division, or sub-division to load the office view.</p>
    <?php endif; ?>
</section>

<section class="comparison-split-layout">
    <article class="card comparison-pane">
        <div class="comparison-pane-head">
            <h2>Office View</h2>
            <p class="hint">Exact office-side board logic. Preview only.</p>
        </div>
        <?php if ($previewFrameUrl !== ''): ?>
            <iframe class="comparison-frame" src="<?= e($previewFrameUrl); ?>" title="Office comparison view" loading="lazy" sandbox="allow-same-origin"></iframe>
        <?php else: ?>
            <div class="comparison-empty-state">Choose an office from the filter card to load the office-side view.</div>
        <?php endif; ?>
    </article>
    <article class="card comparison-pane">
        <div class="comparison-pane-head">
            <h2>Superadmin View</h2>
            <p class="hint">Exact superadmin board logic.</p>
        </div>
        <iframe class="comparison-frame" src="<?= e($superadminFrameUrl); ?>" title="Superadmin comparison view" loading="lazy"></iframe>
    </article>
</section>

<?php require __DIR__ . '/footer.php'; ?>
