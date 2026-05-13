<?php
require __DIR__ . '/header.php';
$orders = get_office_orders();
?>
<section class="card">
    <h2>Office Orders</h2>
    <p class="hint">Official orders, letters, and notices shared by the superadmin.</p>
</section>

<?php if (is_superadmin()): ?>
<section class="card">
    <h3>Upload Office Order</h3>
    <form method="post" action="index.php" enctype="multipart/form-data" class="grid">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="create_office_order">
        <label>Order / Letter Subject
            <input type="text" name="subject" required>
        </label>
        <label>Files
            <input type="file" name="order_files[]" accept=".pdf,.txt,.doc,.docx,.xls,.xlsx,image/*" multiple required>
        </label>
        <button type="submit">Upload Files</button>
    </form>
</section>
<?php endif; ?>

<section class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>SL No</th>
                    <th>Subject</th>
                    <th>Date of Upload</th>
                    <th>Files</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$orders): ?>
                    <tr>
                        <td colspan="4" class="muted">No office orders uploaded yet.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($orders as $index => $order): ?>
                    <tr>
                        <td><?= e((string)($index + 1)); ?></td>
                        <td><?= e((string)$order['subject']); ?></td>
                        <td><?= e((string)$order['uploaded_at']); ?></td>
                        <td>
                            <div class="file-link-list">
                                <?php foreach (($order['files'] ?? []) as $file): ?>
                                    <?php
                                        $ext = strtolower((string)$file['file_ext']);
                                        $chipClass = 'is-file';
                                        $iconText = strtoupper($ext);
                                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true)) {
                                            $chipClass = 'is-image';
                                            $iconText = 'IMG';
                                        } elseif ($ext === 'pdf') {
                                            $chipClass = 'is-pdf';
                                            $iconText = 'PDF';
                                        } elseif (in_array($ext, ['doc', 'docx'], true)) {
                                            $chipClass = 'is-doc';
                                            $iconText = 'DOC';
                                        } elseif (in_array($ext, ['xls', 'xlsx'], true)) {
                                            $chipClass = 'is-sheet';
                                            $iconText = 'XLS';
                                        } elseif ($ext === 'txt') {
                                            $chipClass = 'is-text';
                                            $iconText = 'TXT';
                                        }
                                    ?>
                                    <a
                                        href="index.php?page=office_order_file&id=<?= e((string)$file['id']); ?>"
                                        target="_blank"
                                        rel="noopener"
                                        class="file-chip <?= e($chipClass); ?>"
                                    >
                                        <span class="file-chip-icon"><?= e($iconText); ?></span>
                                        <span class="file-chip-name"><?= e((string)$file['original_name']); ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/footer.php'; ?>
