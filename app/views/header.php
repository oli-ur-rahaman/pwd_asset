<?php $user = current_user(); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $info = get_info_row(); ?>
    <?php $themeKey = asset_normalize_theme_key((string)($info['ui_theme_key'] ?? '')); ?>
    <title><?= e((string)($info['site_name'] ?? 'PWD Asset Management System')); ?></title>
    <link rel="stylesheet" href="public/assets/style.css">
</head>
<body class="theme-<?= e($themeKey); ?>">
<nav class="nav">
    <div class="nav-title"><?= e((string)($info['site_name'] ?? 'PWD Asset Management System')); ?></div>
    <div class="nav-links">
        <a href="index.php?page=board">Information</a>
        <a href="index.php?page=office_orders">Office Orders</a>
        <?php if (is_superadmin()): ?>
            <a href="index.php?page=declarations">Declarations</a>
            <?php if (can_manage_superadmin_scope()): ?>
                <a href="index.php?page=admin">Management</a>
                <a href="index.php?page=offices">Offices</a>
                <a href="index.php?page=user_permissions">User Permissions</a>
                <a href="index.php?page=interface">Interface</a>
            <?php endif; ?>
        <?php endif; ?>
        <a href="index.php?page=profile">Profile</a>
        <a href="<?= e((string)($info['video_tutorial_url'] ?? '#')); ?>" target="_blank" rel="noopener">Tutorial</a>
        <form method="post" action="index.php" class="logout-form">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="logout">
            <button type="submit">Logout</button>
        </form>
    </div>
</nav>
<main class="container">
<?php
    $success_message = flash('success');
    $error_message = flash('error');
    $needs_name = $user && (empty($user['officer_name']) || $user['officer_name'] === '0');
    $show_welcome_message = !$needs_name && !empty($_SESSION['show_welcome_message']) && !empty($info['welcome_message']);
    if ($show_welcome_message) {
        unset($_SESSION['show_welcome_message']);
    }
?>
<?php if ($success_message || $error_message): ?>
    <div class="modal-backdrop open" id="flash-modal" aria-hidden="false">
        <div class="modal-card flash-modal-card" role="dialog" aria-modal="true" aria-labelledby="flash-modal-title">
            <div class="flash-modal-head">
                <h3 id="flash-modal-title"><?= $error_message ? 'Notice' : 'Success'; ?></h3>
                <button type="button" class="welcome-modal-close modal-close" data-close="flash-modal" aria-label="Close">Ã—</button>
            </div>
            <div class="alert <?= $error_message ? 'error' : 'success'; ?> flash-modal-alert">
                <?= e((string)($error_message ?: $success_message)); ?>
            </div>
            <div class="modal-actions">
                <button type="button" class="modal-close" data-close="flash-modal">Close</button>
            </div>
        </div>
    </div>
<?php endif; ?>
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
                    <button type="button" class="modal-close" id="name-cancel" data-close="name-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
<?php if ($show_welcome_message): ?>
    <div class="modal-backdrop open" id="welcome-modal" aria-hidden="false">
        <div class="modal-card welcome-modal-card" role="dialog" aria-modal="true" aria-label="Welcome message">
            <button type="button" class="welcome-modal-close modal-close" data-close="welcome-modal" aria-label="Close">×</button>
            <div class="welcome-message-body">
                <?= (string)$info['welcome_message']; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
