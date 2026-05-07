<?php
declare(strict_types=1);
require_once __DIR__ . '/init.php';

$user = current_user();
$cartCount = (int)array_sum($_SESSION['cart'] ?? []);
$flashError = flash_get('error');
$flashSuccess = flash_get('success');

// Fetch notifications if logged in
$notifCount = 0;
if ($user) {
    $notifCount = (int)db()->query("SELECT COUNT(*) FROM notifications WHERE user_id = {$user['user_id']} AND is_read = 0")->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title ?? APP_NAME) ?></title>
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    <script>
      (function () {
        try {
          var stored = localStorage.getItem('theme');
          var theme = (stored === 'dark' || stored === 'light') ? stored : 'light';
          document.documentElement.setAttribute('data-theme', theme);
        } catch (e) {}
      })();
    </script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
    <?php if (empty($hide_nav)): ?>
    <header class="topbar" id="navbar">
        <div class="container topbar__inner">
            <a class="brand" href="<?= e(url('index.php')) ?>">
                <i class="fas fa-burger"></i> <?= e(APP_NAME) ?>
            </a>
            
            <nav class="nav">
                <a href="<?= e(url('index.php')) ?>" class="nav-link <?= str_contains($_SERVER['PHP_SELF'], 'index.php') ? 'active' : '' ?>">Home</a>
                <a href="<?= e(url('food.php')) ?>" class="nav-link <?= str_contains($_SERVER['PHP_SELF'], 'food.php') ? 'active' : '' ?>">Menu</a>
                <a href="<?= e(url('about.php')) ?>" class="nav-link <?= str_contains($_SERVER['PHP_SELF'], 'about.php') ? 'active' : '' ?>">About</a>
                <a href="<?= e(url('contact.php')) ?>" class="nav-link <?= str_contains($_SERVER['PHP_SELF'], 'contact.php') ? 'active' : '' ?>">Contact</a>
                
                <?php if (is_admin()): ?>
                    <a href="<?= e(url('admin/index.php')) ?>" class="nav-link" style="color: var(--brand-primary);"><i class="fas fa-shield-halved"></i> Admin</a>
                <?php endif; ?>
            </nav>

            <div class="nav-actions">
                <button type="button" class="icon-btn theme-toggle" id="themeToggle" aria-label="Toggle theme" title="Toggle theme">
                    <i class="fas fa-moon"></i>
                </button>

                <a href="<?= e(url('cart.php')) ?>" class="icon-btn">
                    <i class="fas fa-shopping-basket"></i>
                    <?php if ($cartCount > 0): ?>
                        <span class="badge"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>

                <?php if ($user): ?>
                    <a href="<?= e(url('profile/notifications.php')) ?>" class="icon-btn">
                        <i class="fas fa-bell"></i>
                        <?php if ($notifCount > 0): ?>
                            <span class="badge"><?= $notifCount ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <a href="<?= e(url(is_admin() ? 'admin/index.php' : 'profile/index.php')) ?>" class="icon-btn" title="<?= is_admin() ? 'Admin Dashboard' : 'My Account' ?>">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?= e(url('uploads/profile-images/' . $user['avatar'])) ?>" alt="">
                        <?php else: ?>
                            <i class="fas fa-<?= is_admin() ? 'shield-halved' : 'user-circle' ?>"></i>
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <div style="margin-left: 0.5rem;">
                        <a href="<?= e(url('login.php')) ?>" class="btn btn-primary" style="padding: 0.6rem 1.5rem;">Sign In</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <?php endif; ?>

    <main style="min-height: 70vh;">
        <div class="container" style="padding-top: 1.5rem;">
            <?php if ($flashError): ?>
                <div class="alert alert--error" style="border-radius: 12px; margin-bottom: 2rem;"><i class="fas fa-circle-exclamation"></i> <?= e($flashError) ?></div>
            <?php endif; ?>
            <?php if ($flashSuccess): ?>
                <div class="alert alert--success" style="border-radius: 12px; margin-bottom: 2rem;"><i class="fas fa-circle-check"></i> <?= e($flashSuccess) ?></div>
            <?php endif; ?>
        </div>
