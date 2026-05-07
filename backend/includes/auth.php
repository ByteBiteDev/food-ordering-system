<?php
declare(strict_types=1);

function is_logged_in(): bool
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['user_id']);
}

function current_user(): ?array
{
    if (!isset($_SESSION['user']['user_id'])) {
        return null;
    }
    
    // Fetch fresh data to ensure new columns (avatar, etc.) are available
    $stmt = db()->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user']['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        unset($_SESSION['user']);
        return null;
    }
    
    return $user;
}

function is_admin(): bool
{
    $user = current_user();
    return $user !== null && (($user['role'] ?? '') === 'admin');
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash_set('error', 'Please login to continue.');
        redirect('login.php');
    }
}

function require_admin(): void
{
    if (!is_logged_in() || !is_admin()) {
        flash_set('error', 'Admin access required.');
        redirect('login.php');
    }
}

