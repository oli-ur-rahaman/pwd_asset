<?php $user = current_user(); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $info = get_info_row(); ?>
    <?php $themeKey = asset_normalize_theme_key((string)($info['ui_theme_key'] ?? '')); ?>
<?php $globalTutorialUrl = (string)($info['video_tutorial_url'] ?? ''); ?>
<?php $globalTutorialEmbedUrl = (string)(asset_youtube_embed_url($globalTutorialUrl) ?? ''); ?>
<?php $globalHostedTutorialUrl = (string)(asset_global_tutorial_stream_url($info) ?? ''); ?>
<?php $globalHostedTutorialName = (string)($info['hosted_tutorial_video_original_name'] ?? $info['hosted_tutorial_video_path'] ?? ''); ?>
    <title><?= e((string)($info['site_name'] ?? 'PWD Asset Management System')); ?></title>
    <link rel="stylesheet" href="<?= e(asset_url('public/assets/style.css')); ?>">
</head>
<body class="theme-<?= e($themeKey); ?>">
<nav class="nav">
    <div class="nav-title"><?= e((string)($info['site_name'] ?? 'PWD Asset Management System')); ?></div>
    <div class="nav-links">
        <a href="index.php?page=board">Information</a>
        <?php if (is_superadmin()): ?>
            <a href="index.php?page=audit">Audit</a>
        <?php endif; ?>
        <a href="index.php?page=office_orders">Office Orders</a>
        <?php if (is_superadmin()): ?>
            <a href="index.php?page=declarations">Declarations</a>
            <?php if (can_manage_superadmin_scope()): ?>
                <a href="index.php?page=admin">Management</a>
                <a href="index.php?page=download_manager">Download Manager</a>
                <a href="index.php?page=comparison">Comparison</a>
                <a href="index.php?page=offices">Offices</a>
                <a href="index.php?page=user_permissions">User Permissions</a>
                <a href="index.php?page=interface">Interface</a>
            <?php endif; ?>
        <?php endif; ?>
        <a href="index.php?page=profile">Profile</a>
        <button type="button" class="button-link nav-tutorial-button" data-global-tutorial-open data-tutorial-url="<?= e($globalTutorialUrl); ?>" data-tutorial-embed-url="<?= e($globalTutorialEmbedUrl); ?>" data-tutorial-hosted-url="<?= e($globalHostedTutorialUrl); ?>" data-tutorial-hosted-name="<?= e($globalHostedTutorialName); ?>">Tutorial</button>
        <form method="post" action="index.php" class="logout-form">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="logout">
            <button type="submit">Logout</button>
        </form>
    </div>
</nav>
<div class="modal-backdrop" id="global-tutorial-modal" aria-hidden="true">
    <div class="modal-card modal-wide" role="dialog" aria-modal="true" aria-labelledby="global-tutorial-title">
        <div class="flash-modal-head">
            <h3 id="global-tutorial-title">Tutorial</h3>
            <button type="button" class="welcome-modal-close modal-close" data-close="global-tutorial-modal" aria-label="Close">Ã—</button>
        </div>
        <div class="field-help-video hidden" id="global-tutorial-video">
            <video id="global-tutorial-player" class="hidden" controls preload="none"></video>
            <iframe
                id="global-tutorial-iframe"
                class="hidden"
                src=""
                title="Global tutorial"
                loading="lazy"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                allowfullscreen
            ></iframe>
            <p><a href="#" target="_blank" rel="noopener" id="global-tutorial-link">Open tutorial</a></p>
        </div>
        <div class="alert error hidden" id="global-tutorial-empty">No tutorial video is configured.</div>
        <div class="modal-actions">
            <button type="button" class="modal-close" data-close="global-tutorial-modal">Close</button>
        </div>
    </div>
</div>
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
<script>
    (function () {
        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-close="welcome-modal"]');
            if (!button) {
                return;
            }
            var modal = document.getElementById('welcome-modal');
            if (!modal) {
                return;
            }
            event.preventDefault();
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
        });
    })();
</script>
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
