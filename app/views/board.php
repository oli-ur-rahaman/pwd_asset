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
$reset_filters = request_str('reset', '') === '1';
if ($reset_filters) {
    unset($_SESSION['board_filters']);
}
$saved_filters = $reset_filters ? [] : ($_SESSION['board_filters'] ?? []);
$fy_selected_id = request_str('fy_id', $saved_filters['fy_id'] ?? '');
$budget_type_filter = request_str('budget_type', $saved_filters['budget_type'] ?? 'all');
$ministry_filter = request_str('ministry_id', $saved_filters['ministry_id'] ?? 'all');
$view_mode = request_str('view_mode', $saved_filters['view_mode'] ?? 'ministry');
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
    'budget_type' => $budget_type_filter,
    'ministry_id' => $ministry_filter,
    'view_mode' => $view_mode,
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
$apply_active_filter = $office_role === 2 || $office_role === 3 || ($office_role === 1 && ($office_type === 2 || $office_type === 3));
if ($apply_active_filter) {
    $active_division_ids = db()->query('SELECT DISTINCT division_id FROM users WHERE active_status = 1 AND division_id IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN);
    $active_division_ids = array_map('intval', $active_division_ids);
    $allowed_divisions = array_values(array_filter(
        $allowed_divisions,
        fn($d) => in_array((int)($d['id'] ?? 0), $active_division_ids, true)
    ));
}
$allowed_division_ids = array_map(fn($d) => (int)$d['id'], $allowed_divisions);
$graph_divisions = array_values(array_filter(
    $all_divisions,
    fn($d) => in_array((int)$d['id'], $allowed_division_ids, true)
));

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
$filtered_divisions = array_values(array_filter(
    $allowed_divisions,
    fn($d) => in_array((int)$d['id'], $division_ids, true)
));

$opr_ministries = get_ministries_for_budget('opr');
$dev_ministries = get_ministries_for_budget('dev');
$default_opr_ministries = get_ministries_for_budget('opr', true);
$default_dev_ministries = get_ministries_for_budget('dev', true);
$default_opr_ministry_id = $default_opr_ministries[0]['id'] ?? ($opr_ministries[0]['id'] ?? null);
$default_dev_ministry_id = $default_dev_ministries[0]['id'] ?? ($dev_ministries[0]['id'] ?? null);
$default_opr_ministry_ids = array_map(fn($m) => (int)$m['id'], $default_opr_ministries);
$default_dev_ministry_ids = array_map(fn($m) => (int)$m['id'], $default_dev_ministries);
$default_opr_ministry_name = '';
$default_dev_ministry_name = '';
foreach ($opr_ministries as $ministry) {
    if ((int)$ministry['id'] === (int)$default_opr_ministry_id) {
        $default_opr_ministry_name = $ministry['name'] ?? '';
        break;
    }
}
foreach ($dev_ministries as $ministry) {
    if ((int)$ministry['id'] === (int)$default_dev_ministry_id) {
        $default_dev_ministry_name = $ministry['name'] ?? '';
        break;
    }
}
$filter_ministry_map = [];
$filter_division_ids = $division_ids;
if ($fy) {
    $params = [$fy['id']];
    $in = $filter_division_ids ? implode(',', array_fill(0, count($filter_division_ids), '?')) : '';
    if ($filter_division_ids) {
        $params = array_merge($params, $filter_division_ids);
    }
    $opr_sql = 'SELECT DISTINCT m.id, m.name FROM operational o JOIN ministries m ON m.id = o.ministry_id WHERE o.fy_id = ?';
    $dev_sql = 'SELECT DISTINCT m.id, m.name FROM development d JOIN ministries m ON m.id = d.ministry_id WHERE d.fy_id = ?';
    if ($filter_division_ids) {
        $opr_sql .= " AND o.division_id IN ({$in})";
        $dev_sql .= " AND d.division_id IN ({$in})";
    }
    $stmt = db()->prepare($opr_sql);
    $stmt->execute($params);
    foreach ($stmt->fetchAll() as $row) {
        $filter_ministry_map[(int)$row['id']] = $row['name'];
    }
    $stmt = db()->prepare($dev_sql);
    $stmt->execute($params);
    foreach ($stmt->fetchAll() as $row) {
        $filter_ministry_map[(int)$row['id']] = $row['name'];
    }
    foreach ($default_opr_ministries as $row) {
        $filter_ministry_map[(int)$row['id']] = $row['name'];
    }
    foreach ($default_dev_ministries as $row) {
        $filter_ministry_map[(int)$row['id']] = $row['name'];
    }
}
asort($filter_ministry_map);
$present_opr_ids = [];
$present_dev_ids = [];
if (is_division_user() && (int)($user['office_type'] ?? 0) === 4 && $fy) {
    $stmt = db()->prepare('SELECT DISTINCT ministry_id FROM operational WHERE division_id = ? AND fy_id = ?');
    $stmt->execute([(int)$user['division_id'], (int)$fy['id']]);
    $present_opr_ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    $stmt = db()->prepare('SELECT DISTINCT ministry_id FROM development WHERE division_id = ? AND fy_id = ?');
    $stmt->execute([(int)$user['division_id'], (int)$fy['id']]);
    $present_dev_ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

$latest_operational = $fy ? get_latest_records_with_ministry('operational', (int)$fy['id'], $division_ids) : [];
$latest_development = $fy ? get_latest_records_with_ministry('development', (int)$fy['id'], $division_ids) : [];

if ($default_opr_ministries) {
    $latest_operational = merge_default_ministry_rows($latest_operational, $filtered_divisions, $default_opr_ministries);
}
if ($default_dev_ministries) {
    $latest_development = merge_default_ministry_rows($latest_development, $filtered_divisions, $default_dev_ministries);
}

$latest_operational_row = ($fy && is_division_user() && $default_opr_ministry_id)
    ? get_latest_record_for_division_ministry('operational', (int)$fy['id'], (int)$user['division_id'], (int)$default_opr_ministry_id)
    : null;
$latest_development_row = ($fy && is_division_user() && $default_dev_ministry_id)
    ? get_latest_record_for_division_ministry('development', (int)$fy['id'], (int)$user['division_id'], (int)$default_dev_ministry_id)
    : null;
$month_options = $fy ? fy_month_options($fy['fiscal_years']) : [];
$default_month = $fy ? current_month_val_for_fy($fy['fiscal_years']) : 1;
$info = get_info_row();
$last_update_days = null;
if (is_division_user()) {
    $dates = [];
    if (!empty($latest_operational_row['created_at'])) {
        $dates[] = $latest_operational_row['created_at'];
    }
    if (!empty($latest_development_row['created_at'])) {
        $dates[] = $latest_development_row['created_at'];
    }
    if ($dates) {
        $latest_date = max($dates);
        $diff = (new DateTime($latest_date))->diff(new DateTime($today));
        $last_update_days = (int)$diff->format('%a');
    }
}

$graph_ministries = [];
$graph_opr_ids = [];
$graph_dev_ids = [];
if ($fy) {
    $stmt = db()->prepare('SELECT DISTINCT m.id, m.name FROM ministries m JOIN operational o ON o.ministry_id = m.id WHERE o.fy_id = ?');
    $stmt->execute([(int)$fy['id']]);
    $graph_ministries = $stmt->fetchAll();
    foreach ($graph_ministries as $row) {
        $graph_opr_ids[(int)$row['id']] = true;
    }
    $stmt = db()->prepare('SELECT DISTINCT m.id, m.name FROM ministries m JOIN development d ON d.ministry_id = m.id WHERE d.fy_id = ?');
    $stmt->execute([(int)$fy['id']]);
    $dev_min_list = $stmt->fetchAll();

    $seen = [];
    foreach ($graph_ministries as $row) {
        $seen[(int)$row['id']] = $row['name'];
    }
    foreach ($dev_min_list as $row) {
        $seen[(int)$row['id']] = $row['name'];
    }
    foreach ($default_opr_ministries as $row) {
        $seen[(int)$row['id']] = $row['name'];
        $graph_opr_ids[(int)$row['id']] = true;
    }
    foreach ($default_dev_ministries as $row) {
        $seen[(int)$row['id']] = $row['name'];
        $graph_dev_ids[(int)$row['id']] = true;
    }

    $graph_ministries = [];
    foreach ($seen as $id => $name) {
        $graph_ministries[] = ['id' => $id, 'name' => $name];
    }
    usort($graph_ministries, fn($a, $b) => strcmp($a['name'], $b['name']));
}

$render_card = function (string $title, string $table, string $edit_modal, string $download_modal, string $info_modal, array $rows, bool $show_ministry_col = false, array $default_ministry_ids = [], ?bool $show_division_col = null, array $card_meta = []) use ($today) {
    $path = __DIR__ . '/_board_card.php';
    include $path;
};
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
            <input type="hidden" name="view_mode" id="view-mode-input" value="<?= e($view_mode); ?>">
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
            <label>Budget Type
                <select name="budget_type">
                    <option value="all" <?= $budget_type_filter === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="operational" <?= $budget_type_filter === 'operational' ? 'selected' : ''; ?>>Operational</option>
                    <option value="development" <?= $budget_type_filter === 'development' ? 'selected' : ''; ?>>Development</option>
                </select>
            </label>
            <label>Ministry Name
                <select name="ministry_id">
                    <option value="all">All</option>
                    <?php foreach ($filter_ministry_map as $min_id => $min_name): ?>
                        <option value="<?= e((string)$min_id); ?>" <?= (string)$ministry_filter === (string)$min_id ? 'selected' : ''; ?>>
                            <?= e($min_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>
        <div class="view-toggle-row">
            <div class="view-toggle">
                <button type="button" class="toggle-btn <?= $view_mode === 'ministry' ? 'active' : ''; ?>" data-view="ministry">Ministry-wise</button>
                <button type="button" class="toggle-btn <?= $view_mode === 'division' ? 'active' : ''; ?>" data-view="division">Division-wise</button>
            </div>
            <div class="view-toggle-actions">
                <button type="button" class="icon-link" id="filters-reset" title="Reset Filters" aria-label="Reset Filters">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M21 12a9 9 0 1 1-2.64-6.36"></path>
                        <path d="M21 3v6h-6"></path>
                    </svg>
                </button>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php
    $show_ministry_cards = !$is_division
        && ($office_role === 2 || $office_role === 3 || ($office_role === 1 && ($office_type === 2 || $office_type === 3)));
?>

<?php if ($show_ministry_cards): ?>
    <?php
        $operational_by_ministry = [];
        foreach ($latest_operational as $row) {
            $mid = (int)($row['ministry_id'] ?? 0);
            if ($mid <= 0) {
                continue;
            }
            if (!isset($operational_by_ministry[$mid])) {
                $operational_by_ministry[$mid] = [];
            }
            $operational_by_ministry[$mid][] = $row;
        }
        $development_by_ministry = [];
        foreach ($latest_development as $row) {
            $mid = (int)($row['ministry_id'] ?? 0);
            if ($mid <= 0) {
                continue;
            }
            if (!isset($development_by_ministry[$mid])) {
                $development_by_ministry[$mid] = [];
            }
            $development_by_ministry[$mid][] = $row;
        }

        $opr_default_ids = $default_opr_ministry_ids;
        $dev_default_ids = $default_dev_ministry_ids;
        $opr_present_ids = array_keys($operational_by_ministry);
        $dev_present_ids = array_keys($development_by_ministry);

        $opr_extra_ids = array_values(array_diff($opr_present_ids, $opr_default_ids));
        usort($opr_extra_ids, function ($a, $b) use ($opr_ministries) {
            $name_a = '';
            $name_b = '';
            foreach ($opr_ministries as $m) {
                if ((int)$m['id'] === (int)$a) {
                    $name_a = $m['name'] ?? '';
                    break;
                }
            }
            foreach ($opr_ministries as $m) {
                if ((int)$m['id'] === (int)$b) {
                    $name_b = $m['name'] ?? '';
                    break;
                }
            }
            return strcmp($name_a, $name_b);
        });

        $dev_extra_ids = array_values(array_diff($dev_present_ids, $dev_default_ids));
        usort($dev_extra_ids, function ($a, $b) use ($dev_ministries) {
            $name_a = '';
            $name_b = '';
            foreach ($dev_ministries as $m) {
                if ((int)$m['id'] === (int)$a) {
                    $name_a = $m['name'] ?? '';
                    break;
                }
            }
            foreach ($dev_ministries as $m) {
                if ((int)$m['id'] === (int)$b) {
                    $name_b = $m['name'] ?? '';
                    break;
                }
            }
            return strcmp($name_a, $name_b);
        });

        $opr_order = array_values(array_unique(array_merge($opr_default_ids, $opr_extra_ids)));
        $dev_order = array_values(array_unique(array_merge($dev_default_ids, $dev_extra_ids)));

        $operational_by_division = [];
        foreach ($latest_operational as $row) {
            $div_id = (int)($row['division_id'] ?? 0);
            if ($div_id <= 0) {
                continue;
            }
            if (!isset($operational_by_division[$div_id])) {
                $operational_by_division[$div_id] = [];
            }
            $operational_by_division[$div_id][] = $row;
        }
        $development_by_division = [];
        foreach ($latest_development as $row) {
            $div_id = (int)($row['division_id'] ?? 0);
            if ($div_id <= 0) {
                continue;
            }
            if (!isset($development_by_division[$div_id])) {
                $development_by_division[$div_id] = [];
            }
            $development_by_division[$div_id][] = $row;
        }
    ?>

    <?php if ($budget_type_filter !== 'development' && $view_mode === 'ministry'): ?>
        <section class="card card-plain section-heading section-heading-tight" id="operational-heading">
            <h2 class="center">Operational Budget</h2>
        </section>
        <section class="board-grid">
            <?php foreach ($opr_order as $mid): ?>
                <?php if ($ministry_filter !== 'all' && (int)$ministry_filter !== (int)$mid) { continue; } ?>
                <?php
                    $min_name = $filter_ministry_map[$mid] ?? '';
                    $rows = $operational_by_ministry[$mid] ?? [];
                ?>
                <div class="operational-budget-card" data-table="operational" data-fy-id="<?= e((string)($fy['id'] ?? 0)); ?>">
                    <?php $render_card($min_name, 'operational', 'revenue-modal', 'revenue-download-modal', 'info-opr', $rows, false, [], null, [
                        'ministry_id' => $mid,
                        'ministry_name' => $min_name,
                        'view_mode' => $view_mode,
                    ]); ?>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if ($budget_type_filter !== 'operational' && $view_mode === 'ministry'): ?>
        <section class="card card-plain section-heading section-heading-tight" id="development-heading">
            <h2 class="center">Development Budget</h2>
        </section>
        <section class="board-grid">
            <?php foreach ($dev_order as $mid): ?>
                <?php if ($ministry_filter !== 'all' && (int)$ministry_filter !== (int)$mid) { continue; } ?>
                <?php
                    $min_name = $filter_ministry_map[$mid] ?? '';
                    $rows = $development_by_ministry[$mid] ?? [];
                ?>
                <div class="operational-budget-card" data-table="development" data-fy-id="<?= e((string)($fy['id'] ?? 0)); ?>">
                    <?php $render_card($min_name, 'development', 'development-modal', 'development-download-modal', 'info-dev', $rows, false, [], null, [
                        'ministry_id' => $mid,
                        'ministry_name' => $min_name,
                        'view_mode' => $view_mode,
                    ]); ?>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if ($budget_type_filter !== 'development' && $view_mode === 'division'): ?>
        <section class="card card-plain section-heading section-heading-tight" id="operational-heading">
            <h2 class="center">Operational Budget</h2>
        </section>
        <section class="board-grid">
            <?php foreach ($filtered_divisions as $division): ?>
                <?php
                    $div_id = (int)$division['id'];
                    $rows = $operational_by_division[$div_id] ?? [];
                    if ($ministry_filter !== 'all') {
                        $rows = array_values(array_filter($rows, fn($r) => (int)($r['ministry_id'] ?? 0) === (int)$ministry_filter));
                    }
                    if (!$rows) {
                        continue;
                    }
                    $rows = array_map(function ($row) {
                        $row['office_name'] = null;
                        return $row;
                    }, $rows);
                ?>
                <div class="operational-budget-card" data-table="operational" data-fy-id="<?= e((string)($fy['id'] ?? 0)); ?>">
                    <?php $render_card($division['office_name'], 'operational', 'revenue-modal', 'revenue-download-modal', 'info-opr', $rows, true, $default_opr_ministry_ids, false, [
                        'division_id' => $div_id,
                        'division_name' => $division['office_name'],
                        'view_mode' => $view_mode,
                    ]); ?>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if ($budget_type_filter !== 'operational' && $view_mode === 'division'): ?>
        <section class="card card-plain section-heading section-heading-tight" id="development-heading">
            <h2 class="center">Development Budget</h2>
        </section>
        <section class="board-grid">
            <?php foreach ($filtered_divisions as $division): ?>
                <?php
                    $div_id = (int)$division['id'];
                    $rows = $development_by_division[$div_id] ?? [];
                    if ($ministry_filter !== 'all') {
                        $rows = array_values(array_filter($rows, fn($r) => (int)($r['ministry_id'] ?? 0) === (int)$ministry_filter));
                    }
                    if (!$rows) {
                        continue;
                    }
                    $rows = array_map(function ($row) {
                        $row['office_name'] = null;
                        return $row;
                    }, $rows);
                ?>
                <div class="operational-budget-card" data-table="development" data-fy-id="<?= e((string)($fy['id'] ?? 0)); ?>">
                    <?php $render_card($division['office_name'], 'development', 'development-modal', 'development-download-modal', 'info-dev', $rows, true, $default_dev_ministry_ids, false, [
                        'division_id' => $div_id,
                        'division_name' => $division['office_name'],
                        'view_mode' => $view_mode,
                    ]); ?>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
<?php else: ?>
    <section class="card card-plain section-heading section-heading-tight" id="operational-heading">
        <h2 class="center">Operational Budget</h2>
    </section>
    <div class="operational-budget-card" data-table="operational" data-fy-id="<?= e((string)($fy['id'] ?? 0)); ?>">
    <?php $render_card('', 'operational', 'revenue-modal', 'revenue-download-modal', 'info-opr', $latest_operational, true, $default_opr_ministry_ids, null, [
        'view_mode' => $view_mode,
    ]); ?>
    </div>

    <section class="card card-plain section-heading section-heading-tight" id="development-heading">
        <h2 class="center">Development Budget</h2>
    </section>
    <div class="operational-budget-card" data-table="development" data-fy-id="<?= e((string)($fy['id'] ?? 0)); ?>">
    <?php $render_card('', 'development', 'development-modal', 'development-download-modal', 'info-dev', $latest_development, true, $default_dev_ministry_ids, null, [
        'view_mode' => $view_mode,
    ]); ?>
    </div>
<?php endif; ?>

<?php if (is_division_user() && (int)($user['office_type'] ?? 0) === 4): ?>
    <div class="modal-backdrop" id="operational-ministry-modal" aria-hidden="true">
        <div class="modal-card ministry-modal" role="dialog" aria-modal="true" aria-labelledby="operational-ministry-title">
            <h3 id="operational-ministry-title">Operational Budget</h3>
            <p class="modal-sub">Select Ministry/Type</p>
            <?php
                $opr_present = array_filter(
                    $opr_ministries,
                    fn($m) => (int)($m['is_default'] ?? 0) === 1 || in_array((int)$m['id'], $present_opr_ids, true)
                );
                $opr_available = array_filter(
                    $opr_ministries,
                    fn($m) => !((int)($m['is_default'] ?? 0) === 1 || in_array((int)$m['id'], $present_opr_ids, true))
                );
            ?>
            <div class="ministry-group">
                <div class="ministry-group-title">Already present in the table</div>
                <div class="ministry-list">
                    <?php foreach ($opr_present as $ministry): ?>
                        <button type="button" class="ministry-pill disabled" data-disabled="1" data-ministry-id="<?= e((string)$ministry['id']); ?>" data-ministry-name="<?= e($ministry['name']); ?>" data-table="operational">
                            <?= e($ministry['name']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="ministry-group">
                <div class="ministry-group-title">Add new ministries</div>
                <input type="text" class="ministry-search" data-target="operational" placeholder="Search ministries...">
                <div class="ministry-list">
                    <?php foreach ($opr_available as $ministry): ?>
                        <button type="button" class="ministry-pill" data-ministry-id="<?= e((string)$ministry['id']); ?>" data-ministry-name="<?= e($ministry['name']); ?>" data-table="operational">
                            <?= e($ministry['name']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="modal-close" data-close="operational-ministry-modal">Close</button>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" id="development-ministry-modal" aria-hidden="true">
        <div class="modal-card ministry-modal" role="dialog" aria-modal="true" aria-labelledby="development-ministry-title">
            <h3 id="development-ministry-title">Development Budget</h3>
            <p class="modal-sub">Select Ministry/Type</p>
            <?php
                $dev_present = array_filter(
                    $dev_ministries,
                    fn($m) => (int)($m['is_default'] ?? 0) === 1 || in_array((int)$m['id'], $present_dev_ids, true)
                );
                $dev_available = array_filter(
                    $dev_ministries,
                    fn($m) => !((int)($m['is_default'] ?? 0) === 1 || in_array((int)$m['id'], $present_dev_ids, true))
                );
            ?>
            <div class="ministry-group">
                <div class="ministry-group-title">Already present in the table</div>
                <div class="ministry-list">
                    <?php foreach ($dev_present as $ministry): ?>
                        <button type="button" class="ministry-pill disabled" data-disabled="1" data-ministry-id="<?= e((string)$ministry['id']); ?>" data-ministry-name="<?= e($ministry['name']); ?>" data-table="development">
                            <?= e($ministry['name']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="ministry-group">
                <div class="ministry-group-title">Add new ministries</div>
                <input type="text" class="ministry-search" data-target="development" placeholder="Search ministries...">
                <div class="ministry-list">
                    <?php foreach ($dev_available as $ministry): ?>
                        <button type="button" class="ministry-pill" data-ministry-id="<?= e((string)$ministry['id']); ?>" data-ministry-name="<?= e($ministry['name']); ?>" data-table="development">
                            <?= e($ministry['name']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="modal-close" data-close="development-ministry-modal">Close</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php /*
<section class="card card-plain">
    <h2 class="center">Entries For MoHPW only</h2>
</section>

<section class="board-grid">
    <?php $render_card('Operational Budget (Repair Works)', 'opr_repair', 'revenue-modal', 'revenue-download-modal', 'info-opr-repair', $latest_revenue); ?>
    <?php $render_card('Operational Budget (Other than Repair)', 'opr_other', 'opr-other-modal', 'opr-other-download-modal', 'info-opr-other', $latest_opr_other); ?>
    <?php $render_card('Development Budget (MoHPW)', 'dev_pw', 'development-modal', 'development-download-modal', 'info-dev-pw', $latest_development); ?>
    <div class="card card-plain section-heading">
        <h2 class="center">Entries for Ministries Other than MoHPW</h2>
    </div>
    <?php $render_card('Operational Budget (Other Ministry)', 'opr_other_min', 'opr-other-min-modal', 'opr-other-min-download-modal', 'info-opr-min', $latest_opr_other_min); ?>
    <?php $render_card('Development Budget (Other Ministry)', 'dev_other_min', 'dev-other-min-modal', 'dev-other-min-download-modal', 'info-dev-min', $latest_dev_other_min); ?>
</section>
*/ ?>

<?php if (is_division_user()): ?>
    <div class="modal-backdrop" id="revenue-modal" aria-hidden="true">
        <div class="modal-card budget-modal" role="dialog" aria-modal="true" aria-labelledby="revenue-title">
            <h3 id="revenue-title">Operational Budget</h3>
            <p class="modal-sub" id="operational-ministry-name"><?= e((string)$default_opr_ministry_name); ?></p>
            <form method="post" action="index.php" class="grid">
                <?= csrf_input(); ?>
                <input type="hidden" name="action" value="add_record">
                <input type="hidden" name="table" value="operational">
                <?php $opr_selected_ministry = (int)($latest_operational_row['ministry_id'] ?? ($default_opr_ministry_id ?? 0)); ?>
                <input type="hidden" name="ministry_id" value="<?= e((string)$opr_selected_ministry); ?>">
                <label>Total no. of packages
                    <input type="number" name="pkg" value="<?= e((string)($latest_operational_row['pkg'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Month
                    <select name="month_val" required>
                        <?php foreach ($month_options as $opt): ?>
                            <?php
                                $selected = $latest_operational_row && !empty($latest_operational_row['month_val'])
                                    ? (int)$latest_operational_row['month_val'] === (int)$opt['value']
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
                    <input type="number" name="est" step="0.01" value="<?= e((string)($latest_operational_row['est'] ?? 0)); ?>" min="0" required>
                </label>
                <label>In live (No.)
                    <input type="number" name="pkg_live" value="<?= e((string)($latest_operational_row['pkg_live'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Evaluation/Appr.(No.)
                    <input type="number" name="pkg_eval" value="<?= e((string)($latest_operational_row['pkg_eval'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Contract Awarded (No.)
                    <input type="number" name="pkg_cont" value="<?= e((string)($latest_operational_row['pkg_cont'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Value of awarded contracts in Lakh Tk.
                    <input type="number" name="cont" step="0.01" value="<?= e((string)($latest_operational_row['cont'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Note / Remarks
                    <textarea name="note" rows="2"><?= e((string)($latest_operational_row['note'] ?? '')); ?></textarea>
                </label>
                <div class="modal-actions">
                    <button type="submit">Save</button>
                    <button type="button" class="modal-close" data-close="revenue-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-backdrop" id="development-modal" aria-hidden="true">
        <div class="modal-card budget-modal" role="dialog" aria-modal="true" aria-labelledby="development-title">
            <h3 id="development-title">Development Budget</h3>
            <p class="modal-sub" id="development-ministry-name"><?= e((string)$default_dev_ministry_name); ?></p>
            <form method="post" action="index.php" class="grid">
                <?= csrf_input(); ?>
                <input type="hidden" name="action" value="add_record">
                <input type="hidden" name="table" value="development">
                <?php $dev_selected_ministry = (int)($latest_development_row['ministry_id'] ?? ($default_dev_ministry_id ?? 0)); ?>
                <input type="hidden" name="ministry_id" value="<?= e((string)$dev_selected_ministry); ?>">
                <label>Total no. of packages
                    <input type="number" name="pkg" value="<?= e((string)($latest_development_row['pkg'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Month
                    <select name="month_val" required>
                        <?php foreach ($month_options as $opt): ?>
                            <?php
                                $selected = $latest_development_row && !empty($latest_development_row['month_val'])
                                    ? (int)$latest_development_row['month_val'] === (int)$opt['value']
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
                    <input type="number" name="est" step="0.01" value="<?= e((string)($latest_development_row['est'] ?? 0)); ?>" min="0" required>
                </label>
                <label>In live (No.)
                    <input type="number" name="pkg_live" value="<?= e((string)($latest_development_row['pkg_live'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Evaluation/Appr.(No.)
                    <input type="number" name="pkg_eval" value="<?= e((string)($latest_development_row['pkg_eval'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Contract Awarded (No.)
                    <input type="number" name="pkg_cont" value="<?= e((string)($latest_development_row['pkg_cont'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Value of awarded contracts in Lakh Tk.
                    <input type="number" name="cont" step="0.01" value="<?= e((string)($latest_development_row['cont'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Note / Remarks
                    <textarea name="note" rows="2"><?= e((string)($latest_development_row['note'] ?? '')); ?></textarea>
                </label>
                <div class="modal-actions">
                    <button type="submit">Save</button>
                    <button type="button" class="modal-close" data-close="development-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <?php /*
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
    */ ?>
<?php endif; ?>

<div class="modal-backdrop" id="revenue-download-modal" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="revenue-download-title">
        <h3 id="revenue-download-title">Download Operational Budget</h3>
        <form method="get" action="export_board.php" class="grid">
            <input type="hidden" name="table" value="operational">
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
            <input type="hidden" name="table" value="development">
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

<div class="modal-backdrop" id="graph-modal" aria-hidden="true" data-division-id="<?= is_division_user() ? e((string)$user['division_id']) : ''; ?>" data-view-mode="<?= e($view_mode); ?>" data-zone-id="<?= e((string)$zone_filter); ?>">
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
            <label>Ministry
                <select id="graph-ministry">
                    <option value="all">All</option>
                    <?php foreach ($graph_ministries as $ministry): ?>
                        <?php
                            $mid = (int)$ministry['id'];
                            $has_opr = !empty($graph_opr_ids[$mid]) ? '1' : '0';
                            $has_dev = !empty($graph_dev_ids[$mid]) ? '1' : '0';
                        ?>
                        <option value="<?= e((string)$ministry['id']); ?>" data-opr="<?= $has_opr; ?>" data-dev="<?= $has_dev; ?>">
                            <?= e($ministry['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php if (!is_division_user()): ?>
                <label>Office Name
                    <select id="graph-division">
                        <option value="all">All</option>
                        <?php foreach ($graph_divisions as $div): ?>
                            <option value="<?= e((string)$div['id']); ?>" data-zone="<?= e((string)($div['zone_id'] ?? '')); ?>">
                                <?= e($div['office_name']); ?>
                            </option>
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
                <div>Ministry: <span id="graph-ministry-name">All</span></div>
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

<div class="modal-backdrop" id="info-opr" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="info-opr-title">
        <h3 id="info-opr-title">Operational Budget</h3>
        <p><?= e((string)($info['i_opr'] ?? 'No message')); ?></p>
        <div class="modal-actions">
            <button type="button" class="modal-close" data-close="info-opr">Close</button>
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

<div class="modal-backdrop" id="info-dev" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="info-dev-title">
        <h3 id="info-dev-title">Development Budget</h3>
        <p><?= e((string)($info['i_dev'] ?? 'No message')); ?></p>
        <div class="modal-actions">
            <button type="button" class="modal-close" data-close="info-dev">Close</button>
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
<div class="jump-buttons" aria-label="Jump to budget sections">
    <button type="button" class="jump-btn" data-jump-target="operational-heading" data-tooltip="Operational Budget" aria-label="Go to Operational Budget">
        O
    </button>
    <button type="button" class="jump-btn" data-jump-target="development-heading" data-tooltip="Development Budget" aria-label="Go to Development Budget">
        D
    </button>
</div>
<?php require __DIR__ . '/footer.php'; ?>
