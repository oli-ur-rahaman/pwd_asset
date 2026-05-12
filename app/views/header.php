<?php $user = current_user(); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>APP Dashboard</title>
    <link rel="stylesheet" href="public/assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<nav class="nav">
    <div class="nav-title">APP Monitoring</div>
    <div class="nav-links">
        <a href="index.php?page=board">Dashboard</a>
        <?php if (can_view_logs()): ?>
            <a href="index.php?page=logs">Logs</a>
        <?php endif; ?>
        <?php if (is_superadmin()): ?>
            <a href="index.php?page=admin">Management</a>
            <a href="index.php?page=interface">Interface</a>
            <a href="index.php?page=users">Users</a>
        <?php endif; ?>
        <a href="index.php?page=profile">Profile</a>
        <?php $info = get_info_row(); ?>
        <a href="<?= e((string)($info['video_tutorial_url'] ?? '#')); ?>" target="_blank" rel="noopener">Tutorial_video</a>
        <form method="post" action="index.php" class="logout-form">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="logout">
            <button type="submit">Logout</button>
        </form>
    </div>
</nav>
<main class="container">
<?php if ($msg = flash('success')): ?>
    <div class="alert success"><?= e($msg); ?></div>
<?php endif; ?>
<?php if ($msg = flash('error')): ?>
    <div class="alert error"><?= e($msg); ?></div>
<?php endif; ?>
<?php
    $needs_name = $user && (empty($user['officer_name']) || $user['officer_name'] === '0');
?>
<?php if ($needs_name): ?>
    <div class="modal-backdrop open" id="name-modal" aria-hidden="false">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="name-title">
            <h3 id="name-title">Update Officer Name</h3>
            <p>Please enter your name to continue.</p>
            <form method="post" action="index.php" id="name-form" class="grid">
                <?= csrf_input(); ?>
                <input type="hidden" name="action" value="update_profile">
                <label>Officer Name
                    <input type="text" name="officer_name" id="officer-name-input" required>
                </label>
                <div class="modal-actions">
                    <button type="submit">Save</button>
                    <button type="button" class="modal-close" id="name-cancel">Cancel</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
