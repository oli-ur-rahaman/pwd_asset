<?php
require __DIR__ . '/header.php';
$user = current_user();
$fy = get_current_fy();
$office_name = get_office_name_for_user($user);
$today = date('Y-m-d');
$division_list = get_divisions_for_user($user);
$division_ids = array_column($division_list, 'id');
$latest_revenue = $fy ? get_latest_records('revenue', (int)$fy['id'], $division_ids) : [];
$latest_development = $fy ? get_latest_records('development', (int)$fy['id'], $division_ids) : [];
$latest_rev = $fy && is_division_user() ? get_latest_record_for_division('revenue', (int)$fy['id'], (int)$user['division_id']) : null;
$latest_dev = $fy && is_division_user() ? get_latest_record_for_division('development', (int)$fy['id'], (int)$user['division_id']) : null;
$fy_list = get_fy_list();
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
    <h2 class="center">APP Manager</h2>
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

<section class="board-grid">
    <div class="card card-actions">
        <div class="card-actions-bar">
            <?php if (is_division_user()): ?>
                <button type="button" class="icon-link" title="Edit" aria-label="Edit" data-modal="revenue-modal">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M4 20h4l10-10-4-4L4 16v4z"></path>
                        <path d="M13 6l4 4"></path>
                    </svg>
                </button>
            <?php endif; ?>
            <button type="button" class="icon-link" title="Graph" aria-label="Graph" data-modal="graph-modal" data-table="revenue">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 18V6"></path>
                    <path d="M4 18h16"></path>
                    <path d="M7 14l4-4 4 3 4-6"></path>
                </svg>
            </button>
            <button type="button" class="icon-link" title="Download" aria-label="Download" data-modal="revenue-download-modal">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 4v10"></path>
                    <path d="M8 10l4 4 4-4"></path>
                    <path d="M4 20h16"></path>
                </svg>
            </button>
        </div>
        <h2>Revenue Budget</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <?php if (!is_division_user()): ?>
                            <th>Division</th>
                        <?php endif; ?>
                        <th>Total no. of packages</th>
                        <th>Total Value of packages in Lakh Tk.</th>
                        <th>In live (No.)</th>
                        <th>Evaluation/Appr.(No.)</th>
                        <th>Contract Awarded (No.)</th>
                        <th>Value of awarded contracts in Lakh Tk.</th>
                        <?php if (!is_division_user()): ?>
                            <th>Updated (days ago)</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($latest_revenue as $row): ?>
                        <tr>
                            <?php if (!is_division_user()): ?>
                                <td><?= e($row['office_name']); ?></td>
                            <?php endif; ?>
                            <td><?= e((string)$row['pkg']); ?></td>
                            <td><?= e((string)$row['est']); ?></td>
                            <td><?= e((string)$row['pkg_live']); ?></td>
                            <td><?= e((string)$row['pkg_eval']); ?></td>
                            <td><?= e((string)$row['pkg_cont']); ?></td>
                            <td><?= e((string)$row['cont']); ?></td>
                            <?php if (!is_division_user()): ?>
                                <?php
                                    $days = '';
                                    if (!empty($row['created_at'])) {
                                        $diff = (new DateTime($row['created_at']))->diff(new DateTime($today));
                                        $days = $diff->format('%a');
                                    }
                                ?>
                                <td><?= $days !== '' ? e($days) : ''; ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card card-actions">
        <div class="card-actions-bar">
            <?php if (is_division_user()): ?>
                <button type="button" class="icon-link" title="Edit" aria-label="Edit" data-modal="development-modal">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M4 20h4l10-10-4-4L4 16v4z"></path>
                        <path d="M13 6l4 4"></path>
                    </svg>
                </button>
            <?php endif; ?>
            <button type="button" class="icon-link" title="Graph" aria-label="Graph" data-modal="graph-modal" data-table="development">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 18V6"></path>
                    <path d="M4 18h16"></path>
                    <path d="M7 14l4-4 4 3 4-6"></path>
                </svg>
            </button>
            <button type="button" class="icon-link" title="Download" aria-label="Download" data-modal="development-download-modal">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 4v10"></path>
                    <path d="M8 10l4 4 4-4"></path>
                    <path d="M4 20h16"></path>
                </svg>
            </button>
        </div>
        <h2>Development Budget</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <?php if (!is_division_user()): ?>
                            <th>Division</th>
                        <?php endif; ?>
                        <th>Total no. of packages</th>
                        <th>Total Value of packages in Lakh Tk.</th>
                        <th>In live (No.)</th>
                        <th>Evaluation/Appr.(No.)</th>
                        <th>Contract Awarded (No.)</th>
                        <th>Value of awarded contracts in Lakh Tk.</th>
                        <?php if (!is_division_user()): ?>
                            <th>Updated (days ago)</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($latest_development as $row): ?>
                        <tr>
                            <?php if (!is_division_user()): ?>
                                <td><?= e($row['office_name']); ?></td>
                            <?php endif; ?>
                            <td><?= e((string)$row['pkg']); ?></td>
                            <td><?= e((string)$row['est']); ?></td>
                            <td><?= e((string)$row['pkg_live']); ?></td>
                            <td><?= e((string)$row['pkg_eval']); ?></td>
                            <td><?= e((string)$row['pkg_cont']); ?></td>
                            <td><?= e((string)$row['cont']); ?></td>
                            <?php if (!is_division_user()): ?>
                                <?php
                                    $days = '';
                                    if (!empty($row['created_at'])) {
                                        $diff = (new DateTime($row['created_at']))->diff(new DateTime($today));
                                        $days = $diff->format('%a');
                                    }
                                ?>
                                <td><?= $days !== '' ? e($days) : ''; ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php if (is_division_user()): ?>
    <div class="modal-backdrop" id="revenue-modal" aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="revenue-title">
            <h3 id="revenue-title">Revenue Budget Packages Information</h3>
            <p class="modal-sub">Date: <?= e($today); ?> | FY: <?= e($fy['fiscal_years'] ?? 'Not set'); ?></p>
            <form method="post" action="index.php" class="grid">
                <?= csrf_input(); ?>
                <input type="hidden" name="action" value="add_record">
                <input type="hidden" name="table" value="revenue">
                <label>Total no. of packages
                    <input type="number" name="pkg" value="<?= e((string)($latest_rev['pkg'] ?? 0)); ?>" min="0" required>
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
                <input type="hidden" name="table" value="development">
                <label>Total no. of packages
                    <input type="number" name="pkg" value="<?= e((string)($latest_dev['pkg'] ?? 0)); ?>" min="0" required>
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
<?php endif; ?>

<div class="modal-backdrop" id="revenue-download-modal" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="revenue-download-title">
        <h3 id="revenue-download-title">Download Revenue Budget</h3>
        <form method="get" action="export_board.php" class="grid">
            <input type="hidden" name="table" value="revenue">
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
<?php require __DIR__ . '/footer.php'; ?>
