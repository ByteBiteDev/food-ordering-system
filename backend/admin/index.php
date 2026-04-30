<?php
declare(strict_types=1);
$page_title = 'Dashboard Overview';
require_once __DIR__ . '/includes/header.php';

$pdo = db();

// Fetch summary stats
$stats = [
    'total_orders'    => (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'pending_orders'  => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn(),
    'completed_orders'=> (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Completed'")->fetchColumn(),
    'total_revenue'   => (float)$pdo->query("SELECT SUM(total) FROM orders WHERE status = 'Completed'")->fetchColumn(),
    'total_customers' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn(),
    'total_foods'     => (int)$pdo->query("SELECT COUNT(*) FROM foods")->fetchColumn(),
];

// Fetch recent orders
$recent_orders = $pdo->query("
    SELECT o.*, u.name as customer_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.user_id 
    ORDER BY o.created_at DESC 
    LIMIT 5
")->fetchAll();

// Fetch revenue data for last 7 days for the chart
$revenue_data = $pdo->query("
    SELECT DATE(created_at) as date, SUM(total) as daily_revenue 
    FROM orders 
    WHERE status = 'Completed' 
    AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
")->fetchAll();

$chart_labels = [];
$chart_values = [];
// Fill in gaps for dates with no revenue
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('M d', strtotime($date));
    $found = false;
    foreach ($revenue_data as $row) {
        if ($row['date'] === $date) {
            $chart_values[] = (float)$row['daily_revenue'];
            $found = true;
            break;
        }
    }
    if (!$found) $chart_values[] = 0;
}
?>
<div class="page-header">
    <div class="page-title">
        <h2>Dashboard Overview</h2>
        <p>Monitor your cafe's performance and orders in real-time.</p>
    </div>
    <div class="page-actions">
        <a href="<?= e(url('admin/reports.php')) ?>" class="btn btn-secondary">
            <i class="fas fa-chart-line"></i> View Reports
        </a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-title">Total Revenue</div>
        <div class="stat-card-value">$<?= number_format($stats['total_revenue'], 2) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-title">Active Orders</div>
        <div class="stat-card-value"><?= $stats['pending_orders'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-title">Customers</div>
        <div class="stat-card-value"><?= $stats['total_customers'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-title">Menu Items</div>
        <div class="stat-card-value"><?= $stats['total_foods'] ?></div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Revenue Chart -->
    <div class="card">
        <div class="card-header">
            <h3>Revenue Analytics (Last 7 Days)</h3>
        </div>
        <div style="padding: 1.5rem;">
            <canvas id="revenueChart" height="250"></canvas>
        </div>
    </div>

    <!-- Order Summary -->
    <div class="card">
        <div class="card-header">
            <h3>Order Distribution</h3>
        </div>
        <div style="padding: 1.5rem;">
            <canvas id="orderChart" height="250"></canvas>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Recent Orders</h3>
        <a href="<?= e(url('admin/orders.php')) ?>" class="btn btn-secondary btn-sm">View All</a>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_orders as $order): ?>
                <tr>
                    <td>#<?= $order['order_id'] ?></td>
                    <td><?= e($order['customer_name']) ?></td>
                    <td>$<?= number_format((float)$order['total'], 2) ?></td>
                    <td>
                        <span class="badge badge-<?= strtolower(str_replace(' ', '-', (string)$order['status'])) ?>">
                            <?= e($order['status']) ?>
                        </span>
                    </td>
                    <td><?= date('M d, H:i', strtotime($order['created_at'])) ?></td>
                    <td>
                        <a href="<?= e(url('admin/order_edit.php?id=' . $order['order_id'])) ?>" class="btn btn-secondary btn-sm">Details</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    const ctxRevenue = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctxRevenue, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Revenue ($)',
                data: <?= json_encode($chart_values) ?>,
                borderColor: '#4CAF50',
                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#4CAF50'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                x: { grid: { display: false } }
            }
        }
    });

    // Order Distribution Chart
    const ctxOrder = document.getElementById('orderChart').getContext('2d');
    new Chart(ctxOrder, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Completed', 'Cancelled'],
            datasets: [{
                data: [<?= $stats['pending_orders'] ?>, <?= $stats['completed_orders'] ?>, <?= $stats['total_orders'] - $stats['pending_orders'] - $stats['completed_orders'] ?>],
                backgroundColor: ['#FF9F43', '#4CAF50', '#E74C3C'],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            },
            cutout: '70%'
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
