<?php
declare(strict_types=1);
$page_title = 'Category Management';
require_once __DIR__ . '/includes/header.php';

$pdo = db();

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $name = trim($_POST['name'] ?? '');
    $cat_id = (int)($_POST['category_id'] ?? 0);
    $status = (int)($_POST['status'] ?? 1);

    if ($name) {
        if ($cat_id) {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, status = ? WHERE category_id = ?");
            $stmt->execute([$name, $status, $cat_id]);
            flash_set('success', 'Category updated.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name, status) VALUES (?, ?)");
            $stmt->execute([$name, $status]);
            flash_set('success', 'New category added.');
        }
    }
    redirect('admin/categories.php');
}

// Handle delete
if (isset($_GET['delete'])) {
    $cat_id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->execute([$cat_id]);
        flash_set('success', 'Category deleted.');
    } catch (Exception $e) {
        flash_set('error', 'Cannot delete category (linked to foods).');
    }
    redirect('admin/categories.php');
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>
<div class="page-header">
    <div class="page-title">
        <h2>Category Management</h2>
        <p>Organize your menu by creating and managing food categories.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
    <!-- Add/Edit Form -->
    <div class="card">
        <div class="card-header">
            <h3 id="form-title">Add Category</h3>
        </div>
        <div style="padding: 1.5rem;">
            <form method="POST" id="category-form">
                <?= csrf_field() ?>
                <input type="hidden" name="category_id" id="cat-id" value="">
                <div class="form-group">
                    <label class="form-label">Category Name</label>
                    <input type="text" name="name" id="cat-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Visibility</label>
                    <select name="status" id="cat-status" class="form-control">
                        <option value="1">Visible</option>
                        <option value="0">Hidden</option>
                    </select>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary" id="submit-btn">Add Category</button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()" id="cancel-btn" style="display: none;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Categories List -->
    <div class="card">
        <div class="card-header">
            <h3>Existing Categories</h3>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td style="font-weight: 600;"><?= e($cat['name']) ?></td>
                        <td>
                            <span class="badge badge-<?= $cat['status'] ? 'completed' : 'cancelled' ?>">
                                <?= $cat['status'] ? 'Visible' : 'Hidden' ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="editCategory(<?= $cat['category_id'] ?>, '<?= e($cat['name']) ?>', <?= $cat['status'] ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?delete=<?= $cat['category_id'] ?>" class="btn btn-secondary btn-sm" style="color: var(--danger);" onclick="return confirm('Delete this category?')">
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
</div>

<script>
function editCategory(id, name, status) {
    document.getElementById('form-title').innerText = 'Edit Category';
    document.getElementById('cat-id').value = id;
    document.getElementById('cat-name').value = name;
    document.getElementById('cat-status').value = status;
    document.getElementById('submit-btn').innerText = 'Update Category';
    document.getElementById('cancel-btn').style.display = 'inline-flex';
}

function resetForm() {
    document.getElementById('form-title').innerText = 'Add Category';
    document.getElementById('cat-id').value = '';
    document.getElementById('cat-name').value = '';
    document.getElementById('cat-status').value = '1';
    document.getElementById('submit-btn').innerText = 'Add Category';
    document.getElementById('cancel-btn').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
