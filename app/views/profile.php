<?php
require __DIR__ . '/header.php';
$user = current_user();
?>
<section class="card">
    <h2>Profile</h2>
    <?php if ($msg = flash('success')): ?>
        <div class="alert success"><?= e($msg); ?></div>
    <?php endif; ?>
    <?php if ($msg = flash('error')): ?>
        <div class="alert error"><?= e($msg); ?></div>
    <?php endif; ?>
    <form method="post" action="index.php" class="grid profile-form">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="update_profile">
        <label>Officer Name
            <input type="text" name="officer_name" value="<?= e((string)($user['officer_name'] ?? '')); ?>" required>
        </label>
        <button type="submit" class="btn-small">Update Name</button>
    </form>
</section>

<section class="card">
    <h2>Change Password</h2>
    <form method="post" action="index.php" class="grid profile-form">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="update_profile">
        <label>New Password
            <input type="password" name="password" required>
        </label>
        <label>Confirm New Password
            <input type="password" name="password_confirm" required>
        </label>
        <button type="submit" class="btn-small">Update Password</button>
    </form>
</section>
<?php require __DIR__ . '/footer.php'; ?>
