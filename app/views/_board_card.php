<div class="card card-actions">
    <div class="card-actions-bar">
        <?php if (is_division_user()): ?>
            <button type="button" class="icon-link" title="Edit" aria-label="Edit" data-modal="<?= e($edit_modal); ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 20h4l10-10-4-4L4 16v4z"></path>
                    <path d="M13 6l4 4"></path>
                </svg>
            </button>
        <?php endif; ?>
        <button type="button" class="icon-link" title="Graph" aria-label="Graph" data-modal="graph-modal" data-table="<?= e($table); ?>">
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
    <h2><?= e($title); ?></h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <?php if (!is_division_user()): ?>
                        <th>Division</th>
                    <?php endif; ?>
                    <?php if (str_starts_with($table, 'opr_')): ?>
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
                <?php foreach ($rows as $row): ?>
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
            </tbody>
        </table>
    </div>
</div>
