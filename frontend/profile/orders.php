<?php
declare(strict_types=1);
$page_title = 'My Order History';
require_once __DIR__ . '/includes/header.php';

$pdo = db();
$user_id = (int)$user['user_id'];
$status_filter = $_GET['status'] ?? '';

$query = "SELECT * FROM orders WHERE user_id = ?";
$params = [$user_id];

if ($status_filter) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h3 style="font-weight: 700; margin-bottom: 0.25rem;">Your Orders</h3>
        <p style="color: var(--text-muted); font-size: 0.875rem;">Track and manage your recent food adventures.</p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <a href="?status=" class="btn <?= $status_filter === '' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 0.5rem 1rem;">All</a>
        <a href="?status=Pending%20Payment" class="btn <?= $status_filter === 'Pending Payment' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 0.5rem 1rem;">Pending Payment</a>
        <a href="?status=Pending" class="btn <?= $status_filter === 'Pending' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 0.5rem 1rem;">Pending</a>
        <a href="?status=Completed" class="btn <?= $status_filter === 'Completed' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 0.5rem 1rem;">Completed</a>
        <a href="?status=Cancelled" class="btn <?= $status_filter === 'Cancelled' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 0.5rem 1rem;">Cancelled</a>
    </div>
</div>

<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <?php if (empty($orders)): ?>
        <div class="card" style="padding: 4rem; text-align: center; border-style: dashed;">
            <div style="font-size: 4rem; color: var(--border); margin-bottom: 1.5rem;"><i class="fas fa-receipt"></i></div>
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">No orders found</h4>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Looks like you haven't placed any orders matching this filter.</p>
            <a href="<?= e(url('index.php')) ?>" class="btn btn-primary">Start Ordering</a>
        </div>
    <?php endif; ?>

    <?php foreach ($orders as $order): ?>
        <div class="card" style="margin-bottom: 0;">
            <div style="padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
                <div style="display: flex; gap: 1.5rem; align-items: center;">
                    <div style="width: 60px; height: 60px; border-radius: 15px; background: var(--primary-soft); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div>
                        <div style="font-weight: 800; font-size: 1.125rem;">Order #<?= $order['order_id'] ?></div>
                        <div style="font-size: 0.875rem; color: var(--text-muted); margin-top: 0.25rem;">
                            <i class="fas fa-calendar-alt" style="margin-right: 5px;"></i> <?= date('M d, Y at H:i', strtotime($order['created_at'])) ?>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 3rem; align-items: center;">
                    <div style="text-align: center;">
                        <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 0.25rem;">Status</div>
                        <span class="badge badge-<?= $order['status'] === 'Completed' ? 'success' : ($order['status'] === 'Cancelled' ? 'danger' : 'warning') ?>">
                            <?= e($order['status']) ?>
                        </span>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 0.25rem;">Total</div>
                        <div style="font-weight: 800; font-size: 1.125rem; color: var(--text-main);">$<?= number_format((float)$order['total'], 2) ?></div>
                    </div>
                    <div style="display: flex; gap: 0.75rem;">
                        <a href="<?= e(url('profile/order_view.php?id=' . $order['order_id'])) ?>" class="btn btn-secondary">Track / View</a>
                        <button class="btn btn-primary" onclick="alert('Reordering feature coming soon!')">Reorder</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
