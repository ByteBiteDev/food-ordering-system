<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/backend/includes/init.php';

$pdo = db();
$user = current_user();
$userId = $user ? (int)$user['user_id'] : null;

// Handle Add to Cart
if (is_post()) {
    verify_csrf();
    $foodId = int_from_request($_POST, 'food_id', 0);
    $quantity = max(1, int_from_request($_POST, 'quantity', 1));
    
    if ($foodId > 0) {
        $stmt = $pdo->prepare("SELECT food_id FROM foods WHERE food_id = ? AND status = 1");
        $stmt->execute([$foodId]);
        if ($stmt->fetch()) {
            cart_add($foodId, $quantity);
            flash_set('success', 'Added to cart!');
            redirect('food.php' . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
        }
    }
}

$categoryId = int_from_request($_GET, 'category_id', 0);
$search = trim((string)($_GET['q'] ?? ''));

// Fetch Categories for Sidebar
$categories = $pdo->query("SELECT * FROM categories WHERE status = 1 ORDER BY name")->fetchAll();

// Fetch Foods based on filters
$sql = "SELECT f.*, c.name as category_name";
$params = [];
if ($userId !== null) {
    $sql .= ", (fav.favorite_id IS NOT NULL) AS is_favorite";
}
$sql .= " FROM foods f JOIN categories c ON f.category_id = c.category_id";
if ($userId !== null) {
    $sql .= " LEFT JOIN favorites fav ON fav.food_id = f.food_id AND fav.user_id = ?";
    $params[] = $userId;
}
$sql .= " WHERE f.status = 1";

if ($categoryId > 0) {
    $sql .= " AND f.category_id = ?";
    $params[] = $categoryId;
}

if ($search !== '') {
    $sql .= " AND (f.name LIKE ? OR f.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY f.food_id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$foods = $stmt->fetchAll();

$page_title = "Explore Menu - " . APP_NAME;
require APP_ROOT . '/backend/includes/layout_top.php';
?>

<section class="menu-page">
    <div class="container">
        <!-- Header & Search -->
        <div class="menu-header">
            <div class="fade-in">
                <h1 class="menu-title">Our Menu</h1>
                <p class="menu-subtitle">Discover a world of flavors delivered to your doorstep.</p>
            </div>
            <div class="fade-in menu-search-wrap">
                <button type="button" class="menu-sidebar-toggle" id="menuSidebarToggle" aria-controls="menuSidebar" aria-expanded="false">
                    <i class="fas fa-bars"></i>
                    Categories
                </button>
                <form method="GET" class="premium-search">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search for your favorite dish...">
                    <?php if ($categoryId > 0): ?>
                        <input type="hidden" name="category_id" value="<?= $categoryId ?>">
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>
        </div>

        <div class="menu-layout">
            <!-- Categories Sidebar -->
            <aside class="fade-in menu-sidebar" id="menuSidebar" aria-label="Menu categories">
                <div class="sidebar-sticky">
                    <div class="glass sidebar-card">
                        <h4 class="sidebar-title">Categories</h4>
                        <div class="category-pills">
                            <a href="<?= e(url('food.php')) ?>" class="pill-item <?= $categoryId === 0 ? 'active' : '' ?>">
                                <i class="fas fa-border-all"></i> All Menu
                            </a>
                            <?php foreach ($categories as $cat): ?>
                                <a href="<?= e(url('food.php?category_id=' . $cat['category_id'])) ?>" class="pill-item <?= $categoryId === (int)$cat['category_id'] ? 'active' : '' ?>">
                                    <i class="fas fa-chevron-right"></i> <?= e($cat['name']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="promo-box-premium">
                        <div class="promo-content">
                            <span class="promo-label">Limited Offer</span>
                            <h3>First Order?</h3>
                            <p>Use code <strong>FRESH20</strong> for 20% off.</p>
                            <a href="<?= e(url('register.php')) ?>" class="btn-promo">Join Now</a>
                        </div>
                        <i class="fas fa-gift promo-icon"></i>
                    </div>
                </div>
            </aside>

            <div class="menu-sidebar-overlay" id="menuSidebarOverlay" aria-hidden="true"></div>

            <!-- Food Display -->
            <div class="fade-in">
                <!-- Filter Bar -->
                <div class="filter-bar glass">
                    <div class="filter-info">
                        Showing <strong><?= count($foods) ?></strong> items
                    </div>
                    <div class="filter-actions">
                        <select class="filter-select">
                            <option>Sort by: Newest</option>
                            <option>Price: Low to High</option>
                            <option>Price: High to Low</option>
                            <option>Top Rated</option>
                        </select>
                    </div>
                </div>

                <!-- Food Grid -->
                <div class="food-grid">
                    <?php if (count($foods) > 0): ?>
                        <?php foreach ($foods as $food): ?>
                            <?php render_premium_card($food); ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: var(--text-muted);">No foods found. Try adjusting your search or filters.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require APP_ROOT . '/backend/includes/layout_bottom.php'; ?>