<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function project_base_url(): string
{
    static $base = null;
    if (is_string($base)) {
        return $base;
    }

    $projectRoot = defined('APP_ROOT') ? realpath(APP_ROOT) : realpath(__DIR__ . '/..');
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $docRootReal = $docRoot !== '' ? realpath($docRoot) : false;

    if ($projectRoot && $docRootReal && str_starts_with($projectRoot, $docRootReal)) {
        $rel = substr($projectRoot, strlen($docRootReal));
        $rel = str_replace('\\', '/', $rel);
        $base = '/' . trim($rel, '/');
        if ($base === '/') {
            $base = '';
        }
        return $base;
    }

    // Fallback: best-effort based on script path
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = rtrim(dirname($script), '/');
    if (str_ends_with($dir, '/admin')) {
        $dir = substr($dir, 0, -strlen('/admin'));
    }
    $base = $dir === '/' ? '' : $dir;
    return $base;
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    $base = project_base_url();
    return $base . ($path === '' ? '/' : '/' . $path);
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

// Removed CSRF functions as they are now in includes/csrf.php

function int_from_request(array $source, string $key, int $default = 0): int
{
    $value = $source[$key] ?? null;
    if (is_string($value) && preg_match('/^\d+$/', $value)) {
        return (int)$value;
    }
    if (is_int($value)) {
        return $value;
    }
    return $default;
}

function get_food_image_url(?string $image): string
{
    $image = trim((string)$image);
    if ($image === '') {
        return url('assets/img/food-placeholder.svg');
    }

    $image = str_replace('\\', '/', $image);

    if (preg_match('~^https?://~i', $image)) {
        return $image;
    }

    // Guard: some demo/seed data may accidentally point at non-food images.
    // If a known "phone mockup" filename is used, show a real food photo instead.
    $baseName = strtolower(basename($image));
    if (in_array($baseName, [
        'food_69ef8ec6c0af3.jpg',
        'food_20260426_040544_bd95ca44_photo-1511707171634-5f897ff02aa9222.jpg.jpg',
    ], true)) {
        return 'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?auto=format&fit=crop&w=1200&q=80';
    }

    $base = project_base_url(); // e.g. '' or '/food-ordering-system'

    // If the DB stored an absolute site path, keep it (when it already includes our base).
    if (str_starts_with($image, '/')) {
        if ($base !== '' && str_starts_with($image, $base . '/')) {
            return $image;
        }
        return url(ltrim($image, '/'));
    }

    // If the DB stored a path like "uploads/foods/xyz.jpg", don't double-prefix it.
    if (preg_match('~^(uploads/|assets/|images/|img/)~i', $image)) {
        return url($image);
    }

    // If it already contains an uploads path somewhere, best-effort normalize.
    if (str_contains($image, 'uploads/foods/')) {
        $pos = strpos($image, 'uploads/foods/');
        $normalized = substr($image, $pos);
        return url($normalized);
    }

    // Default: treat as a bare filename stored in the DB.
    return url('uploads/foods/' . $image);
}

function render_premium_card(array $food): void
{
    $rating = (float)($food['rating'] ?? 4.5);
    $reviews = (int)($food['reviews'] ?? rand(50, 500));
    $isFavorite = !empty($food['is_favorite']);
    $isNew = ($food['food_id'] ?? 0) > 15;
    $isTrending = ($food['order_count'] ?? 0) > 10;
    $price = (float)($food['price'] ?? 0);
    $originalPrice = isset($food['original_price']) ? (float)$food['original_price'] : null;
    ?>
    <div class="food-card">
        <div class="food-image-container">
            <img
                src="<?= e(get_food_image_url($food['image'] ?? '')) ?>"
                onerror="this.onerror=null;this.src='<?= e(url('assets/img/food-placeholder.svg')) ?>';"
                alt="<?= e($food['name']) ?>"
                class="food-image"
                loading="lazy"
            />
            
            <div class="card-badges">
                <?php if ($isNew): ?>
                    <span class="badge-tag badge-new">New</span>
                <?php endif; ?>
                <?php if ($isTrending): ?>
                    <span class="badge-tag badge-trending">Trending</span>
                <?php endif; ?>
            </div>

            <button class="fav-btn glass <?= $isFavorite ? 'active' : '' ?>" onclick="toggleFavorite(<?= (int)($food['food_id'] ?? 0) ?>, this)">
                <i class="<?= $isFavorite ? 'fas' : 'far' ?> fa-heart"></i>
            </button>
            <div class="rating-tag">
                <i class="fas fa-star"></i>
                <span><?= number_format((float)$rating, 1) ?></span>
            </div>
        </div>

        <div class="food-info">
            <div class="food-header">
                <h3 class="food-title"><?= e($food['name']) ?></h3>
                <div class="food-meta">
                    <span class="food-category"><?= e($food['category_name'] ?? 'Food') ?></span>
                    <span class="food-reviews">
                        <i class="fas fa-comment"></i>
                        <?= number_format($reviews) ?>
                    </span>
                </div>
            </div>

            <p class="food-description">
                <?= e(substr($food['description'] ?? 'Delicious and fresh food made with premium ingredients.', 0, 80)) ?>...
            </p>

            <div class="food-footer">
                <div class="price-section">
                    <span class="price">ETB <?= number_format($price, 0) ?></span>
                    <?php if ($originalPrice !== null && $originalPrice > $price): ?>
                        <span class="original-price">ETB <?= number_format($originalPrice, 0) ?></span>
                    <?php endif; ?>
                </div>

                <div class="action-buttons">
                    <button class="btn-icon" onclick="addToCart(<?= (int)($food['food_id'] ?? 0) ?>, 1)">
                        <i class="fas fa-cart-plus"></i>
                    </button>
                    <a href="<?= e(url('food_details.php?food_id=' . $food['food_id'])) ?>" class="btn-icon">
                        <i class="fas fa-eye"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function render_skeleton_card(): void
{
    ?>
    <div class="food-card skeleton-card">
        <div class="food-image-container skeleton"></div>
        <div class="food-info">
            <div class="skeleton" style="height: 24px; width: 80%; margin-bottom: 12px;"></div>
            <div class="skeleton" style="height: 16px; width: 60%; margin-bottom: 8px;"></div>
            <div class="skeleton" style="height: 16px; width: 40%; margin-bottom: 24px;"></div>
            <div class="food-footer">
                <div class="skeleton" style="height: 32px; width: 60px;"></div>
                <div class="skeleton" style="height: 40px; width: 40px; border-radius: 12px;"></div>
            </div>
        </div>
    </div>
    <?php
}
