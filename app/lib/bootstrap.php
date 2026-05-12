<?php
$config = require __DIR__ . '/../config.php';

date_default_timezone_set('Asia/Dhaka');
ini_set('default_charset', 'UTF-8');
ini_set('session.use_strict_mode', '1');

session_name($config['app']['session_name']);
$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => $secure,
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require __DIR__ . '/helpers.php';
require __DIR__ . '/security.php';
require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/data.php';
require __DIR__ . '/exports.php';
require __DIR__ . '/asset.php';

$autoload = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoload)) {
    require $autoload;
}

ensure_asset_schema();
