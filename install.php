<?php
$config = require __DIR__ . '/app/config.php';
date_default_timezone_set('Asia/Dhaka');
ini_set('default_charset', 'UTF-8');

require __DIR__ . '/app/lib/db.php';
require __DIR__ . '/app/lib/asset.php';

$lockDir = __DIR__ . '/storage';
$lockPath = $lockDir . '/install.lock';
$message = null;
$error = null;

function installer_storage_ready(string $dir): void
{
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new RuntimeException('Unable to create storage directory.');
    }
    $templates = $dir . '/templates';
    if (!is_dir($templates) && !mkdir($templates, 0777, true) && !is_dir($templates)) {
        throw new RuntimeException('Unable to create storage/templates directory.');
    }
}

function installer_run_schema_file(string $path): void
{
    if (!is_file($path)) {
        throw new RuntimeException('Schema file not found.');
    }
    $sql = (string)file_get_contents($path);
    $statements = preg_split('/;\s*(?:\r\n|\r|\n|$)/', $sql) ?: [];
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement === '') {
            continue;
        }
        db()->exec($statement);
    }
}

function installer_upsert_superadmin(string $email, string $password, string $officerName): void
{
    $stmt = db()->prepare('SELECT id FROM users WHERE email_id = ? LIMIT 1');
    $stmt->execute([$email]);
    $userId = (int)($stmt->fetchColumn() ?: 0);
    $hash = password_hash($password, PASSWORD_DEFAULT);
    if ($userId > 0) {
        db()->prepare('UPDATE users SET officer_name = ?, password = ?, office_type = 1, office_role = 3, active_status = 1, zone_id = NULL, circle_id = NULL, division_id = NULL, updated_at = NOW() WHERE id = ?')->execute([$officerName, $hash, $userId]);
        return;
    }
    db()->prepare('INSERT INTO users (email_id, officer_name, password, office_type, office_role, active_status, created_at) VALUES (?, ?, ?, 1, 3, 1, NOW())')->execute([$email, $officerName, $hash]);
}

function installer_upsert_info(string $siteName): void
{
    $stmt = db()->query('SELECT id FROM info ORDER BY id ASC LIMIT 1');
    $infoId = (int)($stmt->fetchColumn() ?: 0);
    if ($infoId > 0) {
        db()->prepare('UPDATE info SET site_name = ?, updated_at = NOW() WHERE id = ?')->execute([$siteName, $infoId]);
        return;
    }
    db()->prepare('INSERT INTO info (site_name, created_at) VALUES (?, NOW())')->execute([$siteName]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siteName = trim((string)($_POST['site_name'] ?? 'PWD Asset Management System'));
    $email = trim((string)($_POST['email_id'] ?? ''));
    $officerName = trim((string)($_POST['officer_name'] ?? 'Super Admin'));
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'A valid superadmin email is required.';
    } elseif ($password === '') {
        $error = 'A superadmin password is required.';
    } else {
        try {
            installer_storage_ready($lockDir);
            installer_run_schema_file(__DIR__ . '/app/sql/schema.sql');
            ensure_asset_schema();
            installer_upsert_info($siteName);
            installer_upsert_superadmin($email, $password, $officerName);
            file_put_contents($lockPath, json_encode([
                'installed_at' => date('Y-m-d H:i:s'),
                'superadmin' => $email,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $message = 'Installation completed. Tables are ready and the superadmin account has been created or updated.';
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$isInstalled = is_file($lockPath);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PWD Asset Installer</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", Arial, sans-serif; background: #f3f6fb; color: #17324d; }
        .wrap { max-width: 760px; margin: 48px auto; padding: 0 20px; }
        .card { background: #fff; border-radius: 18px; padding: 24px; box-shadow: 0 18px 44px rgba(15, 76, 129, 0.10); }
        h1 { margin-top: 0; }
        .grid { display: grid; gap: 16px; }
        label { display: grid; gap: 8px; font-weight: 600; }
        input { width: 100%; border: 1px solid #c7d4e3; border-radius: 10px; padding: 10px 12px; font: inherit; }
        button { background: #0f4c81; color: #fff; border: 0; border-radius: 10px; padding: 12px 16px; cursor: pointer; font: inherit; }
        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; }
        .success { background: #e8f7ef; color: #0f6b43; }
        .error { background: #fdeceb; color: #b42318; }
        .muted { color: #5b748f; }
        code { background: #eff5fb; padding: 2px 6px; border-radius: 6px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>PWD Asset Installer</h1>
        <p class="muted">This page creates the database tables from <code>app/sql/schema.sql</code>, seeds the asset defaults, and creates or updates one superadmin user.</p>
        <?php if ($isInstalled && !$message): ?>
            <div class="alert success">This application already appears to be installed. Running the installer again will refresh the schema and update the superadmin account.</div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="alert success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <form method="post" class="grid">
            <label>Site Name
                <input type="text" name="site_name" value="PWD Asset Management System" required>
            </label>
            <label>Superadmin Email
                <input type="email" name="email_id" placeholder="admin@example.com" required>
            </label>
            <label>Superadmin Name
                <input type="text" name="officer_name" value="Super Admin" required>
            </label>
            <label>Superadmin Password
                <input type="password" name="password" required>
            </label>
            <button type="submit">Install Now</button>
        </form>
        <p class="muted">Before opening this page, set the live DB credentials in <code>app/config.php</code>.</p>
    </div>
</div>
</body>
</html>
