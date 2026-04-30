<?php require_once __DIR__ . '/../../includes/init.php'; require_admin(); ?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(($page_title ?? 'Admin Dashboard') . ' - ' . APP_NAME) ?></title>
    
    <!-- Premium Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= e(url('assets/admin/css/admin.css')) ?>">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Inject Theme script early to prevent flash -->
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
</head>
<body>
    <?php require __DIR__ . '/sidebar.php'; ?>

    <div class="main-wrapper">
        <header class="top-nav">
            <div class="nav-left">
                <button id="sidebar-toggle" title="Toggle Sidebar">
                    <i class="fas fa-bars-staggered"></i>
                </button>
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);"><?= $page_title ?? 'Dashboard' ?></h2>
            </div>
            
            <div class="nav-right" style="display: flex; align-items: center; gap: 1.25rem;">
                <a href="<?= e(url('index.php')) ?>" class="btn btn-secondary" style="padding: 0.5rem 0.75rem; border-radius: 10px;" title="Back to Website">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <button id="theme-toggle" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
                
                <div class="admin-profile" style="display: flex; align-items: center; gap: 0.85rem; padding-left: 1rem; border-left: 1px solid var(--border);">
                    <div class="admin-meta">
                        <div style="font-size: 0.875rem; font-weight: 700; color: var(--text-main);"><?= e(current_user()['name']) ?></div>
                        <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Super Admin</div>
                    </div>
                    <div style="width: 42px; height: 42px; background: linear-gradient(135deg, var(--primary), var(--primary-hover)); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #fff; box-shadow: 0 4px 6px rgba(99, 102, 241, 0.2);">
                        <?= strtoupper(substr(current_user()['name'], 0, 1)) ?>
                    </div>
                </div>
                
                <a href="<?= e(url('logout.php')) ?>" class="btn btn-secondary" style="padding: 0.5rem 0.75rem; border-radius: 10px;" title="Sign Out">
                    <i class="fas fa-arrow-right-from-bracket"></i>
                </a>
            </div>
        </header>
        <main class="page-content">
            <!-- Page Specific Content Starts -->
