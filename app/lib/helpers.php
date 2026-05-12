<?php
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_url(string $path = ''): string
{
    global $config;
    $base = rtrim($config['app']['base_url'], '/');
    $path = ltrim($path, '/');
    return $base === '' ? '/' . $path : $base . '/' . $path;
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function csrf_input(): string
{
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}
