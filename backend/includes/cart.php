<?php
declare(strict_types=1);

function cart_items(): array
{
    $cart = $_SESSION['cart'] ?? [];
    return is_array($cart) ? $cart : [];
}

function cart_count(): int
{
    return (int)array_sum(cart_items());
}

function cart_add(int $foodId, int $quantity): void
{
    if ($foodId <= 0 || $quantity <= 0) {
        return;
    }
    $cart = cart_items();
    $cart[$foodId] = (int)($cart[$foodId] ?? 0) + $quantity;
    $_SESSION['cart'] = $cart;
}

function cart_set(int $foodId, int $quantity): void
{
    if ($foodId <= 0) {
        return;
    }
    $cart = cart_items();
    if ($quantity <= 0) {
        unset($cart[$foodId]);
    } else {
        $cart[$foodId] = $quantity;
    }
    $_SESSION['cart'] = $cart;
}

function cart_clear(): void
{
    $_SESSION['cart'] = [];
}

