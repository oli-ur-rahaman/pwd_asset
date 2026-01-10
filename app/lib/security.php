<?php
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_validate(?string $token): bool
{
    if (!$token || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function input_int(string $key, int $default = 0): int
{
    $value = filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT);
    if ($value === false || $value === null) {
        return $default;
    }
    return $value;
}

function input_float(string $key, float $default = 0.0): float
{
    $value = filter_input(INPUT_POST, $key, FILTER_VALIDATE_FLOAT);
    if ($value === false || $value === null) {
        return $default;
    }
    return $value;
}

function input_str(string $key, string $default = ''): string
{
    $value = filter_input(INPUT_POST, $key, FILTER_UNSAFE_RAW);
    if ($value === null) {
        return $default;
    }
    return trim($value);
}

function request_str(string $key, string $default = ''): string
{
    $value = filter_input(INPUT_GET, $key, FILTER_UNSAFE_RAW);
    if ($value === null) {
        return $default;
    }
    return trim($value);
}
