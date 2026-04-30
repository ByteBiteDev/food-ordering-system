<?php
declare(strict_types=1);
$page_title = 'Order Details';
require_once __DIR__ . '/includes/header.php';

$pdo = db();
$order_id = (int)($_GET['id'] ?? 0);

if (!$order_id) {
    flash_set('error', 'Invalid order ID.');
    redirect('admin/orders.php');
}

// Fetch order info
$stmt = $pdo->prepare("
    SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
    FROM orders o 
    JOIN users u ON o.user_id = u.user_id 
    WHERE o.order_id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    flash_set('error', 'Order not found.');
    redirect('admin/orders.php');
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $new_status = $_POST['status'] ?? '';
    
    if (in_array($new_status, ['Pending Payment', 'Pending', 'Completed', 'Cancelled'], true)) {
        $update = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $update->execute([$new_status, $order_id]);
        flash_set('success', 'Order status updated successfully.');
        redirect('admin/order_edit.php?id=' . $order_id);
    }
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, f.name as food_name 
    FROM order_items oi 
    JOIN foods f ON oi.food_id = f.food_id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();
?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
    <!-- Order Items -->
    <div class="card">
        <div class="card-header">
            <h3 style="font-size: 1rem; font-weight: 600;">Items in Order #<?= $order['order_id'] ?></h3>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Food Item</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e($item['food_name']) ?></td>
                        <td>$<?= number_format((float)$item['unit_price'], 2) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td>$<?= number_format((float)$item['line_total'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right; font-weight: 700; padding: 1.5rem;">Total Amount:</td>
                        <td style="font-weight: 700; font-size: 1.1rem; color: var(--primary);">$<?= number_format((float)$order['total'], 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Customer & Status -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <div class="card">
            <div class="card-header">
                <h3 style="font-size: 1rem; font-weight: 600;">Customer Information</h3>
            </div>
            <div style="padding: 1.5rem;">
                <div style="margin-bottom: 1rem;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">NAME</div>
                    <div style="font-weight: 500;"><?= e($order['customer_name']) ?></div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">EMAIL</div>
                    <div style="font-weight: 500;"><?= e($order['customer_email']) ?></div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">PHONE</div>
                    <div style="font-weight: 500;"><?= e($order['customer_phone']) ?></div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">SHIPPING ADDRESS</div>
                    <div style="font-weight: 500;"><?= nl2br(e($order['address'])) ?></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 style="font-size: 1rem; font-weight: 600;">Update Status</h3>
            </div>
            <div style="padding: 1.5rem;">
                <form method="POST">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label class="form-label">Order Status</label>
                        <select name="status" class="form-control" style="margin-bottom: 1rem;">
                            <option value="Pending Payment" <?= $order['status'] === 'Pending Payment' ? 'selected' : '' ?>>Pending Payment</option>
                            <option value="Pending" <?= $order['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Completed" <?= $order['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="Cancelled" <?= $order['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Update Status</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div style="margin-top: 2rem;">
    <a href="<?= e(url('admin/orders.php')) ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Orders
    </a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
