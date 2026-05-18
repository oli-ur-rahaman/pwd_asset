<?php
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        redirect('index.php?page=login');
    }
}

function login_user(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email_id = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }
    if (isset($user['active_status']) && (int)$user['active_status'] === 0) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['user'] = $user;
    $_SESSION['show_welcome_message'] = true;
    return true;
}

function logout_user(): void
{
    $_SESSION = [];
    if (session_id() !== '') {
        session_destroy();
    }
}

function is_superadmin(): bool
{
    $user = current_user();
    return $user && (int)$user['office_role'] === 3;
}

function can_manage_superadmin_scope(?array $user = null): bool
{
    $user = $user ?: current_user();
    if (!$user) {
        return false;
    }
    return (int)($user['office_role'] ?? 0) === 3 && (int)($user['office_access_level'] ?? 0) !== 3;
}

function is_admin(): bool
{
    $user = current_user();
    return $user && (int)$user['office_role'] === 2;
}

function is_division_user(): bool
{
    $user = current_user();
    return $user && (int)$user['office_type'] === 4 && !empty($user['division_id']);
}

function is_circle_user(): bool
{
    $user = current_user();
    return $user && (int)$user['office_type'] === 3 && !empty($user['circle_id']);
}

function is_zone_user(): bool
{
    $user = current_user();
    return $user && (int)$user['office_type'] === 2 && !empty($user['zone_id']);
}

function is_chief_user(): bool
{
    $user = current_user();
    return $user && (int)$user['office_type'] === 1;
}

function is_view_only_user(?array $user = null): bool
{
    $user = $user ?: current_user();
    if (!$user || (int)($user['office_role'] ?? 0) >= 2) {
        return false;
    }
    return (int)($user['office_access_level'] ?? 2) === 3;
}

function can_modify_office_assets(?array $user = null): bool
{
    $user = $user ?: current_user();
    if (!$user) {
        return false;
    }
    if ((int)($user['office_role'] ?? 0) >= 2) {
        return true;
    }
    return !is_view_only_user($user);
}

function can_view_logs(): bool
{
    return false;
}
