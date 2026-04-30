<?php
declare(strict_types=1);
$page_title = 'Order Management';
require_once __DIR__ . '/includes/header.php';

$pdo = db();
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$query = "
    SELECT o.*, u.name as customer_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.user_id 
    WHERE 1=1
";
$params = [];

if ($status_filter) {
    $query .= " AND o.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $query .= " AND (u.name LIKE ? OR o.order_id = ?)";
    $params[] = "%$search%";
    $params[] = (int)$search;
}

$query .= " ORDER BY o.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <h3 style="font-size: 1rem; font-weight: 600;">Filters & Search</h3>
    </div>
    <div style="padding: 1.5rem;">
        <form method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                <label class="form-label">Search Customer/ID</label>
                <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= e($search) ?>">
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 150px;">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="Pending Payment" <?= $status_filter === 'Pending Payment' ? 'selected' : '' ?>>Pending Payment</option>
                    <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Completed" <?= $status_filter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="Cancelled" <?= $status_filter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="<?= e(url('admin/orders.php')) ?>" class="btn btn-secondary">Reset</a>
        </form>
    </div>
</div>

<div class="page-header">
    <div class="page-title">
        <h2>Order Management</h2>
        <p>Track and manage customer orders in real-time.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>All Orders</h3>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">No orders found.</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?= $order['order_id'] ?></td>
                    <td><?= e($order['customer_name']) ?></td>
                    <td>$<?= number_format((float)$order['total'], 2) ?></td>
                    <td>
                        <span class="badge badge-<?= strtolower(str_replace(' ', '-', (string)$order['status'])) ?>">
                            <?= e($order['status']) ?>
                        </span>
                    </td>
                    <td><?= date('M d, Y H:i', strtotime($order['created_at'])) ?></td>
                    <td>
                        <a href="<?= e(url('admin/order_edit.php?id=' . $order['order_id'])) ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
