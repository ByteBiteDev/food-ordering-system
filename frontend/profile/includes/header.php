<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/backend/includes/init.php';
require_login(); 
$user = current_user();

if (!$user) {
    redirect('login.php');
}

// Fetch unread notification count
$pdo = db();
$notif_count = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id = " . (int)$user['user_id'] . " AND is_read = 0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title ?? 'My Profile') ?> - Food Ordering System</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= e(url('assets/profile/css/profile.css')) ?>">
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
</head>
<body>
    <?php require __DIR__ . '/sidebar.php'; ?>

    <div class="main-content">
        <header class="top-nav">
            <div class="nav-left">
                <button id="sidebar-toggle" class="icon-square-btn" type="button" title="Toggle menu">
                    <i class="fas fa-bars"></i>
                </button>
                <h2 style="font-weight: 800; font-size: 1.25rem; color: var(--text-main);"><?= e($page_title ?? 'Dashboard') ?></h2>
            </div>
            
            <div class="nav-right">
                <button id="theme-toggle" class="icon-square-btn" type="button" title="Toggle theme">
                    <i class="fas fa-moon"></i>
                </button>

                <a href="<?= e(url('profile/notifications.php')) ?>" class="icon-square-btn notif-link" title="Notifications">
                    <i class="fas fa-bell fa-lg"></i>
                    <?php if ($notif_count > 0): ?>
                        <span class="notif-badge"><?= $notif_count ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="<?= e(url('profile/edit.php')) ?>" class="profile-trigger">
                    <div class="profile-trigger-meta">
                        <div style="font-size: 0.875rem; font-weight: 700; color: var(--text-main);"><?= e($user['name']) ?></div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">Customer</div>
                    </div>
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="<?= e(url('uploads/profile-images/' . $user['avatar'])) ?>" alt="" style="width: 40px; height: 40px; border-radius: 10px; object-fit: cover; border: 2px solid var(--border);">
                    <?php else: ?>
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: var(--primary-soft); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 800; border: 2px solid var(--border);">
                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </a>
                
                <a href="<?= e(url('index.php')) ?>" class="btn btn-primary" style="padding: 0.6rem 1.2rem; border-radius: 10px; font-weight: 700;">
                    <i class="fas fa-house"></i> <span class="d-none d-md-inline">Go to Shop</span>
                </a>
            </div>
        </header>
        <main class="page-container">
