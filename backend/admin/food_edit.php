<?php
declare(strict_types=1);
$page_title = 'Edit Food Item';
require_once __DIR__ . '/includes/header.php';

$pdo = db();
$food_id = (int)($_GET['id'] ?? 0);
$food = null;

if ($food_id) {
    $stmt = $pdo->prepare("SELECT * FROM foods WHERE food_id = ?");
    $stmt->execute([$food_id]);
    $food = $stmt->fetch();
} else {
    $page_title = 'Add New Food';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    
    $name = trim($_POST['name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $status = (int)($_POST['status'] ?? 1);
    
    // Validation
    if (!$name || !$category_id || $price <= 0) {
        flash_set('error', 'Please fill in all required fields.');
    } else {
        $image_name = $food['image'] ?? '';
        
        // Handle Image Upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
            $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            
            if (in_array($file_ext, $allowed_exts)) {
                $new_image_name = uniqid('food_') . '.' . $file_ext;
                $upload_path = rtrim(FOOD_UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $new_image_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    // Delete old image if it exists
                    $oldPath = rtrim(FOOD_UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $image_name;
                    if ($image_name && is_file($oldPath)) {
                        unlink($oldPath);
                    }
                    $image_name = $new_image_name;
                }
            } else {
                flash_set('warning', 'Invalid image format. Supported: JPG, PNG, WEBP.');
            }
        }
        
        if ($food_id) {
            // Update
            $stmt = $pdo->prepare("UPDATE foods SET category_id = ?, name = ?, price = ?, description = ?, image = ?, status = ? WHERE food_id = ?");
            $stmt->execute([$category_id, $name, $price, $description, $image_name, $status, $food_id]);
            flash_set('success', 'Food item updated.');
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO foods (category_id, name, price, description, image, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$category_id, $name, $price, $description, $image_name, $status]);
            flash_set('success', 'New food item added.');
        }
        redirect('admin/foods.php');
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>
<div class="page-header">
    <div class="page-title">
        <h2><?= $food_id ? 'Edit Food' : 'Add New Food' ?></h2>
        <p><?= $food_id ? 'Update food details, pricing, and availability.' : 'Create a new item for your cafe menu.' ?></p>
    </div>
    <div class="page-actions">
        <a href="<?= e(url('admin/foods.php')) ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Menu
        </a>
    </div>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h3>Food Details</h3>
    </div>
    <div style="padding: 2rem;">
        <form method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Food Name *</label>
                    <input type="text" name="name" class="form-control" value="<?= e($food['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Category *</label>
                    <select name="category_id" class="form-control" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['category_id'] ?>" <?= (isset($food['category_id']) && $food['category_id'] == $cat['category_id']) ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Price ($) *</label>
                    <input type="number" step="0.01" name="price" class="form-control" value="<?= e((string)($food['price'] ?? '')) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="1" <?= (isset($food['status']) && $food['status'] == 1) ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= (isset($food['status']) && $food['status'] == 0) ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4"><?= e($food['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Food Image</label>
                <?php if (isset($food['image']) && $food['image']): ?>
                    <div style="margin-bottom: 1rem;">
                        <img src="<?= e(get_food_image_url((string)$food['image'])) ?>" alt="" style="width: 120px; height: 120px; object-fit: cover; border-radius: 0.75rem; border: 1px solid var(--border);">
                    </div>
                <?php endif; ?>
                <input type="file" name="image" class="form-control">
                <small style="color: var(--text-muted);">Leave empty to keep current image (if editing).</small>
            </div>

            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary">Save Food Item</button>
                <a href="<?= e(url('admin/foods.php')) ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
