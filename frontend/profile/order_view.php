<?php
declare(strict_types=1);
$page_title = 'Track Order';
require_once __DIR__ . '/includes/header.php';

$pdo = db();
$user_id = (int)$user['user_id'];
$order_id = (int)($_GET['id'] ?? 0);

if (!$order_id) {
    flash_set('error', 'Invalid order ID.');
    redirect('profile/orders.php');
}

// Fetch order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    flash_set('error', 'Order not found.');
    redirect('profile/orders.php');
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, f.name as food_name, f.image as food_image 
    FROM order_items oi 
    JOIN foods f ON oi.food_id = f.food_id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

// Timeline stages
$stages = [
    'Pending'   => 1,
    'Preparing' => 2,
    'Delivering'=> 3,
    'Completed' => 4,
    'Cancelled' => -1
];
$current_stage = $stages[$order['status']] ?? 1;
?>

<div style="margin-bottom: 2rem;">
    <a href="<?= e(url('profile/orders.php')) ?>" style="color: var(--text-muted); text-decoration: none; font-weight: 600; font-size: 0.875rem;">
        <i class="fas fa-arrow-left"></i> Back to History
    </a>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2.5rem;">
    <!-- Main Tracking Info -->
    <div>
        <!-- Timeline Card -->
        <div class="card">
            <div class="card-header">
                <h3 style="font-weight: 700; font-size: 1.125rem;">Order Tracking</h3>
                <span class="badge badge-warning">ID #<?= $order['order_id'] ?></span>
            </div>
            <div class="card-body">
                <?php if ($current_stage === -1): ?>
                    <div style="text-align: center; padding: 1rem;">
                        <div style="font-size: 3rem; color: var(--danger); margin-bottom: 1rem;"><i class="fas fa-times-circle"></i></div>
                        <h4 style="font-weight: 700; color: var(--danger);">Order Cancelled</h4>
                        <p style="color: var(--text-muted);">This order was cancelled. Please contact support for more information.</p>
                    </div>
                <?php else: ?>
                    <div style="display: flex; justify-content: space-between; position: relative; padding: 2rem 0;">
                        <!-- Progress Line -->
                        <div style="position: absolute; top: 50%; left: 10%; right: 10%; height: 4px; background: var(--border); transform: translateY(-50%); z-index: 1;">
                            <div style="width: <?= (($current_stage - 1) / 3) * 100 ?>%; height: 100%; background: var(--success); transition: width 0.5s ease;"></div>
                        </div>
                        
                        <?php 
                        $steps = [
                            ['label' => 'Placed', 'icon' => 'fa-receipt'],
                            ['label' => 'Preparing', 'icon' => 'fa-fire-burner'],
                            ['label' => 'On Way', 'icon' => 'fa-motorcycle'],
                            ['label' => 'Delivered', 'icon' => 'fa-home-check']
                        ];
                        foreach ($steps as $i => $step): 
                            $isActive = ($current_stage > $i);
                            $isCurrent = ($current_stage === $i + 1);
                        ?>
                            <div style="position: relative; z-index: 2; text-align: center; width: 80px;">
                                <div style="width: 48px; height: 48px; border-radius: 50%; background: <?= $isActive ? 'var(--success)' : 'var(--bg-card)' ?>; border: 4px solid <?= $isActive ? 'var(--success)' : 'var(--border)' ?>; color: <?= $isActive ? '#fff' : 'var(--text-muted)' ?>; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem; transition: all 0.3s;">
                                    <i class="fas <?= $step['icon'] ?>"></i>
                                </div>
                                <div style="font-size: 0.75rem; font-weight: <?= $isActive ? '700' : '500' ?>; color: <?= $isActive ? 'var(--text-main)' : 'var(--text-muted)' ?>;"><?= $step['label'] ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Order Items -->
        <div class="card">
            <div class="card-header"><h3 style="font-weight: 700; font-size: 1rem;">Order Items</h3></div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <?php if ($item['food_image']): ?>
                                        <img src="<?= e(url('uploads/' . $item['food_image'])) ?>" alt="" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover;">
                                    <?php endif; ?>
                                    <span style="font-weight: 600;"><?= e($item['food_name']) ?></span>
                                </div>
                            </td>
                            <td>$<?= number_format((float)$item['unit_price'], 2) ?></td>
                            <td>x <?= $item['quantity'] ?></td>
                            <td style="text-align: right; font-weight: 700;">$<?= number_format((float)$item['line_total'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right; font-weight: 700; padding: 1.5rem;">Grand Total</td>
                            <td style="text-align: right; font-weight: 800; font-size: 1.25rem; color: var(--brand-primary); padding: 1.5rem;">$<?= number_format((float)$order['total'], 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Side Details -->
    <div>
        <div class="card">
            <div class="card-header"><h4 style="font-weight: 700; font-size: 1rem;">Delivery Details</h4></div>
            <div class="card-body">
                <div style="margin-bottom: 1.5rem;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 0.5rem;">Delivery Address</div>
                    <div style="font-size: 0.9375rem; font-weight: 500; line-height: 1.5; color: var(--text-main);">
                        <?= nl2br(e($order['address'])) ?>
                    </div>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 0.5rem;">Payment Method</div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600;">
                        <i class="fas fa-credit-card" style="color: var(--brand-secondary);"></i> Cash on Delivery
                    </div>
                </div>
                <button class="btn btn-secondary" style="width: 100%; justify-content: center; font-size: 0.875rem;">
                    <i class="fas fa-download"></i> Download Receipt
                </button>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body" style="text-align: center;">
                <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem;">Something wrong with your order?</p>
                <a href="<?= e(url('contact.php')) ?>" class="btn btn-secondary" style="width: 100%; justify-content: center;">Report Issue</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
