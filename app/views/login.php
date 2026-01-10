<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <link rel="stylesheet" href="public/assets/style.css">
</head>
<body class="auth-body">
<div class="auth-card">
    <h1>Login</h1>
    <?php if ($msg = flash('error')): ?>
        <div class="alert error"><?= e($msg); ?></div>
    <?php endif; ?>
    <form method="post" action="index.php">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="login">
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit">Sign in</button>
    </form>
</div>
</body>
</html>
