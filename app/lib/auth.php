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
    session_regenerate_id(true);
    $_SESSION['user'] = $user;
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

function can_view_logs(): bool
{
    return is_division_user() || is_superadmin();
}
