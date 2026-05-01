<?php
declare(strict_types=1);
$page_title = 'My Favorites';
require_once __DIR__ . '/includes/header.php';

$pdo = db();
$user_id = (int)$user['user_id'];

// Handle removal
if (isset($_GET['remove'])) {
    $fav_id = (int)$_GET['remove'];
    $pdo->prepare("DELETE FROM favorites WHERE favorite_id = ? AND user_id = ?")->execute([$fav_id, $user_id]);
    flash_set('success', 'Removed from favorites.');
    redirect('profile/favorites.php');
}

$stmt = $pdo->prepare("
    SELECT f.*, fo.name, fo.price, fo.image, c.name as category_name 
    FROM favorites f 
    JOIN foods fo ON f.food_id = fo.food_id 
    JOIN categories c ON fo.category_id = c.category_id 
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
");
$stmt->execute([$user_id]);
$favorites = $stmt->fetchAll();
?>

<div style="margin-bottom: 2.5rem;">
    <h3 style="font-weight: 700; margin-bottom: 0.25rem;">Saved Favorites</h3>
    <p style="color: var(--text-muted); font-size: 0.875rem;">Quick access to the flavors you love most.</p>
</div>

<?php if (empty($favorites)): ?>
    <div class="card" style="padding: 4rem; text-align: center; border-style: dashed;">
        <div style="font-size: 4rem; color: var(--border); margin-bottom: 1.5rem;"><i class="fas fa-heart-broken"></i></div>
        <h4 style="font-weight: 700; margin-bottom: 0.5rem;">Your wishlist is empty</h4>
        <p style="color: var(--text-muted); margin-bottom: 2rem;">Save your favorite meals to quickly find them later.</p>
        <a href="<?= e(url('index.php')) ?>" class="btn btn-primary">Discover Food</a>
    </div>
<?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
        <?php foreach ($favorites as $fav): ?>
            <div class="card" style="margin-bottom: 0; transition: transform 0.2s;">
                <div style="position: relative;">
                    <img
                        src="<?= e(get_food_image_url((string)($fav['image'] ?? ''))) ?>"
                        onerror="this.onerror=null;this.src='<?= e(url('assets/img/food-placeholder.svg')) ?>';"
                        alt="<?= e((string)$fav['name']) ?>"
                        style="width: 100%; height: 180px; object-fit: cover;"
                        loading="lazy"
                    >
                    <a href="?remove=<?= $fav['favorite_id'] ?>" style="position: absolute; top: 12px; right: 12px; background: #fff; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--danger); box-shadow: var(--shadow); text-decoration: none;">
                        <i class="fas fa-heart"></i>
                    </a>
                    <span style="position: absolute; bottom: 12px; left: 12px; background: rgba(0,0,0,0.6); color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;"><?= e($fav['category_name']) ?></span>
                </div>
                <div class="card-body" style="padding: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                        <h4 style="font-weight: 700; font-size: 1.1rem;"><?= e($fav['name']) ?></h4>
                        <div style="font-weight: 800; color: var(--primary);">$<?= number_format((float)$fav['price'], 2) ?></div>
                    </div>
                    <div style="margin-top: 1.25rem; display: flex; gap: 0.75rem;">
                        <form action="<?= e(url('cart.php')) ?>" method="POST" style="flex: 1;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="food_id" value="<?= $fav['food_id'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; font-size: 0.8rem;">Add to Cart</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
