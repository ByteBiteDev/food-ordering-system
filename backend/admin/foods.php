<?php
declare(strict_types=1);
$page_title = 'Food Management';
require_once __DIR__ . '/includes/header.php';

$pdo = db();

// Handle status toggle
if (isset($_GET['toggle_status'])) {
    $food_id = (int)$_GET['toggle_status'];
    $stmt = $pdo->prepare("UPDATE foods SET status = 1 - status WHERE food_id = ?");
    $stmt->execute([$food_id]);
    flash_set('success', 'Food status toggled.');
    redirect('admin/foods.php');
}

// Fetch foods with categories
$foods = $pdo->query("
    SELECT f.*, c.name as category_name 
    FROM foods f 
    JOIN categories c ON f.category_id = c.category_id 
    ORDER BY c.name ASC, f.name ASC
")->fetchAll();
?>

<div class="page-header">
    <div class="page-title">
        <h2>Food Management</h2>
        <p>View and manage your cafe's menu items.</p>
    </div>
    <div class="page-actions">
        <a href="<?= e(url('admin/food_edit.php')) ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Food
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Menu Items</h3>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($foods as $food): ?>
                <tr>
                    <td>
                        <?php if ($food['image']): ?>
                            <img src="<?= e(url('uploads/' . $food['image'])) ?>" alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 0.5rem;">
                        <?php else: ?>
                            <div style="width: 50px; height: 50px; background: #f1f5f9; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; color: #cbd5e1;">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight: 600;"><?= e($food['name']) ?></td>
                    <td><span class="badge btn-secondary"><?= e($food['category_name']) ?></span></td>
                    <td>$<?= number_format((float)$food['price'], 2) ?></td>
                    <td>
                        <a href="?toggle_status=<?= $food['food_id'] ?>" style="text-decoration: none;">
                            <span class="badge badge-<?= $food['status'] ? 'completed' : 'cancelled' ?>" style="cursor: pointer;">
                                <?= $food['status'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </a>
                    </td>
                    <td>
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="<?= e(url('admin/food_edit.php?id=' . $food['food_id'])) ?>" class="btn btn-secondary btn-sm" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="<?= e(url('admin/foods.php?delete=' . $food['food_id'])) ?>" class="btn btn-secondary btn-sm" style="color: var(--danger);" onclick="return confirm('Are you sure?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Handle delete logic (simplified for this module)
if (isset($_GET['delete'])) {
    $food_id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM foods WHERE food_id = ?");
        $stmt->execute([$food_id]);
        flash_set('success', 'Food item deleted.');
    } catch (Exception $e) {
        flash_set('error', 'Cannot delete item (it might be linked to orders).');
    }
    redirect('admin/foods.php');
}
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
