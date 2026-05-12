<?php $info = get_info_row(); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e((string)($info['site_name'] ?? 'PWD APP Manager')); ?></title>
    <link rel="stylesheet" href="public/assets/style.css">
</head>
<body class="auth-body">
<div class="auth-wrap">
    <div class="auth-logo-row">
        <div class="logo-stack" aria-hidden="true">
            <span class="logo-dot"></span>
            <img src="public/assets/login_logo.png" alt="Organization Logo" class="auth-logo">
        </div>
    </div>
    <div class="auth-title-row">
        <h1><?= e((string)($info['site_name'] ?? 'PWD APP Manager')); ?></h1>
    </div>
    <div class="auth-grid">
        <div class="auth-card">
            <?php if ($msg = flash('error')): ?>
                <div class="alert error"><?= e($msg); ?></div>
            <?php endif; ?>
            <form method="post" action="index.php" class="login-grid">
                <?= csrf_input(); ?>
                <input type="hidden" name="action" value="login">
                <div class="login-title">Login</div>
                <div class="login-label">Email</div>
                <input type="email" name="email" placeholder="Enter PWD Official email" class="login-input" required>
                <div class="login-label">Password</div>
                <input type="password" name="password" placeholder="The Password" class="login-input" required>
                <div class="login-actions">
                    <button type="submit">Sign in</button>
                    <div class="login-links">
                        <a href="#" class="forgot-link" id="forgot-link">Forget Password</a>
                        <a href="<?= e((string)($info['video_tutorial_url'] ?? '#')); ?>" class="video-link" target="_blank" rel="noopener">Video Tutorial</a>
                    </div>
                </div>
            </form>
        </div>
        <div class="auth-info">
            <?= $info && !empty($info['login_message']) ? $info['login_message'] : '<p>Please sign in to continue.</p>'; ?>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="forgot-modal" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="forgot-title">
        <h3 id="forgot-title">Forgot Password</h3>
        <p>Please see the Help &amp; Instructions section on this login page.</p>
        <button type="button" id="close-forgot">Close</button>
    </div>
</div>
<script src="public/assets/app.js"></script>
</body>
</html>
