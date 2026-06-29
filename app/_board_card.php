<div class="card card-actions">
    <?php $current = current_user(); ?>
    <?php $is_office_type4 = (int)($current['office_type'] ?? 0) === 4; ?>
    <div class="card-head">
        <?php if ($title !== ''): ?>
            <h2><?= e($title); ?></h2>
        <?php endif; ?>
        <div class="card-actions-bar">
        <?php if (is_division_user() && $show_ministry_col): ?>
            <?php if ((int)($current['office_type'] ?? 0) === 4): ?>
                <button type="button" class="icon-link" title="Add Ministry Row" aria-label="Add Ministry Row" data-ministry-list="<?= e($table); ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 5v14"></path>
                        <path d="M5 12h14"></path>
                    </svg>
                </button>
            <?php endif; ?>
        <?php endif; ?>
        <?php
            $card_ministry_id = $card_meta['ministry_id'] ?? null;
            $card_ministry_name = $card_meta['ministry_name'] ?? null;
            $card_division_id = $card_meta['division_id'] ?? null;
            $card_division_name = $card_meta['division_name'] ?? null;
            $card_view_mode = $card_meta['view_mode'] ?? null;
        ?>
        <button type="button" class="icon-link" title="Graph" aria-label="Graph" data-modal="graph-modal" data-table="<?= e($table); ?>"
            <?= $card_ministry_id ? 'data-ministry-id="' . e((string)$card_ministry_id) . '"' : ''; ?>
            <?= $card_ministry_name ? 'data-ministry-name="' . e((string)$card_ministry_name) . '"' : ''; ?>
            <?= $card_division_id ? 'data-division-id="' . e((string)$card_division_id) . '"' : ''; ?>
            <?= $card_division_name ? 'data-division-name="' . e((string)$card_division_name) . '"' : ''; ?>
            <?= $card_view_mode ? 'data-view-mode="' . e((string)$card_view_mode) . '"' : ''; ?>
        >
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 18V6"></path>
                <path d="M4 18h16"></path>
                <path d="M7 14l4-4 4 3 4-6"></path>
            </svg>
        </button>
        <button type="button" class="icon-link" title="Download" aria-label="Download" data-modal="<?= e($download_modal); ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 4v10"></path>
                <path d="M8 10l4 4 4-4"></path>
                <path d="M4 20h16"></path>
            </svg>
        </button>
        <button type="button" class="icon-link" title="Information" aria-label="Information" data-modal="<?= e($info_modal); ?>">i</button>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>SL</th>
                    <?php
                        $division_col = $show_division_col;
                        if ($division_col === null) {
                            $division_col = !is_division_user();
                        }
                    ?>
                    <?php if ($show_ministry_col): ?>
                        <th>Ministry/Type Name</th>
                    <?php endif; ?>
                    <?php if ($division_col): ?>
                        <th>Division</th>
                    <?php endif; ?>
                    <?php if (str_starts_with($table, 'opr_') || $table === 'operational'): ?>
                        <th>Total no. of packages (as Per Approved APP)</th>
                    <?php else: ?>
                        <th>Total no. of packages (as Per Approved DPP)</th>
                    <?php endif; ?>
                    <th>Total Value of packages in Lakh Tk.</th>
                    <th>In live (No.)</th>
                    <th>Evaluation/Appr.(No.)</th>
                    <th>Contract Awarded (No.)</th>
                    <th>Value of awarded contracts in Lakh Tk.</th>
                    <?php if (!is_division_user()): ?>
                        <th>Progress (contract pkgs / total pkgs) %</th>
                        <th>Progress (Total Cont Amount / Total Pkg Amount) %</th>
                        <th>Updated (days ago)</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php $sl = 1; ?>
                <?php foreach ($rows as $row): ?>
                    <?php
                        $row_classes = [];
                        if ($show_ministry_col && $is_office_type4) {
                            $row_classes[] = 'row-clickable';
                        }
                        $ministry_id = (int)($row['ministry_id'] ?? 0);
                        $is_default_ministry = $show_ministry_col && $ministry_id > 0 && in_array($ministry_id, $default_ministry_ids, true);
                    ?>
                    <tr
                        <?php if ($show_ministry_col): ?>
                            data-table="<?= e($table); ?>"
                            data-ministry-id="<?= e((string)($row['ministry_id'] ?? '0')); ?>"
                            data-ministry-name="<?= e((string)($row['ministry_name'] ?? '')); ?>"
                            data-default="<?= $is_default_ministry ? '1' : '0'; ?>"
                        <?php endif; ?>
                        <?= $row_classes ? 'class="' . e(implode(' ', $row_classes)) . '"' : ''; ?>
                    >
                        <td><?= $sl++; ?></td>
                        <?php if ($show_ministry_col): ?>
                            <td><?= e((string)($row['ministry_name'] ?? '-')); ?></td>
                        <?php endif; ?>
                        <?php if ($division_col): ?>
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
                                $pkg_total = (float)($row['pkg'] ?? 0);
                                $pkg_cont = (float)($row['pkg_cont'] ?? 0);
                                $est_total = (float)($row['est'] ?? 0);
                                $cont_total = (float)($row['cont'] ?? 0);
                                $prog_pkg = $pkg_total > 0 ? ($pkg_cont / $pkg_total) * 100 : 0;
                                $prog_amt = $est_total > 0 ? ($cont_total / $est_total) * 100 : 0;
                            ?>
                            <td><?= e(number_format($prog_pkg, 2)); ?>%</td>
                            <td><?= e(number_format($prog_amt, 2)); ?>%</td>
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
                <?php
                    $totals = [
                        'pkg' => 0,
                        'est' => 0,
                        'pkg_live' => 0,
                        'pkg_eval' => 0,
                        'pkg_cont' => 0,
                        'cont' => 0,
                    ];
                    foreach ($rows as $row) {
                        $totals['pkg'] += (float)($row['pkg'] ?? 0);
                        $totals['est'] += (float)($row['est'] ?? 0);
                        $totals['pkg_live'] += (float)($row['pkg_live'] ?? 0);
                        $totals['pkg_eval'] += (float)($row['pkg_eval'] ?? 0);
                        $totals['pkg_cont'] += (float)($row['pkg_cont'] ?? 0);
                        $totals['cont'] += (float)($row['cont'] ?? 0);
                    }
                    $total_prog_pkg = $totals['pkg'] > 0 ? ($totals['pkg_cont'] / $totals['pkg']) * 100 : 0;
                    $total_prog_amt = $totals['est'] > 0 ? ($totals['cont'] / $totals['est']) * 100 : 0;
                ?>
                <tr class="total-row">
                    <td></td>
                    <?php if ($show_ministry_col): ?>
                        <td>Total</td>
                    <?php endif; ?>
                    <?php if ($division_col): ?>
                        <td>Total</td>
                    <?php endif; ?>
                    <td><?= e(number_format($totals['pkg'], 0)); ?></td>
                    <td><?= e(number_format($totals['est'], 2)); ?></td>
                    <td><?= e(number_format($totals['pkg_live'], 0)); ?></td>
                    <td><?= e(number_format($totals['pkg_eval'], 0)); ?></td>
                    <td><?= e(number_format($totals['pkg_cont'], 0)); ?></td>
                    <td><?= e(number_format($totals['cont'], 2)); ?></td>
                    <?php if (!is_division_user()): ?>
                        <td><?= e(number_format($total_prog_pkg, 2)); ?>%</td>
                        <td><?= e(number_format($total_prog_amt, 2)); ?>%</td>
                        <td></td>
                    <?php endif; ?>
                </tr>
            </tbody>
        </table>
    </div>
</div>
