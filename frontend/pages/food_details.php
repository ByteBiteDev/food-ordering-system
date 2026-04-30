<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/backend/includes/init.php';

$foodId = int_from_request($_GET, 'food_id', 0);
if ($foodId <= 0) {
    redirect('food.php');
}

$stmt = db()->prepare('SELECT f.*, c.name AS category_name
                       FROM foods f
                       JOIN categories c ON c.category_id = f.category_id
                       WHERE f.food_id = ?');
$stmt->execute([$foodId]);
$food = $stmt->fetch();

if (!$food) {
    flash_set('error', 'Food not found.');
    redirect('food.php');
}

$imgUrl = get_food_image_url($food['image'] ?? '');

// Gallery: this project stores a single image per food item, so keep it clean.
$galleryImages = [$imgUrl];

// Reviews: only allow customers who ordered the item to review.
$user = current_user();
$hasOrdered = false;
if ($user) {
    $stmt = db()->prepare("
        SELECT 1
        FROM orders o
        JOIN order_items oi ON oi.order_id = o.order_id
        WHERE o.user_id = ? AND oi.food_id = ? AND o.status NOT IN ('Cancelled', 'Canceled')
        LIMIT 1
    ");
    $stmt->execute([(int)$user['user_id'], $foodId]);
    $hasOrdered = (bool)$stmt->fetchColumn();
}

// Handle Review Submit
if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'submit_review') {
        if (!$user) {
            flash_set('error', 'Please sign in to leave a review.');
            redirect('login.php');
        }
        if (!$hasOrdered) {
            flash_set('error', 'Only customers who ordered this item can leave a review.');
            redirect('food_details.php?food_id=' . $foodId);
        }

        $rating = int_from_request($_POST, 'rating', 0);
        $comment = trim((string)($_POST['comment'] ?? ''));

        if ($rating < 1 || $rating > 5) {
            flash_set('error', 'Please select a rating from 1 to 5.');
            redirect('food_details.php?food_id=' . $foodId . '#reviews');
        }
        if ($comment === '' || strlen($comment) < 10) {
            flash_set('error', 'Please write a short review (at least 10 characters).');
            redirect('food_details.php?food_id=' . $foodId . '#reviews');
        }

        try {
            $stmt = db()->prepare("
                INSERT INTO food_reviews (food_id, user_id, rating, comment, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), updated_at = NOW()
            ");
            $stmt->execute([$foodId, (int)$user['user_id'], $rating, $comment]);
            flash_set('success', 'Your review has been saved.');
        } catch (Throwable $e) {
            flash_set('error', 'Reviews are not enabled in the database yet.');
        }
        redirect('food_details.php?food_id=' . $foodId . '#reviews');
    }
}

$reviewsFeatureAvailable = true;
$myReview = null;
$reviews = [];
try {
    $stmtReviews = db()->prepare("
        SELECT r.rating, r.comment, r.created_at, u.name
        FROM food_reviews r
        JOIN users u ON u.user_id = r.user_id
        WHERE r.food_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmtReviews->execute([$foodId]);
    $reviews = $stmtReviews->fetchAll();

    if ($user) {
        $stmtMy = db()->prepare("
            SELECT rating, comment
            FROM food_reviews
            WHERE food_id = ? AND user_id = ?
            LIMIT 1
        ");
        $stmtMy->execute([$foodId, (int)$user['user_id']]);
        $myReview = $stmtMy->fetch() ?: null;
    }
} catch (Throwable $e) {
    $reviewsFeatureAvailable = false;
    $reviews = [];
    $myReview = null;
}

$reviewCount = count($reviews);
$avgRating = 0.0;
if ($reviewCount > 0) {
    $sum = 0;
    foreach ($reviews as $r) {
        $sum += (int)$r['rating'];
    }
    $avgRating = $sum / $reviewCount;
}

// Mock recommended foods
$recommendedFoods = db()->query("SELECT food_id, name, price, image FROM foods WHERE status = 1 AND food_id != $foodId ORDER BY RAND() LIMIT 4")->fetchAll();

// Handle Add to Cart
if (is_post()) {
    verify_csrf();
    $quantity = max(1, int_from_request($_POST, 'quantity', 1));
    $size = $_POST['size'] ?? 'regular';
    $spiceLevel = $_POST['spice_level'] ?? 'medium';
    $action = $_POST['action'] ?? '';

    if ($action === 'add_to_cart') {
        cart_add($foodId, $quantity);
        flash_set('success', 'Added to cart!');
        redirect('food_details.php?food_id=' . $foodId);
    } elseif ($action === 'order_now') {
        cart_add($foodId, $quantity);
        redirect('checkout.php');
    }
}

$page_title = $food['name'] . " - " . APP_NAME;
require APP_ROOT . '/backend/includes/layout_top.php';
?>

<!-- Product Detail Hero -->
<section class="product-hero">
    <div class="container">
        <nav class="breadcrumb">
            <a href="<?= e(url('index.php')) ?>">Home</a>
            <span>/</span>
            <a href="<?= e(url('food.php')) ?>">Menu</a>
            <span>/</span>
            <span><?= e($food['name']) ?></span>
        </nav>
    </div>
</section>

<div class="container product-container">
    <div class="product-grid">
        <!-- Image Gallery -->
        <div class="product-gallery">
            <div class="main-image-container">
                <img src="<?= e($imgUrl) ?>" onerror="this.onerror=null;this.src='<?= e(url('assets/img/food-placeholder.svg')) ?>';" alt="<?= e($food['name']) ?>" class="main-image" id="mainImage">
                <div class="image-zoom-overlay" id="zoomOverlay">
                    <img src="<?= e($imgUrl) ?>" onerror="this.onerror=null;this.src='<?= e(url('assets/img/food-placeholder.svg')) ?>';" alt="Zoom" id="zoomImage">
                </div>
                <div class="gallery-nav">
                    <button class="nav-btn prev-btn" id="prevBtn">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="nav-btn next-btn" id="nextBtn">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="image-badges">
                    <span class="badge badge-popular">Popular</span>
                    <span class="badge badge-rating">
                        <i class="fas fa-star"></i> 4.8
                    </span>
                </div>
            </div>

            <?php if (count($galleryImages) > 1): ?>
                <div class="thumbnail-grid">
                    <?php foreach ($galleryImages as $index => $image): ?>
                    <div class="thumbnail <?= $index === 0 ? 'active' : '' ?>" data-image="<?= e($image) ?>">
                        <img src="<?= e($image) ?>" onerror="this.onerror=null;this.src='<?= e(url('assets/img/food-placeholder.svg')) ?>';" alt="View <?= $index + 1 ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Product Info -->
        <div class="product-info">
            <div class="product-header">
                <div class="category-tag"><?= e($food['category_name']) ?></div>
                <h1 class="product-title"><?= e($food['name']) ?></h1>
                <div class="product-meta">
                    <div class="rating">
                        <div class="stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                        <span class="rating-text">4.8 (<?= count($reviews) ?> reviews)</span>
                    </div>
                    <div class="delivery-time">
                        <i class="fas fa-clock"></i>
                        <span>25-35 min</span>
                    </div>
                </div>
            </div>

            <div class="product-price">
                <div class="current-price">KSh <?= number_format((float)$food['price'], 2) ?></div>
                <div class="original-price">KSh <?= number_format((float)$food['price'] * 1.3, 2) ?></div>
                <div class="discount-badge">23% OFF</div>
            </div>

            <div class="product-description">
                <h3>About This Dish</h3>
                <p><?= e($food['description']) ?></p>
                <div class="product-features">
                    <span class="feature-tag"><i class="fas fa-leaf"></i> Organic</span>
                    <span class="feature-tag"><i class="fas fa-fire"></i> Fresh</span>
                    <span class="feature-tag"><i class="fas fa-utensils"></i> Chef Special</span>
                </div>
            </div>

            <!-- Customization Options -->
            <div class="customization-section">
                <h3>Customize Your Order</h3>

                <div class="customization-group">
                    <label class="customization-label">Size</label>
                    <div class="option-grid">
                        <label class="option-card active" for="size-regular">
                            <input type="radio" id="size-regular" name="size" value="regular" checked>
                            <div class="option-content">
                                <span class="option-title">Regular</span>
                                <span class="option-price">+KSh 0</span>
                            </div>
                        </label>
                        <label class="option-card" for="size-large">
                            <input type="radio" id="size-large" name="size" value="large">
                            <div class="option-content">
                                <span class="option-title">Large</span>
                                <span class="option-price">+KSh 150</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="customization-group">
                    <label class="customization-label">Spice Level</label>
                    <div class="option-grid">
                        <label class="option-card active" for="spice-mild">
                            <input type="radio" id="spice-mild" name="spice_level" value="mild" checked>
                            <div class="option-content">
                                <span class="option-title">Mild</span>
                                <span class="option-desc">For everyone</span>
                            </div>
                        </label>
                        <label class="option-card" for="spice-medium">
                            <input type="radio" id="spice-medium" name="spice_level" value="medium">
                            <div class="option-content">
                                <span class="option-title">Medium</span>
                                <span class="option-desc">Balanced heat</span>
                            </div>
                        </label>
                        <label class="option-card" for="spice-hot">
                            <input type="radio" id="spice-hot" name="spice_level" value="hot">
                            <div class="option-content">
                                <span class="option-title">Hot</span>
                                <span class="option-desc">For spice lovers</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="customization-group">
                    <label class="customization-label">Add Extra Toppings</label>
                    <div class="addon-grid">
                        <label class="addon-item">
                            <input type="checkbox" name="addons[]" value="extra_cheese">
                            <div class="addon-content">
                                <span class="addon-title">Extra Cheese</span>
                                <span class="addon-price">+KSh 80</span>
                            </div>
                        </label>
                        <label class="addon-item">
                            <input type="checkbox" name="addons[]" value="mushrooms">
                            <div class="addon-content">
                                <span class="addon-title">Mushrooms</span>
                                <span class="addon-price">+KSh 60</span>
                            </div>
                        </label>
                        <label class="addon-item">
                            <input type="checkbox" name="addons[]" value="olives">
                            <div class="addon-content">
                                <span class="addon-title">Olives</span>
                                <span class="addon-price">+KSh 50</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Quantity and Actions -->
            <div class="order-section">
                <div class="quantity-selector">
                    <button type="button" class="qty-btn" onclick="updateQuantity(-1)">
                        <i class="fas fa-minus"></i>
                    </button>
                    <input type="number" id="quantity" name="quantity" value="1" min="1" max="10">
                    <button type="button" class="qty-btn" onclick="updateQuantity(1)">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>

                <div class="total-price">
                    <span class="total-label">Total:</span>
                    <span class="total-amount" id="totalAmount">KSh <?= number_format((float)$food['price'], 2) ?></span>
                </div>
            </div>

            <div class="action-buttons">
                <form method="post" class="action-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="quantity" value="1" id="hiddenQuantity">
                    <button type="submit" name="action" value="add_to_cart" class="btn btn-primary btn-large">
                        <i class="fas fa-cart-plus"></i>
                        Add to Cart
                    </button>
                    <button type="submit" name="action" value="order_now" class="btn btn-secondary btn-large">
                        <i class="fas fa-bolt"></i>
                        Order Now
                    </button>
                </form>
                <button class="btn btn-outline favorite-btn" onclick="toggleFavorite(<?= $food['food_id'] ?>)">
                    <i class="far fa-heart"></i>
                    Save for Later
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reviews Section -->
<section class="reviews-section" id="reviews">
    <div class="container">
        <div class="section-header">
            <h2>Reviews</h2>
            <?php if ($reviewCount > 0): ?>
                <p class="muted" style="margin-top: 0.75rem;">
                    <?= number_format($avgRating, 1) ?> / 5 &middot; <?= $reviewCount ?> review<?= $reviewCount === 1 ? '' : 's' ?>
                </p>
            <?php else: ?>
                <p class="muted" style="margin-top: 0.75rem;">No reviews yet.</p>
            <?php endif; ?>
        </div>

        <?php if (!$reviewsFeatureAvailable): ?>
            <div class="alert alert--error" style="border-radius: 12px; margin-top: 1rem;">
                Reviews are not enabled in the database yet.
            </div>
        <?php else: ?>
            <?php if ($user && $hasOrdered): ?>
                <div class="review-form card review-form--center">
                    <h3 class="review-form__title">Leave a review</h3>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="submit_review">

                        <div class="review-form__row">
                            <div class="review-stars" aria-label="Rating">
                                <?php $selectedRating = (int)($myReview['rating'] ?? 0); ?>
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input class="review-stars__input" type="radio" id="rating<?= $i ?>" name="rating" value="<?= $i ?>" <?= $selectedRating === $i ? 'checked' : '' ?>>
                                    <label class="review-stars__label" for="rating<?= $i ?>" title="<?= $i ?> stars">
                                        <i class="fas fa-star"></i>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="form-group review-form__comment">
                            <label class="form-label" for="review_comment">Comment <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-pen-to-square input-icon"></i>
                                <textarea id="review_comment" class="form-input review-form__textarea" name="comment" rows="4" required placeholder="Share your experience..."><?= e((string)($myReview['comment'] ?? '')) ?></textarea>
                            </div>
                        </div>

                        <button class="btn btn-primary review-form__submit" type="submit">
                            Save Review
                        </button>
                    </form>
                </div>
            <?php elseif ($user): ?>
                <p class="muted" style="text-align:center; margin-bottom: 2rem;">Order this item to leave a review.</p>
            <?php else: ?>
                <p class="muted" style="text-align:center; margin-bottom: 2rem;">
                    <a href="<?= e(url('login.php')) ?>" class="nav-link">Sign in</a> to leave a review.
                </p>
            <?php endif; ?>

            <?php if ($reviews): ?>
                <div class="reviews-grid">
                    <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">
                                    <span><?= strtoupper(substr((string)$review['name'], 0, 1)) ?></span>
                                </div>
                                <div>
                                    <h4><?= e((string)$review['name']) ?></h4>
                                    <div class="review-date"><?= e(date('M j, Y', strtotime((string)$review['created_at']))) ?></div>
                                </div>
                            </div>
                            <div class="review-rating">
                                <?php $r = (int)$review['rating']; ?>
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <i class="fas fa-star <?= $s <= $r ? 'active' : '' ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p class="review-text"><?= e((string)$review['comment']) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Recommendations Section -->
<section class="recommendations-section">
    <div class="container">
        <div class="section-header">
            <h2>More to Explore</h2>
            <p class="muted">Other items from the menu.</p>
        </div>

        <div class="recommendations-grid">
            <?php foreach ($recommendedFoods as $recFood): ?>
            <div class="food-card">
                <div class="food-image">
                    <img src="<?= e(get_food_image_url($recFood['image'] ?? '')) ?>" onerror="this.onerror=null;this.src='<?= e(url('assets/img/food-placeholder.svg')) ?>';" alt="<?= e($recFood['name']) ?>">
                    <div class="food-overlay">
                        <button class="quick-add" onclick="quickAddToCart(<?= $recFood['food_id'] ?>)">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="food-info">
                    <h3><a href="food_details.php?food_id=<?= $recFood['food_id'] ?>"><?= e($recFood['name']) ?></a></h3>
                    <div class="food-price">KSh <?= number_format((float)$recFood['price'], 2) ?></div>
                    <div class="food-rating">
                        <i class="fas fa-star"></i>
                        <span>4.5</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<style>
/* Product Detail Styles */
.product-hero {
    background: linear-gradient(135deg, rgba(76, 175, 80, 0.95) 0%, rgba(255, 159, 67, 0.9) 100%);
    padding: 40px 0;
    color: white;
    position: relative;
    overflow: hidden;
}

.product-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('<?= e($imgUrl) ?>') center/cover;
    opacity: 0.1;
}

.breadcrumb {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    margin-bottom: 20px;
}

.breadcrumb a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: color 0.3s;
}

.breadcrumb a:hover {
    color: white;
}

.product-container {
    padding: 60px 0;
}

.product-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: start;
}

@media (max-width: 768px) {
    .product-grid {
        grid-template-columns: 1fr;
        gap: 40px;
    }
}

/* Gallery Styles */
.product-gallery {
    position: sticky;
    top: 100px;
}

.main-image-container {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
}

.main-image {
    width: 100%;
    height: 500px;
    object-fit: cover;
    cursor: zoom-in;
    transition: transform 0.3s;
}

.main-image:hover {
    transform: scale(1.02);
}

.image-zoom-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.9);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    cursor: zoom-out;
}

.image-zoom-overlay.active {
    display: flex;
}

#zoomImage {
    max-width: 90%;
    max-height: 90%;
    object-fit: contain;
}

.gallery-nav {
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    transform: translateY(-50%);
    display: flex;
    justify-content: space-between;
    padding: 0 20px;
}

.nav-btn {
    background: rgba(255, 255, 255, 0.9);
    border: none;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.nav-btn:hover {
    background: white;
    transform: scale(1.1);
}

.image-badges {
    position: absolute;
    top: 20px;
    left: 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-popular {
    background: var(--brand-secondary);
    color: white;
}

.badge-rating {
    background: rgba(255, 255, 255, 0.9);
    color: #1F2937;
    display: flex;
    align-items: center;
    gap: 5px;
}

.thumbnail-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
}

.thumbnail {
    border-radius: 10px;
    overflow: hidden;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.3s;
}

.thumbnail.active {
    border-color: var(--brand-primary);
}

.thumbnail img {
    width: 100%;
    height: 100px;
    object-fit: cover;
    transition: transform 0.3s;
}

.thumbnail:hover img {
    transform: scale(1.1);
}

/* Product Info Styles */
.product-info {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.category-tag {
    background: #F9FAFB;
    color: var(--brand-secondary);
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    display: inline-block;
    margin-bottom: 16px;
}

.product-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: #1F2937;
    margin-bottom: 16px;
    line-height: 1.2;
}

.product-meta {
    display: flex;
    align-items: center;
    gap: 24px;
    margin-bottom: 24px;
}

.rating {
    display: flex;
    align-items: center;
    gap: 8px;
}

.stars {
    color: #F59E0B;
}

.rating-text {
    color: #6B7280;
    font-size: 14px;
}

.delivery-time {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #6B7280;
    font-size: 14px;
}

.product-price {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 32px;
}

.current-price {
    font-size: 2rem;
    font-weight: 800;
    color: var(--brand-secondary);
}

.original-price {
    font-size: 1.25rem;
    color: #9CA3AF;
    text-decoration: line-through;
}

.discount-badge {
    background: #EF4444;
    color: white;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.product-description h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1F2937;
    margin-bottom: 16px;
}

.product-description p {
    color: #6B7280;
    line-height: 1.6;
    margin-bottom: 20px;
}

.product-features {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.feature-tag {
    background: #F0F9FF;
    color: #0369A1;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Customization Styles */
.customization-section {
    margin: 40px 0;
    padding: 32px;
    background: #F9FAFB;
    border-radius: 16px;
}

.customization-section h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1F2937;
    margin-bottom: 24px;
}

.customization-group {
    margin-bottom: 32px;
}

.customization-label {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 16px;
}

.option-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px;
}

.option-card {
    background: white;
    border: 2px solid #E5E7EB;
    border-radius: 12px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
}

.option-card:hover {
    border-color: var(--brand-primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.14);
}

.option-card.active {
    border-color: var(--brand-primary);
    background: rgba(76, 175, 80, 0.08);
}

.option-card input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.option-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.option-title {
    font-weight: 600;
    color: #1F2937;
}

.option-price {
    font-size: 14px;
    color: var(--brand-secondary);
    font-weight: 500;
}

.option-desc {
    font-size: 12px;
    color: #6B7280;
}

.addon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}

.addon-item {
    background: white;
    border: 2px solid #E5E7EB;
    border-radius: 12px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
}

.addon-item:hover {
    border-color: var(--brand-primary);
}

.addon-item input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.addon-item input[type="checkbox"]:checked + .addon-content::before {
    content: '✓';
    position: absolute;
    right: 16px;
    top: 16px;
    background: var(--brand-primary);
    color: white;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}

.addon-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.addon-title {
    font-weight: 600;
    color: #1F2937;
}

.addon-price {
    font-size: 14px;
    color: var(--brand-secondary);
    font-weight: 500;
}

/* Order Section */
.order-section {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 32px 0;
    padding: 24px;
    background: #F9FAFB;
    border-radius: 12px;
}

.quantity-selector {
    display: flex;
    align-items: center;
    gap: 16px;
}

.qty-btn {
    background: white;
    border: 2px solid #E5E7EB;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
}

.qty-btn:hover {
    border-color: var(--brand-primary);
    color: var(--brand-primary);
}

#quantity {
    width: 60px;
    text-align: center;
    border: 2px solid #E5E7EB;
    border-radius: 8px;
    padding: 8px;
    font-size: 16px;
    font-weight: 600;
}

.total-price {
    text-align: right;
}

.total-label {
    font-size: 14px;
    color: #6B7280;
    margin-bottom: 4px;
}

.total-amount {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--brand-secondary);
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.action-form {
    display: contents;
}

.btn {
    padding: 16px 32px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 16px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
}

.btn-primary {
    background: var(--gradient-primary);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(76, 175, 80, 0.25);
}

.btn-secondary {
    background: #1F2937;
    color: white;
}

.btn-secondary:hover {
    background: #374151;
    transform: translateY(-2px);
}

.btn-outline {
    background: transparent;
    border: 2px solid #E5E7EB;
    color: #6B7280;
}

.btn-outline:hover {
    border-color: var(--brand-primary);
    color: var(--text-primary);
}

.btn-large {
    padding: 18px 36px;
    font-size: 18px;
}

/* Reviews Section */
.reviews-section {
    background: #F9FAFB;
    padding: 80px 0;
}

.section-header {
    text-align: center;
    margin-bottom: 60px;
}

.section-header h2 {
    font-size: 2.5rem;
    font-weight: 800;
    color: #1F2937;
    margin-bottom: 16px;
}

.reviews-summary {
    display: flex;
    justify-content: center;
    margin-top: 40px;
}

.rating-overview {
    display: flex;
    gap: 40px;
    align-items: center;
}

.rating-score {
    text-align: center;
}

.score {
    font-size: 3rem;
    font-weight: 800;
    color: #1F2937;
    display: block;
}

.rating-breakdown {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.rating-bar {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    color: #6B7280;
}

.bar {
    width: 120px;
    height: 8px;
    background: #E5E7EB;
    border-radius: 4px;
    overflow: hidden;
}

.fill {
    height: 100%;
    background: #F59E0B;
    border-radius: 4px;
}

.reviews-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 24px;
}

.review-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
}

.reviewer-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.reviewer-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--brand-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 18px;
}

.reviewer-info h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #1F2937;
}

.review-date {
    font-size: 12px;
    color: #9CA3AF;
}

.review-rating .fa-star {
    color: #E5E7EB;
    margin-right: 2px;
}

.review-rating .fa-star.active {
    color: #F59E0B;
}

.review-text {
    color: #6B7280;
    line-height: 1.6;
}

/* Recommendations Section */
.recommendations-section {
    padding: 80px 0;
}

.recommendations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
}

.food-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transition: all 0.3s;
}

.food-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.food-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.food-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.food-card:hover .food-image img {
    transform: scale(1.1);
}

.food-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 107, 53, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
}

.food-card:hover .food-overlay {
    opacity: 1;
}

.quick-add {
    background: white;
    border: none;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
}

.quick-add:hover {
    transform: scale(1.1);
}

.food-info {
    padding: 20px;
}

.food-info h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1F2937;
    margin-bottom: 8px;
}

.food-info h3 a {
    text-decoration: none;
    color: inherit;
}

.food-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--brand-secondary);
    margin-bottom: 8px;
}

.food-rating {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 14px;
    color: #6B7280;
}

@media (max-width: 768px) {
    .product-container {
        padding: 40px 0;
    }

    .product-grid {
        grid-template-columns: 1fr;
        gap: 40px;
    }

    .product-info {
        padding: 24px;
    }

    .product-title {
        font-size: 2rem;
    }

    .main-image {
        height: 300px;
    }

    .thumbnail-grid {
        grid-template-columns: repeat(4, 1fr);
    }

    .option-grid {
        grid-template-columns: 1fr;
    }

    .addon-grid {
        grid-template-columns: 1fr;
    }

    .order-section {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }

    .action-buttons {
        flex-direction: column;
    }

    .btn {
        justify-content: center;
    }

    .reviews-grid {
        grid-template-columns: 1fr;
    }

    .rating-overview {
        flex-direction: column;
        gap: 20px;
    }

    .recommendations-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    }
}
</style>

<script>
// Image Gallery Functionality
let currentImageIndex = 0;
const galleryImages = <?= json_encode($galleryImages) ?>;

function changeImage(imageSrc, thumbnailElement) {
    document.getElementById('mainImage').src = imageSrc;
    document.getElementById('zoomImage').src = imageSrc;

    // Update active thumbnail
    document.querySelectorAll('.thumbnail').forEach(thumb => thumb.classList.remove('active'));
    thumbnailElement.classList.add('active');

    currentImageIndex = Array.from(document.querySelectorAll('.thumbnail')).indexOf(thumbnailElement);
}

// Gallery navigation
document.getElementById('prevBtn').addEventListener('click', () => {
    currentImageIndex = (currentImageIndex - 1 + galleryImages.length) % galleryImages.length;
    const newImage = galleryImages[currentImageIndex];
    changeImage(newImage, document.querySelectorAll('.thumbnail')[currentImageIndex]);
});

document.getElementById('nextBtn').addEventListener('click', () => {
    currentImageIndex = (currentImageIndex + 1) % galleryImages.length;
    const newImage = galleryImages[currentImageIndex];
    changeImage(newImage, document.querySelectorAll('.thumbnail')[currentImageIndex]);
});

// Thumbnail click handlers
document.querySelectorAll('.thumbnail').forEach((thumbnail, index) => {
    thumbnail.addEventListener('click', () => {
        changeImage(galleryImages[index], thumbnail);
    });
});

// Image zoom functionality
const mainImage = document.getElementById('mainImage');
const zoomOverlay = document.getElementById('zoomOverlay');

mainImage.addEventListener('click', () => {
    zoomOverlay.classList.add('active');
});

zoomOverlay.addEventListener('click', () => {
    zoomOverlay.classList.remove('active');
});

// Quantity selector
function updateQuantity(change) {
    const quantityInput = document.getElementById('quantity');
    const hiddenQuantity = document.getElementById('hiddenQuantity');
    let newValue = parseInt(quantityInput.value) + change;

    if (newValue >= 1 && newValue <= 10) {
        quantityInput.value = newValue;
        hiddenQuantity.value = newValue;
        updateTotal();
    }
}

document.getElementById('quantity').addEventListener('input', updateQuantity);

// Update total price
function updateTotal() {
    const quantity = parseInt(document.getElementById('quantity').value);
    const basePrice = <?= (float)$food['price'] ?>;
    const total = basePrice * quantity;
    document.getElementById('totalAmount').textContent = 'KSh ' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

// Option selection
document.querySelectorAll('.option-card input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const optionCards = this.closest('.option-grid').querySelectorAll('.option-card');
        optionCards.forEach(card => card.classList.remove('active'));
        this.closest('.option-card').classList.add('active');
    });
});

// Favorite functionality
async function toggleFavorite(foodId) {
    const btn = document.querySelector('.favorite-btn');
    if (!btn) return;

    try {
        const formData = new FormData();
        formData.append('food_id', String(foodId));

        const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
        if (csrfToken) formData.append('csrf_token', csrfToken);

        const response = await fetch('ajax_favorite.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
        });

        const data = await response.json().catch(() => null);

        if (!response.ok || !data?.success) {
            if (data?.redirect) {
                window.location.href = data.redirect;
                return;
            }
            showToast(data?.message || 'Failed to update favorites.', 'error');
            return;
        }

        const isFavorited = Boolean(data.favorited);
        btn.classList.toggle('active', isFavorited);
        btn.innerHTML = isFavorited
            ? '<i class="fas fa-heart"></i> Saved'
            : '<i class="far fa-heart"></i> Save for Later';

        showToast(isFavorited ? 'Added to favorites!' : 'Removed from favorites!', 'success');
    } catch (error) {
        showToast('Failed to update favorites.', 'error');
    }
}

// Quick add to cart
function quickAddToCart(foodId) {
    // Mock AJAX call
    showToast('Added to cart!', 'success');
}

// Toast notifications
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
        <span>${message}</span>
    `;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('show');
    }, 100);

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => document.body.removeChild(toast), 300);
    }, 3000);
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateTotal();
});
</script>

<style>
/* Toast Notifications */
.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border-radius: 8px;
    padding: 16px 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 1000;
    transform: translateX(400px);
    transition: transform 0.3s ease;
    border-left: 4px solid;
}

.toast.show {
    transform: translateX(0);
}

.toast-success {
    border-left-color: #10B981;
}

.toast-success i {
    color: #10B981;
}

.toast-error {
    border-left-color: #EF4444;
}

.toast-error i {
    color: #EF4444;
}

.toast-info {
    border-left-color: #3B82F6;
}

.toast-info i {
    color: #3B82F6;
}

.toast span {
    font-weight: 500;
    color: #1F2937;
}
</style>

<?php require APP_ROOT . '/backend/includes/layout_bottom.php'; ?>