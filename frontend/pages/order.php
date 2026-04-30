<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/backend/includes/init.php';
require_login();

$orderId = int_from_request($_GET, 'order_id', 0);
if ($orderId <= 0) {
    redirect('orders.php');
}

$user = current_user();

if (is_admin()) {
    $stmt = db()->prepare('SELECT o.*, u.name AS customer_name, u.email AS customer_email
                           FROM orders o JOIN users u ON u.user_id = o.user_id
                           WHERE o.order_id = ?');
    $stmt->execute([$orderId]);
} else {
    $stmt = db()->prepare('SELECT * FROM orders WHERE order_id = ? AND user_id = ?');
    $stmt->execute([$orderId, (int) $user['user_id']]);
}
$order = $stmt->fetch();
if (!$order) {
    flash_set('error', 'Order not found.');
    redirect('orders.php');
}

$stmtItems = db()->prepare('SELECT oi.quantity, oi.unit_price, oi.line_total, f.name
                            FROM order_items oi
                            JOIN foods f ON f.food_id = oi.food_id
                            WHERE oi.order_id = ?
                            ORDER BY oi.order_item_id');
$stmtItems->execute([$orderId]);
$items = $stmtItems->fetchAll();

require APP_ROOT . '/backend/includes/layout_top.php';
?>

<div class="row" style="margin-bottom: 24px;">
    <div>
        <h1 style="margin:0 0 8px; font-size: 2.2rem;"><i class="ph ph-receipt"></i> Order #
            <?= (int) $order['order_id'] ?>
        </h1>
        <?php
        $statusColor = 'var(--text-secondary)';
        if ($order['status'] === 'Pending' || $order['status'] === 'Pending Payment')
            $statusColor = '#eab308';
        elseif ($order['status'] === 'Delivered' || $order['status'] === 'Completed')
            $statusColor = 'var(--success)';
        elseif ($order['status'] === 'Cancelled')
            $statusColor = 'var(--danger)';
        ?>
        <div class="muted" style="display:flex; align-items:center; gap:8px;">
            Status:
            <span
                style="display:inline-flex; align-items:center; gap:6px; color:<?= $statusColor ?>; font-weight:600; background:rgba(0,0,0,0.05); padding:4px 10px; border-radius:999px; font-size:0.85rem;">
                <span style="width:8px; height:8px; border-radius:50%; background:currentColor;"></span>
                <?= e((string) $order['status']) ?>
            </span>
        </div>
    </div>
    <?php if (is_admin()): ?>
        <a class="btn btn--primary" href="<?= e(url('admin/order_edit.php?order_id=' . (int) $order['order_id'])) ?>"><i
                class="ph ph-pencil-simple"></i> Admin: Update Status</a>
    <?php elseif (($order['status'] ?? '') === 'Pending Payment'): ?>
        <a class="btn btn-primary"
            href="<?= e(url('payment.php?order_id=' . (int) $order['order_id'] . '&method=' . urlencode((string) ($order['payment_method'] ?? 'telebirr')))) ?>">Pay
            Now</a>
    <?php endif; ?>
</div>

<div class="grid" style="grid-template-columns:1.5fr 1fr; gap: 24px; align-items: start;">
    <div class="card" style="padding: 0; overflow: hidden;">
        <h2 style="margin: 24px 24px 16px; font-size: 1.5rem; display:flex; align-items:center; gap:8px;">
            <i class="ph ph-list-dashes" style="color:var(--brand-primary);"></i> Items
        </h2>
        <div style="overflow-x:auto;">
            <table style="margin: 0;">
                <thead style="background: var(--bg-secondary);">
                    <tr>
                        <th style="padding: 12px 24px; border-bottom: 1px solid var(--border-light);">Item</th>
                        <th
                            style="padding: 12px 24px; border-bottom: 1px solid var(--border-light); text-align:center;">
                            Qty</th>
                        <th style="padding: 12px 24px; border-bottom: 1px solid var(--border-light); text-align:right;">
                            Unit</th>
                        <th style="padding: 12px 24px; border-bottom: 1px solid var(--border-light); text-align:right;">
                            Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td
                                style="padding: 16px 24px; font-weight: 500; border: none; border-bottom: 1px solid var(--border-light); background: transparent;">
                                <?= e((string) $it['name']) ?>
                            </td>
                            <td
                                style="padding: 16px 24px; text-align:center; border: none; border-bottom: 1px solid var(--border-light); background: transparent;">
                                <?= (int) $it['quantity'] ?>
                            </td>
                            <td
                                style="padding: 16px 24px; text-align:right; border: none; border-bottom: 1px solid var(--border-light); background: transparent;">
                                KSh
                                <?= number_format((float) $it['unit_price'], 2) ?>
                            </td>
                            <td
                                style="padding: 16px 24px; text-align:right; border: none; border-bottom: 1px solid var(--border-light); background: transparent; font-weight:700;">
                                KSh
                                <?= number_format((float) $it['line_total'], 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="row"
            style="padding: 24px; justify-content: flex-end; border-top: 1px solid var(--border-light); background: var(--bg-secondary);">
            <div class="muted" style="font-size:1.1rem; margin-right: 16px;">Total Amount</div>
            <div
                style="font-family:'Outfit', sans-serif; font-size:1.8rem; font-weight:800; color:var(--text-primary);">
                KSh
                <?= number_format((float) $order['total'], 2) ?>
            </div>
        </div>
    </div>

    <div class="card" style="padding: 32px;">
        <h2 style="margin: 0 0 20px; font-size: 1.5rem; display:flex; align-items:center; gap:8px;">
            <i class="ph ph-map-pin-line" style="color:var(--brand-secondary);"></i> Delivery Details
        </h2>

        <div style="background:var(--bg-secondary); border-radius:12px; padding:20px; margin-bottom:20px;">
            <?php if (isset($order['customer_name'])): ?>
                <div style="margin-bottom:16px; border-bottom:1px solid var(--border-light); padding-bottom:16px;">
                    <div class="muted"
                        style="font-size:0.9rem; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">
                        Customer</div>
                    <div style="font-weight:600; font-size:1.1rem;"><i class="ph ph-user"></i>
                        <?= e((string) $order['customer_name']) ?>
                    </div>
                    <div class="muted" style="font-size:0.95rem; margin-top:4px;"><i class="ph ph-envelope"></i>
                        <?= e((string) $order['customer_email']) ?>
                    </div>
                </div>
            <?php endif; ?>

            <div style="margin-bottom:16px; border-bottom:1px solid var(--border-light); padding-bottom:16px;">
                <div class="muted"
                    style="font-size:0.9rem; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.05em;">
                    Shipping Address</div>
                <div style="line-height:1.6;"><i class="ph ph-house"></i>
                    <?= nl2br(e((string) $order['address'])) ?>
                </div>
            </div>

            <?php if (!empty($order['payment_method'])): ?>
                <div style="margin-bottom:16px; border-bottom:1px solid var(--border-light); padding-bottom:16px;">
                    <div class="muted"
                        style="font-size:0.9rem; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.05em;">
                        Payment Method</div>
                    <div style="font-weight:500; display:flex; align-items:center; gap:8px;">
                        <?php if ($order['payment_method'] === 'telebirr'): ?>
                            <img src="assets/img/telebirr-logo.svg" alt="TeleBirr" style="height:24px; width:auto;">
                            TeleBirr
                        <?php elseif ($order['payment_method'] === 'chapa'): ?>
                            <img src="assets/img/chapa-logo.svg" alt="Chapa" style="height:24px; width:auto;">
                            Chapa
                        <?php else: ?>
                            <i class="ph ph-credit-card"></i>
                            <?= e(ucfirst((string) $order['payment_method'])) ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div>
                <div class="muted"
                    style="font-size:0.9rem; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.05em;">Order
                    Placed</div>
                <div style="font-weight:500;"><i class="ph ph-calendar-blank"></i>
                    <?= e((string) $order['created_at']) ?>
                </div>
            </div>
        </div>

        <div style="text-align: center;">
            <a class="btn" href="<?= e(url('orders.php')) ?>" style="width:100%;"><i class="ph ph-arrow-left"></i> Back
                to All Orders</a>
        </div>
    </div>
</div>

<?php require APP_ROOT . '/backend/includes/layout_bottom.php'; ?>