<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-burger"></i>
        <span><?= e(APP_NAME) ?></span>
    </div>
    
    <nav class="sidebar-menu">
        <a href="<?= e(url('profile/index.php')) ?>" class="menu-item <?= str_contains($_SERVER['PHP_SELF'], 'index.php') ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="<?= e(url('profile/orders.php')) ?>" class="menu-item <?= str_contains($_SERVER['PHP_SELF'], 'orders.php') ? 'active' : '' ?>">
            <i class="fas fa-shopping-bag"></i>
            <span>My Orders</span>
        </a>
        <a href="<?= e(url('profile/favorites.php')) ?>" class="menu-item <?= str_contains($_SERVER['PHP_SELF'], 'favorites.php') ? 'active' : '' ?>">
            <i class="fas fa-heart"></i>
            <span>Favorites</span>
        </a>
        <a href="<?= e(url('profile/addresses.php')) ?>" class="menu-item <?= str_contains($_SERVER['PHP_SELF'], 'addresses.php') ? 'active' : '' ?>">
            <i class="fas fa-map-marker-alt"></i>
            <span>Saved Addresses</span>
        </a>
        <a href="<?= e(url('profile/notifications.php')) ?>" class="menu-item <?= str_contains($_SERVER['PHP_SELF'], 'notifications.php') ? 'active' : '' ?>">
            <i class="fas fa-bell"></i>
            <span>Notifications</span>
        </a>
        
        <div class="sidebar-section-title">Account</div>
        
        <a href="<?= e(url('profile/edit.php')) ?>" class="menu-item <?= str_contains($_SERVER['PHP_SELF'], 'edit.php') ? 'active' : '' ?>">
            <i class="fas fa-user-circle"></i>
            <span>Personal Info</span>
        </a>
        <a href="<?= e(url('profile/security.php')) ?>" class="menu-item <?= str_contains($_SERVER['PHP_SELF'], 'security.php') ? 'active' : '' ?>">
            <i class="fas fa-shield-alt"></i>
            <span>Security</span>
        </a>
        
        <a href="<?= e(url('logout.php')) ?>" class="menu-item menu-item--danger">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>
</aside>
