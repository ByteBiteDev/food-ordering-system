<?php
declare(strict_types=1);
$page_title = 'Customer Management';
require_once __DIR__ . '/includes/header.php';

$pdo = db();
$search = $_GET['search'] ?? '';

// Handle status toggle
if (isset($_GET['toggle_status'])) {
    require_csrf(); // Not perfect via GET, but adding it for safety if we had CSRF on GET or using a link
    $user_id = (int)$_GET['toggle_status'];
    $stmt = $pdo->prepare("UPDATE users SET status = 1 - status WHERE user_id = ? AND role = 'customer'");
    $stmt->execute([$user_id]);
    flash_set('success', 'Customer status updated.');
    redirect('admin/customers.php');
}

$query = "SELECT * FROM users WHERE role = 'customer'";
$params = [];

if ($search) {
    $query .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>
<div class="page-header">
    <div class="page-title">
        <h2>Customer Management</h2>
        <p>View and manage your registered customer base.</p>
    </div>
</div>

<div class="card" style="margin-bottom: 2rem;">
    <div style="padding: 1.5rem;">
        <form method="GET" style="display: flex; gap: 1rem; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0; flex: 1;">
                <label class="form-label">Search Customer</label>
                <input type="text" name="search" class="form-control" placeholder="Name, email or phone..." value="<?= e($search) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="<?= e(url('admin/customers.php')) ?>" class="btn btn-secondary">Reset</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Customer Accounts</h3>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Joined At</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">No customers found.</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($customers as $customer): ?>
                <tr>
                    <td style="font-weight: 600;"><?= e($customer['name']) ?></td>
                    <td><?= e($customer['email']) ?></td>
                    <td><?= e($customer['phone']) ?></td>
                    <td><?= date('M d, Y', strtotime($customer['created_at'])) ?></td>
                    <td>
                        <span class="badge badge-<?= $customer['status'] ? 'completed' : 'cancelled' ?>">
                            <?= $customer['status'] ? 'Active' : 'Suspended' ?>
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="?toggle_status=<?= $customer['toggle_status'] ?? $customer['user_id'] ?>" class="btn btn-secondary btn-sm" title="<?= $customer['status'] ? 'Suspend' : 'Activate' ?>">
                                <i class="fas fa-power-off"></i>
                            </a>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="alert('View History feature coming soon!')">
                                <i class="fas fa-history"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
