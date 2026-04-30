<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-app-icon">
            <i class="fas fa-burger"></i>
        </div>
        <span class="sidebar-brand-title">Food Admin</span>
    </div>
    
    <nav class="sidebar-menu">
        <a href="<?= e(url('admin/index.php')) ?>" class="menu-item">
            <i class="fas fa-grid-2"></i>
            <span>Dashboard</span>
        </a>
        
        <div class="sidebar-section-title">Management</div>
        
        <a href="<?= e(url('admin/orders.php')) ?>" class="menu-item">
            <i class="fas fa-shopping-cart"></i>
            <span>Orders</span>
        </a>
        <a href="<?= e(url('admin/foods.php')) ?>" class="menu-item">
            <i class="fas fa-utensils"></i>
            <span>Food Items</span>
        </a>
        <a href="<?= e(url('admin/categories.php')) ?>" class="menu-item">
            <i class="fas fa-layer-group"></i>
            <span>Categories</span>
        </a>
        <a href="<?= e(url('admin/customers.php')) ?>" class="menu-item">
            <i class="fas fa-user-group"></i>
            <span>Customers</span>
        </a>
        
        <div class="sidebar-section-title">Operations</div>
        
        <a href="<?= e(url('admin/reports.php')) ?>" class="menu-item">
            <i class="fas fa-chart-pie"></i>
            <span>Sales Reports</span>
        </a>
        <a href="<?= e(url('admin/settings.php')) ?>" class="menu-item">
            <i class="fas fa-sliders"></i>
            <span>Settings</span>
        </a>
    </nav>
</aside>
