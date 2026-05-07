<?php
declare(strict_types=1);

if (!defined('APP_ROOT')) {
    $root = realpath(__DIR__ . '/../..');
    define('APP_ROOT', $root !== false ? $root : dirname(__DIR__, 2));
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/flash.php';
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/csrf.php';

date_default_timezone_set(APP_TIMEZONE);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}

if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || strlen($_SESSION['csrf_token']) < 20) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
