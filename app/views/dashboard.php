<?php
require __DIR__ . '/header.php';
$user = current_user();
$fy = get_current_fy();
$fy_list = get_fy_list();
$division_list = get_divisions_for_user($user);
$division_ids = array_column($division_list, 'id');
$latest_revenue = $fy ? get_latest_records('revenue', (int)$fy['id'], $division_ids) : [];
$latest_development = $fy ? get_latest_records('development', (int)$fy['id'], $division_ids) : [];
?>

<section class="card" id="charts">
    <h2>Current Fiscal Year</h2>
    <p><?= $fy ? e($fy['fiscal_years']) : 'Not set'; ?></p>
</section>

<?php if (is_division_user()): ?>
    <?php
        $latest_rev = $fy ? get_latest_record_for_division('revenue', (int)$fy['id'], (int)$user['division_id']) : null;
        $latest_dev = $fy ? get_latest_record_for_division('development', (int)$fy['id'], (int)$user['division_id']) : null;
    ?>
    <section class="card">
        <h2>Division Entry - Revenue</h2>
        <form method="post" action="index.php">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="add_record">
            <input type="hidden" name="table" value="revenue">
            <div class="grid">
                <label>Total Packages
                    <input type="number" name="pkg" value="<?= e((string)($latest_rev['pkg'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Total Value (Lakh Tk.)
                    <input type="number" name="est" step="0.01" value="<?= e((string)($latest_rev['est'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Packages in Live Tender
                    <input type="number" name="pkg_live" value="<?= e((string)($latest_rev['pkg_live'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Packages under Evaluation
                    <input type="number" name="pkg_eval" value="<?= e((string)($latest_rev['pkg_eval'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Packages Contract Awarded
                    <input type="number" name="pkg_cont" value="<?= e((string)($latest_rev['pkg_cont'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Value of Awarded Contracts (Lakh Tk.)
                    <input type="number" name="cont" step="0.01" value="<?= e((string)($latest_rev['cont'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Note / Remarks
                    <textarea name="note" rows="2"><?= e((string)($latest_rev['note'] ?? '')); ?></textarea>
                </label>
            </div>
            <button type="submit">Save Revenue</button>
        </form>
    </section>

    <section class="card">
        <h2>Division Entry - Development</h2>
        <form method="post" action="index.php">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="add_record">
            <input type="hidden" name="table" value="development">
            <div class="grid">
                <label>Total Packages
                    <input type="number" name="pkg" value="<?= e((string)($latest_dev['pkg'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Total Value (Lakh Tk.)
                    <input type="number" name="est" step="0.01" value="<?= e((string)($latest_dev['est'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Packages in Live Tender
                    <input type="number" name="pkg_live" value="<?= e((string)($latest_dev['pkg_live'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Packages under Evaluation
                    <input type="number" name="pkg_eval" value="<?= e((string)($latest_dev['pkg_eval'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Packages Contract Awarded
                    <input type="number" name="pkg_cont" value="<?= e((string)($latest_dev['pkg_cont'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Value of Awarded Contracts (Lakh Tk.)
                    <input type="number" name="cont" step="0.01" value="<?= e((string)($latest_dev['cont'] ?? 0)); ?>" min="0" required>
                </label>
                <label>Note / Remarks
                    <textarea name="note" rows="2"><?= e((string)($latest_dev['note'] ?? '')); ?></textarea>
                </label>
            </div>
            <button type="submit">Save Development</button>
        </form>
    </section>
<?php endif; ?>

<section class="card" id="exports">
    <h2>Latest Revenue Data</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Division</th>
                    <th>Total Packages</th>
                    <th>Total Value (Lakh Tk.)</th>
                    <th>Live Tender</th>
                    <th>Evaluation</th>
                    <th>Contract Awarded</th>
                    <th>Contract Value</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($latest_revenue as $row): ?>
                    <tr>
                        <td><?= e($row['office_name']); ?></td>
                        <td><?= e((string)$row['pkg']); ?></td>
                        <td><?= e((string)$row['est']); ?></td>
                        <td><?= e((string)$row['pkg_live']); ?></td>
                        <td><?= e((string)$row['pkg_eval']); ?></td>
                        <td><?= e((string)$row['pkg_cont']); ?></td>
                        <td><?= e((string)$row['cont']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <h2>Latest Development Data</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Division</th>
                    <th>Total Packages</th>
                    <th>Total Value (Lakh Tk.)</th>
                    <th>Live Tender</th>
                    <th>Evaluation</th>
                    <th>Contract Awarded</th>
                    <th>Contract Value</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($latest_development as $row): ?>
                    <tr>
                        <td><?= e($row['office_name']); ?></td>
                        <td><?= e((string)$row['pkg']); ?></td>
                        <td><?= e((string)$row['est']); ?></td>
                        <td><?= e((string)$row['pkg_live']); ?></td>
                        <td><?= e((string)$row['pkg_eval']); ?></td>
                        <td><?= e((string)$row['pkg_cont']); ?></td>
                        <td><?= e((string)$row['cont']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <h2>Charts</h2>
    <form id="chart-filter" class="chart-filter">
        <label>Fiscal Year
            <select name="fy_id">
                <?php foreach ($fy_list as $fy_row): ?>
                    <option value="<?= e((string)$fy_row['id']); ?>" <?= $fy && (int)$fy_row['id'] === (int)$fy['id'] ? 'selected' : ''; ?>>
                        <?= e($fy_row['fiscal_years']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Division
            <select name="division_id">
                <?php foreach ($division_list as $div): ?>
                    <option value="<?= e((string)$div['id']); ?>"><?= e($div['office_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Dataset
            <select name="table">
                <option value="revenue">Revenue</option>
                <option value="development">Development</option>
            </select>
        </label>
        <label>Metric
            <select name="metric">
                <option value="pkg">Total Packages</option>
                <option value="est">Total Value</option>
                <option value="pkg_live">Live Tender</option>
                <option value="pkg_eval">Evaluation</option>
                <option value="pkg_cont">Contract Awarded</option>
                <option value="cont">Contract Value</option>
            </select>
        </label>
        <button type="submit">Load Chart</button>
    </form>
    <canvas id="data-chart" height="120"></canvas>
    <div class="chart-actions">
        <button type="button" id="download-jpeg">Download JPEG</button>
        <form method="post" action="chart_export.php" id="chart-pdf-form">
            <?= csrf_input(); ?>
            <input type="hidden" name="image_data" id="chart-image-data">
            <button type="button" id="download-pdf">Download PDF</button>
        </form>
    </div>
</section>

<section class="card">
    <h2>Exports</h2>
    <form method="get" action="export.php" class="export-form">
        <label>Type
            <select name="table">
                <option value="revenue">Revenue</option>
                <option value="development">Development</option>
            </select>
        </label>
        <label>Fiscal Year
            <select name="fy_id">
                <?php foreach ($fy_list as $fy_row): ?>
                    <option value="<?= e((string)$fy_row['id']); ?>" <?= $fy && (int)$fy_row['id'] === (int)$fy['id'] ? 'selected' : ''; ?>>
                        <?= e($fy_row['fiscal_years']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Division
            <select name="division_id">
                <option value="all">All</option>
                <?php foreach ($division_list as $div): ?>
                    <option value="<?= e((string)$div['id']); ?>"><?= e($div['office_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Mode
            <select name="mode">
                <option value="latest">Latest</option>
                <option value="monthly">Monthly</option>
            </select>
        </label>
        <label>Format
            <select name="format">
                <option value="pdf">PDF</option>
                <option value="excel">Excel</option>
            </select>
        </label>
        <button type="submit">Download</button>
    </form>
</section>

<?php require __DIR__ . '/footer.php'; ?>
