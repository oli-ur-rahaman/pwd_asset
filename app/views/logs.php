<?php
require __DIR__ . '/header.php';
$user = current_user();
$logs = get_logs_for_user((int)$user['id']);
?>
<section class="card">
    <h2>Activity Logs</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Table</th>
                <th>Record</th>
                <th>Summary</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= e($log['created_at']); ?></td>
                    <td><?= e($log['table_name']); ?></td>
                    <td><?= e((string)$log['record_id']); ?></td>
                    <td><?= e($log['summary']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php require __DIR__ . '/footer.php'; ?>
