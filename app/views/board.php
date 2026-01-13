<?php
require __DIR__ . '/header.php';
$user = current_user();
$fy = get_current_fy();
$office_name = get_office_name_for_user($user);
$today = date('Y-m-d');
$office_role = (int)($user['office_role'] ?? 1);
$office_type = (int)($user['office_type'] ?? 4);
$is_division = is_division_user();

$all_zones = db()->query('SELECT id, office_name FROM zones ORDER BY office_name')->fetchAll();
$all_circles = db()->query('SELECT id, office_name, zone_id FROM circles ORDER BY office_name')->fetchAll();
$all_divisions = db()->query('SELECT id, office_name, zone_id, circle_id FROM divisions ORDER BY office_name')->fetchAll();

$fy_list = get_fy_list();
$saved_filters = $_SESSION['board_filters'] ?? [];
$fy_selected_id = request_str('fy_id', $saved_filters['fy_id'] ?? '');
$fy_selected = null;
if ($fy_selected_id !== '') {
    foreach ($fy_list as $fy_row) {
        if ((int)$fy_row['id'] === (int)$fy_selected_id) {
            $fy_selected = $fy_row;
            break;
        }
    }
}
$fy = $fy_selected ?: $fy;

$zone_filter = request_str('zone_id', $saved_filters['zone_id'] ?? 'all');
$circle_filter = request_str('circle_id', $saved_filters['circle_id'] ?? 'all');
$division_filter = request_str('division_id', $saved_filters['division_id'] ?? 'all');

$zone_locked = false;
$circle_locked = false;
if ($office_role === 2 || $office_role === 3 || ($office_role === 1 && $office_type === 1)) {
    // full access
} elseif ($office_role === 1 && $office_type === 2 && !empty($user['zone_id'])) {
    $zone_filter = (string)$user['zone_id'];
    $zone_locked = true;
} elseif ($office_role === 1 && $office_type === 3 && !empty($user['circle_id'])) {
    $circle_filter = (string)$user['circle_id'];
    $circle_locked = true;
    foreach ($all_circles as $circle) {
        if ((int)$circle['id'] === (int)$circle_filter) {
            $zone_filter = (string)$circle['zone_id'];
            $zone_locked = true;
            break;
        }
    }
}

$_SESSION['board_filters'] = [
    'fy_id' => $fy_selected_id,
    'zone_id' => $zone_filter,
    'circle_id' => $circle_filter,
    'division_id' => $division_filter,
];

$circle_by_id = [];
foreach ($all_circles as $circle) {
    $circle_by_id[$circle['id']] = $circle;
}
$division_by_id = [];
foreach ($all_divisions as $division) {
    $division_by_id[$division['id']] = $division;
}

$allowed_divisions = [];
if ($office_role === 2 || $office_role === 3 || ($office_role === 1 && $office_type === 1)) {
    $allowed_divisions = $all_divisions;
} else {
    $allowed_divisions = get_divisions_for_user($user);
}
$allowed_division_ids = array_map(fn($d) => (int)$d['id'], $allowed_divisions);

if ($division_filter !== 'all' && !in_array((int)$division_filter, $allowed_division_ids, true)) {
    $division_filter = 'all';
}

if ($division_filter !== 'all' && isset($division_by_id[(int)$division_filter])) {
    $division = $division_by_id[(int)$division_filter];
    $circle_filter = (string)($division['circle_id'] ?? 'all');
    $zone_filter = (string)($division['zone_id'] ?? 'all');
}

if ($circle_filter !== 'all' && isset($circle_by_id[(int)$circle_filter])) {
    $zone_filter = (string)($circle_by_id[(int)$circle_filter]['zone_id'] ?? 'all');
}

if ($division_filter !== 'all') {
    $division_ids = [(int)$division_filter];
} elseif ($circle_filter !== 'all') {
    $division_ids = array_map(
        fn($d) => (int)$d['id'],
        array_filter($allowed_divisions, fn($d) => (int)($d['circle_id'] ?? 0) === (int)$circle_filter)
    );
} elseif ($zone_filter !== 'all') {
    $division_ids = array_map(
        fn($d) => (int)$d['id'],
        array_filter($allowed_divisions, fn($d) => (int)($d['zone_id'] ?? 0) === (int)$zone_filter)
    );
} else {
    $division_ids = $allowed_division_ids;
}

$latest_revenue = $fy ? get_latest_records('opr_repair', (int)$fy['id'], $division_ids) : [];
$latest_opr_other = $fy ? get_latest_records('opr_other', (int)$fy['id'], $division_ids) : [];
$latest_development = $fy ? get_latest_records('dev_pw', (int)$fy['id'], $division_ids) : [];
$latest_opr_other_min = $fy ? get_latest_records('opr_other_min', (int)$fy['id'], $division_ids) : [];
$latest_dev_other_min = $fy ? get_latest_records('dev_other_min', (int)$fy['id'], $division_ids) : [];
$latest_rev = $fy && is_division_user() ? get_latest_record_for_division('opr_repair', (int)$fy['id'], (int)$user['division_id']) : null;
$latest_opr_other_row = $fy && is_division_user() ? get_latest_record_for_division('opr_other', (int)$fy['id'], (int)$user['division_id']) : null;
$latest_dev = $fy && is_division_user() ? get_latest_record_for_division('dev_pw', (int)$fy['id'], (int)$user['division_id']) : null;
$latest_opr_other_min_row = $fy && is_division_user() ? get_latest_record_for_division('opr_other_min', (int)$fy['id'], (int)$user['division_id']) : null;
$latest_dev_other_min_row = $fy && is_division_user() ? get_latest_record_for_division('dev_other_min', (int)$fy['id'], (int)$user['division_id']) : null;
$month_options = $fy ? fy_month_options($fy['fiscal_years']) : [];
$default_month = $fy ? current_month_val_for_fy($fy['fiscal_years']) : 1;
$info = get_info_row();
$last_update_days = null;
if (is_division_user()) {
    $dates = [];
    if (!empty($latest_rev['created_at'])) {
        $dates[] = $latest_rev['created_at'];
    }
    if (!empty($latest_dev['created_at'])) {
        $dates[] = $latest_dev['created_at'];
    }
    if ($dates) {
        $latest_date = max($dates);
        $diff = (new DateTime($latest_date))->diff(new DateTime($today));
        $last_update_days = (int)$diff->format('%a');
    }
}
?>
<section class="card">
    <h2 class="center"><?= e((string)($info['site_name'] ?? 'APP Manager')); ?></h2>
    <div class="grid">
        <div><strong>Office:</strong> <?= e($office_name); ?></div>
        <div><strong>Email:</strong> <?= e($user['email_id'] ?? ''); ?></div>
        <div><strong>Date:</strong> <?= e($today); ?></div>
        <div><strong>Fiscal Year:</strong> <?= e($fy['fiscal_years'] ?? 'Not set'); ?></div>
        <?php if (is_division_user()): ?>
            <div><strong>Last Update:</strong> <?= $last_update_days !== null ? e((string)$last_update_days) . ' day(s) ago' : 'No data'; ?></div>
        <?php endif; ?>
    </div>
</section>

<?php if (!$is_division): ?>
    <section class="card">
        <h2>Filters</h2>
        <form method="get" action="index.php" id="board-filters" class="grid board-filters-grid">
            <input type="hidden" name="page" value="board">
            <label>Fiscal Year
                <select name="fy_id">
                    <?php foreach ($fy_list as $fy_row): ?>
                        <option value="<?= e((string)$fy_row['id']); ?>" <?= $fy && (int)$fy_row['id'] === (int)$fy['id'] ? 'selected' : ''; ?>>
                            <?= e($fy_row['fiscal_years']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Zone
                <select name="zone_id" <?= $zone_locked ? 'disabled' : ''; ?>>
                    <option value="all">All</option>
                    <?php foreach ($all_zones as $zone): ?>
                        <option value="<?= e((string)$zone['id']); ?>" <?= (string)$zone_filter === (string)$zone['id'] ? 'selected' : ''; ?>>
                            <?= e($zone['office_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($zone_locked): ?>
                    <input type="hidden" name="zone_id" value="<?= e((string)$zone_filter); ?>">
                <?php endif; ?>
            </label>
            <label>Circle
                <select name="circle_id" <?= $circle_locked ? 'disabled' : ''; ?>>
                    <option value="all">All</option>
                    <?php foreach ($all_circles as $circle): ?>
                        <?php
                            $show = $zone_filter === 'all' || (int)$circle['zone_id'] === (int)$zone_filter;
                        ?>
                        <?php if ($show): ?>
                            <option value="<?= e((string)$circle['id']); ?>" data-zone="<?= e((string)$circle['zone_id']); ?>" <?= (string)$circle_filter === (string)$circle['id'] ? 'selected' : ''; ?>>
                                <?= e($circle['office_name']); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <?php if ($circle_locked): ?>
                    <input type="hidden" name="circle_id" value="<?= e((string)$circle_filter); ?>">
                <?php endif; ?>
            </label>
            <label>Division
                <select name="division_id">
                    <option value="all">All</option>
                    <?php foreach ($all_divisions as $division): ?>
                        <?php
                            $zone_match = $zone_filter === 'all' || (int)$division['zone_id'] === (int)$zone_filter;
                            $circle_match = $circle_filter === 'all' || (int)$division['circle_id'] === (int)$circle_filter;
                            $allowed = true;
                            if (!empty($division_ids)) {
                                $allowed = in_array((int)$division['id'], $division_ids, true);
                            }
                        ?>
                        <?php if ($zone_match && $circle_match && $allowed): ?>
                            <option value="<?= e((string)$division['id']); ?>" data-zone="<?= e((string)($division['zone_id'] ?? '')); ?>" data-circle="<?= e((string)($division['circle_id'] ?? '')); ?>" <?= (string)$division_filter === (string)$division['id'] ? 'selected' : ''; ?>>
                                <?= e($division['office_name']); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>
    </section>
<?php endif; ?>

<section class="card card-plain">
    <h2 class="center">Entries For MoHPW only</h2>
</section>

<section class="board-grid">
    <?php
        $render_card = function (string $title, string $table, string $edit_modal, string $download_modal, string $info_modal, array $rows) use ($today) {
            $path = __DIR__ . '/_board_card.php';
            include $path;
        };
    ?>
    <?php $render_card('Operational Budget (Repair Works)', 'opr_repair', 'revenue-modal', 'revenue-download-modal', 'info-opr-repair', $latest_revenue); ?>
    <?php $render_card('Operational Budget (Other than Repair)', 'opr_other', 'opr-other-modal', 'opr-other-download-modal', 'info-opr-other', $latest_opr_other); ?>
    <?php $render_card('Development Budget (MoHPW)', 'dev_pw', 'development-modal', 'development-download-modal', 'info-dev-pw', $latest_development); ?>
    <div class="card card-plain section-heading">
        <h2 class="center">Entries for Ministries Other than MoHPW</h2>
    </div>
    <?php $render_card('Operational Budget (Other Ministry)', 'opr_other_min', 'opr-other-min-modal', 'opr-other-min-download-modal', 'info-opr-min', $latest_opr_other_min); ?>
    <?php $render_card('Development Budget (Other Ministry)', 'dev_other_min', 'dev-other-min-modal', 'dev-other-min-download-modal', 'info-dev-min', $latest_dev_other_min); ?>
</section>

<?php if (is_division_user()): ?>
    <div class="modal-backdrop" id="revenue-modal" aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="revenue-title">
            <h3 id="revenue-title">Revenue Budget Packages Information</h3>
            <p class="modal-sub">Date: <?= e($today); ?> | FY: <?= e($fy['fiscal_years'] ?? 'Not set'); ?></p>
            <form method="post" action="index.php" class="grid">
                <?= csrf_input(); ?>
                <input type="hidden" name="action" value="add_record">
                <input type="hidden" name="table" value="opr_repair">
                <label>Total no. of packages
                    <input type="number" name="pkg" value="<?= e((string)($latest_rev['pkg'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Month
                    <select name="month_val" required>
                        <?php foreach ($month_options as $opt): ?>
                            <?php
                                $selected = $latest_rev && !empty($latest_rev['month_val'])
                                    ? (int)$latest_rev['month_val'] === (int)$opt['value']
                                    : (int)$opt['value'] === (int)$default_month;
                                $disabled = (int)$opt['value'] > (int)$default_month;
                            ?>
                            <option value="<?= e((string)$opt['value']); ?>" <?= $selected ? 'selected' : ''; ?> <?= $disabled ? 'disabled' : ''; ?>>
                                <?= e($opt['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Total Value of packages in Lakh Tk.
                    <input type="number" name="est" step="0.01" value="<?= e((string)($latest_rev['est'] ?? 0)); ?>" min="0" required>
                </label>
                <label>In live (No.)
                    <input type="number" name="pkg_live" value="<?= e((string)($latest_rev['pkg_live'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Evaluation/Appr.(No.)
                    <input type="number" name="pkg_eval" value="<?= e((string)($latest_rev['pkg_eval'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Contract Awarded (No.)
                    <input type="number" name="pkg_cont" value="<?= e((string)($latest_rev['pkg_cont'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Value of awarded contracts in Lakh Tk.
                    <input type="number" name="cont" step="0.01" value="<?= e((string)($latest_rev['cont'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Note / Remarks
                    <textarea name="note" rows="2"><?= e((string)($latest_rev['note'] ?? '')); ?></textarea>
                </label>
                <div class="modal-actions">
                    <button type="submit">Save</button>
                    <button type="button" class="modal-close" data-close="revenue-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-backdrop" id="development-modal" aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="development-title">
            <h3 id="development-title">Development Budget Packages Information</h3>
            <p class="modal-sub">Date: <?= e($today); ?> | FY: <?= e($fy['fiscal_years'] ?? 'Not set'); ?></p>
            <form method="post" action="index.php" class="grid">
                <?= csrf_input(); ?>
                <input type="hidden" name="action" value="add_record">
                <input type="hidden" name="table" value="dev_pw">
                <label>Total no. of packages
                    <input type="number" name="pkg" value="<?= e((string)($latest_dev['pkg'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Month
                    <select name="month_val" required>
                        <?php foreach ($month_options as $opt): ?>
                            <?php
                                $selected = $latest_dev && !empty($latest_dev['month_val'])
                                    ? (int)$latest_dev['month_val'] === (int)$opt['value']
                                    : (int)$opt['value'] === (int)$default_month;
                                $disabled = (int)$opt['value'] > (int)$default_month;
                            ?>
                            <option value="<?= e((string)$opt['value']); ?>" <?= $selected ? 'selected' : ''; ?> <?= $disabled ? 'disabled' : ''; ?>>
                                <?= e($opt['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Total Value of packages in Lakh Tk.
                    <input type="number" name="est" step="0.01" value="<?= e((string)($latest_dev['est'] ?? 0)); ?>" min="0" required>
                </label>
                <label>In live (No.)
                    <input type="number" name="pkg_live" value="<?= e((string)($latest_dev['pkg_live'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Evaluation/Appr.(No.)
                    <input type="number" name="pkg_eval" value="<?= e((string)($latest_dev['pkg_eval'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Contract Awarded (No.)
                    <input type="number" name="pkg_cont" value="<?= e((string)($latest_dev['pkg_cont'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Value of awarded contracts in Lakh Tk.
                    <input type="number" name="cont" step="0.01" value="<?= e((string)($latest_dev['cont'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Note / Remarks
                    <textarea name="note" rows="2"><?= e((string)($latest_dev['note'] ?? '')); ?></textarea>
                </label>
                <div class="modal-actions">
                    <button type="submit">Save</button>
                    <button type="button" class="modal-close" data-close="development-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-backdrop" id="opr-other-modal" aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="opr-other-title">
            <h3 id="opr-other-title">Operational Budget (Other than Repair)</h3>
            <p class="modal-sub">Date: <?= e($today); ?> | FY: <?= e($fy['fiscal_years'] ?? 'Not set'); ?></p>
            <form method="post" action="index.php" class="grid">
                <?= csrf_input(); ?>
                <input type="hidden" name="action" value="add_record">
                <input type="hidden" name="table" value="opr_other">
                <label>Total no. of packages
                    <input type="number" name="pkg" value="<?= e((string)($latest_opr_other_row['pkg'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Month
                    <select name="month_val" required>
                        <?php foreach ($month_options as $opt): ?>
                            <?php
                                $selected = $latest_opr_other_row && !empty($latest_opr_other_row['month_val'])
                                    ? (int)$latest_opr_other_row['month_val'] === (int)$opt['value']
                                    : (int)$opt['value'] === (int)$default_month;
                                $disabled = (int)$opt['value'] > (int)$default_month;
                            ?>
                            <option value="<?= e((string)$opt['value']); ?>" <?= $selected ? 'selected' : ''; ?> <?= $disabled ? 'disabled' : ''; ?>>
                                <?= e($opt['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Total Value of packages in Lakh Tk.
                    <input type="number" name="est" step="0.01" value="<?= e((string)($latest_opr_other_row['est'] ?? 0)); ?>" min="0" required>
                </label>
                <label>In live (No.)
                    <input type="number" name="pkg_live" value="<?= e((string)($latest_opr_other_row['pkg_live'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Evaluation/Appr.(No.)
                    <input type="number" name="pkg_eval" value="<?= e((string)($latest_opr_other_row['pkg_eval'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Contract Awarded (No.)
                    <input type="number" name="pkg_cont" value="<?= e((string)($latest_opr_other_row['pkg_cont'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Value of awarded contracts in Lakh Tk.
                    <input type="number" name="cont" step="0.01" value="<?= e((string)($latest_opr_other_row['cont'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Note / Remarks
                    <textarea name="note" rows="2"><?= e((string)($latest_opr_other_row['note'] ?? '')); ?></textarea>
                </label>
                <div class="modal-actions">
                    <button type="submit">Save</button>
                    <button type="button" class="modal-close" data-close="opr-other-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-backdrop" id="opr-other-min-modal" aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="opr-other-min-title">
            <h3 id="opr-other-min-title">Operational Budget (Other Ministry)</h3>
            <p class="modal-sub">Date: <?= e($today); ?> | FY: <?= e($fy['fiscal_years'] ?? 'Not set'); ?></p>
            <form method="post" action="index.php" class="grid">
                <?= csrf_input(); ?>
                <input type="hidden" name="action" value="add_record">
                <input type="hidden" name="table" value="opr_other_min">
                <label>Total no. of packages
                    <input type="number" name="pkg" value="<?= e((string)($latest_opr_other_min_row['pkg'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Month
                    <select name="month_val" required>
                        <?php foreach ($month_options as $opt): ?>
                            <?php
                                $selected = $latest_opr_other_min_row && !empty($latest_opr_other_min_row['month_val'])
                                    ? (int)$latest_opr_other_min_row['month_val'] === (int)$opt['value']
                                    : (int)$opt['value'] === (int)$default_month;
                                $disabled = (int)$opt['value'] > (int)$default_month;
                            ?>
                            <option value="<?= e((string)$opt['value']); ?>" <?= $selected ? 'selected' : ''; ?> <?= $disabled ? 'disabled' : ''; ?>>
                                <?= e($opt['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Total Value of packages in Lakh Tk.
                    <input type="number" name="est" step="0.01" value="<?= e((string)($latest_opr_other_min_row['est'] ?? 0)); ?>" min="0" required>
                </label>
                <label>In live (No.)
                    <input type="number" name="pkg_live" value="<?= e((string)($latest_opr_other_min_row['pkg_live'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Evaluation/Appr.(No.)
                    <input type="number" name="pkg_eval" value="<?= e((string)($latest_opr_other_min_row['pkg_eval'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Contract Awarded (No.)
                    <input type="number" name="pkg_cont" value="<?= e((string)($latest_opr_other_min_row['pkg_cont'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Value of awarded contracts in Lakh Tk.
                    <input type="number" name="cont" step="0.01" value="<?= e((string)($latest_opr_other_min_row['cont'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Note / Remarks
                    <textarea name="note" rows="2"><?= e((string)($latest_opr_other_min_row['note'] ?? '')); ?></textarea>
                </label>
                <div class="modal-actions">
                    <button type="submit">Save</button>
                    <button type="button" class="modal-close" data-close="opr-other-min-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-backdrop" id="dev-other-min-modal" aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="dev-other-min-title">
            <h3 id="dev-other-min-title">Development Budget (Other Ministry)</h3>
            <p class="modal-sub">Date: <?= e($today); ?> | FY: <?= e($fy['fiscal_years'] ?? 'Not set'); ?></p>
            <form method="post" action="index.php" class="grid">
                <?= csrf_input(); ?>
                <input type="hidden" name="action" value="add_record">
                <input type="hidden" name="table" value="dev_other_min">
                <label>Total no. of packages
                    <input type="number" name="pkg" value="<?= e((string)($latest_dev_other_min_row['pkg'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Month
                    <select name="month_val" required>
                        <?php foreach ($month_options as $opt): ?>
                            <?php
                                $selected = $latest_dev_other_min_row && !empty($latest_dev_other_min_row['month_val'])
                                    ? (int)$latest_dev_other_min_row['month_val'] === (int)$opt['value']
                                    : (int)$opt['value'] === (int)$default_month;
                                $disabled = (int)$opt['value'] > (int)$default_month;
                            ?>
                            <option value="<?= e((string)$opt['value']); ?>" <?= $selected ? 'selected' : ''; ?> <?= $disabled ? 'disabled' : ''; ?>>
                                <?= e($opt['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Total Value of packages in Lakh Tk.
                    <input type="number" name="est" step="0.01" value="<?= e((string)($latest_dev_other_min_row['est'] ?? 0)); ?>" min="0" required>
                </label>
                <label>In live (No.)
                    <input type="number" name="pkg_live" value="<?= e((string)($latest_dev_other_min_row['pkg_live'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Evaluation/Appr.(No.)
                    <input type="number" name="pkg_eval" value="<?= e((string)($latest_dev_other_min_row['pkg_eval'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Contract Awarded (No.)
                    <input type="number" name="pkg_cont" value="<?= e((string)($latest_dev_other_min_row['pkg_cont'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Value of awarded contracts in Lakh Tk.
                    <input type="number" name="cont" step="0.01" value="<?= e((string)($latest_dev_other_min_row['cont'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Note / Remarks
                    <textarea name="note" rows="2"><?= e((string)($latest_dev_other_min_row['note'] ?? '')); ?></textarea>
                </label>
                <div class="modal-actions">
                    <button type="submit">Save</button>
                    <button type="button" class="modal-close" data-close="dev-other-min-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="modal-backdrop" id="revenue-download-modal" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="revenue-download-title">
        <h3 id="revenue-download-title">Download Revenue Budget</h3>
        <form method="get" action="export_board.php" class="grid">
            <input type="hidden" name="table" value="opr_repair">
            <label>Format
                <select name="format">
                    <option value="pdf">PDF</option>
                    <option value="excel">Excel</option>
                </select>
            </label>
            <label>Scope
                <select name="scope">
                    <option value="latest">Latest Only</option>
                    <option value="full">Full Data</option>
                </select>
            </label>
            <div class="modal-actions">
                <button type="submit">Download</button>
                <button type="button" class="modal-close" data-close="revenue-download-modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="development-download-modal" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="development-download-title">
        <h3 id="development-download-title">Download Development Budget</h3>
        <form method="get" action="export_board.php" class="grid">
            <input type="hidden" name="table" value="dev_pw">
            <label>Format
                <select name="format">
                    <option value="pdf">PDF</option>
                    <option value="excel">Excel</option>
                </select>
            </label>
            <label>Scope
                <select name="scope">
                    <option value="latest">Latest Only</option>
                    <option value="full">Full Data</option>
                </select>
            </label>
            <div class="modal-actions">
                <button type="submit">Download</button>
                <button type="button" class="modal-close" data-close="development-download-modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="opr-other-download-modal" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="opr-other-download-title">
        <h3 id="opr-other-download-title">Download Operational Budget (Other than Repair)</h3>
        <form method="get" action="export_board.php" class="grid">
            <input type="hidden" name="table" value="opr_other">
            <label>Format
                <select name="format">
                    <option value="pdf">PDF</option>
                    <option value="excel">Excel</option>
                </select>
            </label>
            <label>Scope
                <select name="scope">
                    <option value="latest">Latest Only</option>
                    <option value="full">Full Data</option>
                </select>
            </label>
            <div class="modal-actions">
                <button type="submit">Download</button>
                <button type="button" class="modal-close" data-close="opr-other-download-modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="opr-other-min-download-modal" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="opr-other-min-download-title">
        <h3 id="opr-other-min-download-title">Download Operational Budget (Other Ministry)</h3>
        <form method="get" action="export_board.php" class="grid">
            <input type="hidden" name="table" value="opr_other_min">
            <label>Format
                <select name="format">
                    <option value="pdf">PDF</option>
                    <option value="excel">Excel</option>
                </select>
            </label>
            <label>Scope
                <select name="scope">
                    <option value="latest">Latest Only</option>
                    <option value="full">Full Data</option>
                </select>
            </label>
            <div class="modal-actions">
                <button type="submit">Download</button>
                <button type="button" class="modal-close" data-close="opr-other-min-download-modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="dev-other-min-download-modal" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="dev-other-min-download-title">
        <h3 id="dev-other-min-download-title">Download Development Budget (Other Ministry)</h3>
        <form method="get" action="export_board.php" class="grid">
            <input type="hidden" name="table" value="dev_other_min">
            <label>Format
                <select name="format">
                    <option value="pdf">PDF</option>
                    <option value="excel">Excel</option>
                </select>
            </label>
            <label>Scope
                <select name="scope">
                    <option value="latest">Latest Only</option>
                    <option value="full">Full Data</option>
                </select>
            </label>
            <div class="modal-actions">
                <button type="submit">Download</button>
                <button type="button" class="modal-close" data-close="dev-other-min-download-modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="graph-modal" aria-hidden="true" data-division-id="<?= is_division_user() ? e((string)$user['division_id']) : ''; ?>">
    <div class="modal-card modal-wide" role="dialog" aria-modal="true" aria-labelledby="graph-title">
        <div class="modal-head">
            <h3 id="graph-title">Budget Graph</h3>
            <button type="button" class="icon-link" id="graph-download" title="Download JPEG" aria-label="Download JPEG">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 4v10"></path>
                    <path d="M8 10l4 4 4-4"></path>
                    <path d="M4 20h16"></path>
                </svg>
            </button>
        </div>
        <div class="modal-filters">
            <label>Fiscal Year
                <select id="graph-fy">
                    <?php foreach ($fy_list as $fy_row): ?>
                        <option value="<?= e((string)$fy_row['id']); ?>" <?= $fy && (int)$fy_row['id'] === (int)$fy['id'] ? 'selected' : ''; ?>>
                            <?= e($fy_row['fiscal_years']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php if (!is_division_user()): ?>
                <label>Office Name
                    <select id="graph-division">
                        <option value="all">All</option>
                        <?php foreach ($division_list as $div): ?>
                            <option value="<?= e((string)$div['id']); ?>"><?= e($div['office_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>
        </div>
        <div class="metric-buttons">
            <button type="button" data-metric="pkg">Total no. of packages</button>
            <button type="button" data-metric="est">Total Value</button>
            <button type="button" data-metric="pkg_live">In live</button>
            <button type="button" data-metric="pkg_eval">Evaluation/Appr.</button>
            <button type="button" data-metric="pkg_cont">Contract Awarded</button>
            <button type="button" data-metric="cont">Contract Value</button>
        </div>
        <div class="graph-wrap">
            <div class="graph-meta" id="graph-meta">
                <div>Office: <span id="graph-office-name"><?= e($office_name); ?></span></div>
                <div>Division: <span id="graph-division-name"><?= is_division_user() ? e($office_name) : 'All'; ?></span></div>
                <div>Metric: <span id="graph-metric-name">Total no. of packages</span></div>
                <div>Date: <span id="graph-date"><?= e($today); ?></span></div>
            </div>
            <canvas id="board-chart" height="140"></canvas>
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-close" data-close="graph-modal">Close</button>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="info-opr-repair" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="info-opr-repair-title">
        <h3 id="info-opr-repair-title">Operational Budget (Repair Works)</h3>
        <p><?= e((string)($info['i_opr_repair'] ?? 'No message')); ?></p>
        <div class="modal-actions">
            <button type="button" class="modal-close" data-close="info-opr-repair">Close</button>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="info-opr-other" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="info-opr-other-title">
        <h3 id="info-opr-other-title">Operational Budget (Other than Repair)</h3>
        <p><?= e((string)($info['i_opr_other'] ?? 'No message')); ?></p>
        <div class="modal-actions">
            <button type="button" class="modal-close" data-close="info-opr-other">Close</button>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="info-dev-pw" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="info-dev-pw-title">
        <h3 id="info-dev-pw-title">Development Budget (MoHPW)</h3>
        <p><?= e((string)($info['i_dev_pw'] ?? 'No message')); ?></p>
        <div class="modal-actions">
            <button type="button" class="modal-close" data-close="info-dev-pw">Close</button>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="info-opr-min" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="info-opr-min-title">
        <h3 id="info-opr-min-title">Operational Budget (Other Ministry)</h3>
        <p><?= e((string)($info['i_opr_min'] ?? 'No message')); ?></p>
        <div class="modal-actions">
            <button type="button" class="modal-close" data-close="info-opr-min">Close</button>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="info-dev-min" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="info-dev-min-title">
        <h3 id="info-dev-min-title">Development Budget (Other Ministry)</h3>
        <p><?= e((string)($info['i_dev_min'] ?? 'No message')); ?></p>
        <div class="modal-actions">
            <button type="button" class="modal-close" data-close="info-dev-min">Close</button>
        </div>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
