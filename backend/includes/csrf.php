<?php
declare(strict_types=1);

/**
 * Generate a CSRF token and store it in the session if it doesn't exist.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generate a hidden input field for CSRF protection.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Validate the CSRF token from a request.
 */
function csrf_validate(): bool
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            return false;
        }
    }
    return true;
}

/**
 * Require a valid CSRF token for POST requests, or abort with an error.
 */
function require_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_validate()) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}

/**
 * Alias for require_csrf for compatibility.
 */
function verify_csrf(): void
{
    require_csrf();
}
