<?php
declare(strict_types=1);

function flash_set(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }
    $message = (string)$_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $message;
}

