<?php
declare(strict_types=1);
$page_title = 'My Dashboard';
require_once __DIR__ . '/includes/header.php';

$pdo = db();
$user_id = (int)$user['user_id'];

// Stats
$stats = [
    'total'     => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE user_id = $user_id")->fetchColumn(),
    'active'    => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE user_id = $user_id AND status IN ('Pending Payment', 'Pending', 'Preparing')")->fetchColumn(),
    'completed' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE user_id = $user_id AND status = 'Completed'")->fetchColumn(),
    'favorites' => (int)$pdo->query("SELECT COUNT(*) FROM favorites WHERE user_id = $user_id")->fetchColumn(),
];

// Profile completion calculation
$completion_score = 0;
if (!empty($user['name'])) $completion_score += 25;
if (!empty($user['phone'])) $completion_score += 25;
if (!empty($user['avatar'])) $completion_score += 25;
if ($pdo->query("SELECT COUNT(*) FROM addresses WHERE user_id = $user_id")->fetchColumn() > 0) $completion_score += 25;

// Recent Orders
$recent_orders = $pdo->query("
    SELECT * FROM orders 
    WHERE user_id = $user_id 
    ORDER BY created_at DESC 
    LIMIT 3
")->fetchAll();

// Recommended items based on favorites (random from same category if favorites exist)
$recommendations = $pdo->query("
    SELECT f.* FROM foods f 
    WHERE f.status = 1 
    ORDER BY RAND() 
    LIMIT 4
")->fetchAll();
?>

<!-- Welcome Banner -->
<div class="welcome-banner">
    <div class="welcome-text">
        <h1>Welcome back, <?= e(explode(' ', $user['name'])[0]) ?>!</h1>
        <p>Hungry for something delicious? Check out your recent favorites or track your current orders.</p>
        <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
            <a href="<?= e(url('index.php')) ?>" class="btn" style="background: #fff; color: var(--brand-primary);">Order Now</a>
            <a href="<?= e(url('profile/orders.php')) ?>" class="btn" style="background: rgba(255,255,255,0.2); color: #fff; border: 1px solid rgba(255,255,255,0.4);">View Orders</a>
        </div>
    </div>
    <div style="display: none; display: lg-block;">
        <i class="fas fa-burger-cheese" style="font-size: 8rem; opacity: 0.2; color: #fff;"></i>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-shopping-basket"></i></div>
        <div class="stat-info">
            <div class="value"><?= $stats['total'] ?></div>
            <div class="label">Total Orders</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(243, 156, 18, 0.1); color: var(--warning);"><i class="fas fa-truck-loading"></i></div>
        <div class="stat-info">
            <div class="value"><?= $stats['active'] ?></div>
            <div class="label">Active Orders</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(46, 204, 113, 0.1); color: var(--success);"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <div class="value"><?= $stats['completed'] ?></div>
            <div class="label">Delivered</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(231, 76, 60, 0.1); color: var(--error);"><i class="fas fa-heart"></i></div>
        <div class="stat-info">
            <div class="value"><?= $stats['favorites'] ?></div>
            <div class="label">Saved Items</div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2.5rem;">
    <!-- Recent Activity -->
    <div>
        <h3 style="font-weight: 700; margin-bottom: 1.5rem;">Recent Order Activity</h3>
        <?php if (empty($recent_orders)): ?>
            <div class="card" style="padding: 3rem; text-align: center; border-style: dashed;">
                <div style="font-size: 3rem; color: var(--border); margin-bottom: 1rem;"><i class="fas fa-history"></i></div>
                <p style="color: var(--text-muted);">No orders yet. Start your first culinary journey!</p>
                <a href="<?= e(url('index.php')) ?>" class="btn btn-primary" style="margin-top: 1rem;">Browse Menu</a>
            </div>
        <?php else: ?>
            <?php foreach ($recent_orders as $order): ?>
                <div class="card" style="margin-bottom: 1rem;">
                    <div style="padding: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 48px; height: 48px; border-radius: 12px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: var(--primary);">
                                <i class="fas fa-box"></i>
                            </div>
                            <div>
                                <div style="font-weight: 700;">Order #<?= $order['order_id'] ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);"><?= date('F d, Y', strtotime($order['created_at'])) ?></div>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-weight: 700; color: var(--primary); margin-bottom: 0.25rem;">$<?= number_format((float)$order['total'], 2) ?></div>
                            <span class="badge badge-<?= $order['status'] === 'Completed' ? 'success' : 'warning' ?>">
                                <?= e($order['status']) ?>
                            </span>
                        </div>
                        <a href="<?= e(url('profile/order_view.php?id=' . $order['order_id'])) ?>" class="btn btn-secondary" style="padding: 0.5rem 1rem;">Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
            <a href="<?= e(url('profile/orders.php')) ?>" style="display: block; text-align: center; color: var(--primary); font-weight: 600; text-decoration: none; margin-top: 1rem;">View Full History</a>
        <?php endif; ?>
    </div>

    <!-- Sidebar Info -->
    <div>
        <div class="card">
            <div class="card-header"><h4 style="font-weight: 700; font-size: 1rem;">Profile Completion</h4></div>
            <div class="card-body">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.875rem;">
                    <span style="font-weight: 600;"><?= $completion_score ?>% Complete</span>
                </div>
                <div style="height: 8px; background: var(--bg-main); border-radius: 4px; overflow: hidden; margin-bottom: 1.5rem;">
                    <div style="width: <?= $completion_score ?>%; height: 100%; background: var(--success); transition: width 0.5s ease;"></div>
                </div>
                <ul style="list-style: none; font-size: 0.875rem; color: var(--text-muted);">
                    <li style="margin-bottom: 0.5rem;"><i class="fas fa-check-circle" style="color: var(--success); margin-right: 8px;"></i> Account Verified</li>
                    <li style="margin-bottom: 0.5rem;"><i class="<?= !empty($user['phone']) ? 'fa-check-circle' : 'fa-circle' ?>" style="margin-right: 8px; color: <?= !empty($user['phone']) ? 'var(--success)' : 'var(--border)' ?>;"></i> Add Phone Number</li>
                    <li style="margin-bottom: 0.5rem;"><i class="<?= !empty($user['avatar']) ? 'fa-check-circle' : 'fa-circle' ?>" style="margin-right: 8px; color: <?= !empty($user['avatar']) ? 'var(--success)' : 'var(--border)' ?>;"></i> Upload Avatar</li>
                    <li><i class="<?= $completion_score >= 100 ? 'fa-check-circle' : 'fa-circle' ?>" style="margin-right: 8px; color: <?= $completion_score >= 100 ? 'var(--success)' : 'var(--border)' ?>;"></i> Add Delivery Address</li>
                </ul>
                <a href="<?= e(url('profile/edit.php')) ?>" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; justify-content: center;">Complete Profile</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h4 style="font-weight: 700; font-size: 1rem;">Need Help?</h4></div>
            <div class="card-body" style="text-align: center;">
                <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem;">Having trouble with an order? Our support team is here 24/7.</p>
                <a href="<?= e(url('contact.php')) ?>" class="btn btn-secondary" style="width: 100%; justify-content: center;">Contact Support</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
