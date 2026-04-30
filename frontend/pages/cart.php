<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/backend/includes/init.php';

if (is_post()) {
    verify_csrf();
    $removeFoodId = int_from_request($_POST, 'remove_food_id', 0);
    if ($removeFoodId > 0) {
        cart_set($removeFoodId, 0);
        flash_set('success', 'Item removed.');
        redirect('cart.php');
    }

    if (isset($_POST['qty']) && is_array($_POST['qty'])) {
        foreach ($_POST['qty'] as $foodIdRaw => $qtyRaw) {
            $foodId = is_string($foodIdRaw) && preg_match('/^\d+$/', $foodIdRaw) ? (int) $foodIdRaw : 0;
            $qty = is_string($qtyRaw) && preg_match('/^\d+$/', $qtyRaw) ? (int) $qtyRaw : 0;
            if ($foodId > 0) {
                cart_set($foodId, $qty);
            }
        }
        flash_set('success', 'Cart updated.');
    }
    redirect('cart.php');
}

$cart = cart_items();
$foods = [];
$total = 0.0;
$itemCount = 0;

if ($cart) {
    $ids = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT food_id, name, price, image, status FROM foods WHERE food_id IN ($placeholders)");
    $stmt->execute($ids);
    $foods = $stmt->fetchAll();

    foreach ($foods as $food) {
        $qty = (int) ($cart[(int) $food['food_id']] ?? 0);
        if ($qty > 0 && (int) $food['status'] === 1) {
            $total += ((float) $food['price']) * $qty;
            $itemCount += $qty;
        }
    }
}

// Mock recommended foods
$recommendedFoods = db()->query("SELECT food_id, name, price, image FROM foods WHERE status = 1 ORDER BY RAND() LIMIT 6")->fetchAll();

$page_title = "Shopping Cart - " . APP_NAME;
require APP_ROOT . '/backend/includes/layout_top.php';
?>

<!-- Cart Hero Section -->
<section class="cart-hero">
    <div class="container">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Your Shopping Cart</h1>
                <p><?php if ($cart): ?><?= $itemCount ?> delicious item<?= $itemCount > 1 ? 's' : '' ?> waiting for
                        you<?php else: ?>Ready to add some amazing food?<?php endif; ?></p>
            </div>
            <div class="hero-actions">
                <a href="<?= e(url('index.php')) ?>" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i>
                    Continue Shopping
                </a>
                <?php if ($cart): ?>
                    <a href="<?= e(url('checkout.php')) ?>" class="btn btn-primary">
                        <i class="fas fa-credit-card"></i>
                        Checkout Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="progress-indicator">
            <div class="progress-step active">
                <div class="step-icon"><i class="fas fa-shopping-cart"></i></div>
                <span>Cart</span>
            </div>
            <div class="progress-line"></div>
            <div class="progress-step">
                <div class="step-icon"><i class="fas fa-map-marker-alt"></i></div>
                <span>Checkout</span>
            </div>
            <div class="progress-line"></div>
            <div class="progress-step">
                <div class="step-icon"><i class="fas fa-check-circle"></i></div>
                <span>Complete</span>
            </div>
        </div>
    </div>
</section>

<div class="container cart-container">
    <?php if (!$cart): ?>
        <!-- Empty Cart State -->
        <div class="empty-cart">
            <div class="empty-cart-illustration">
                <div class="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="floating-elements">
                    <div class="food-item pizza">
                        <img src="https://images.unsplash.com/photo-1565299624946-b28f40a0ca4b?auto=format&fit=crop&w=60&q=80"
                            alt="Pizza">
                    </div>
                    <div class="food-item burger">
                        <img src="https://images.unsplash.com/photo-1571091718767-18b5b1457add?auto=format&fit=crop&w=60&q=80"
                            alt="Burger">
                    </div>
                    <div class="food-item sushi">
                        <img src="https://images.unsplash.com/photo-1579871494447-9811cf80d66c?auto=format&fit=crop&w=60&q=80"
                            alt="Sushi">
                    </div>
                </div>
            </div>
            <div class="empty-cart-content">
                <h2>Your cart is empty</h2>
                <p>Discover amazing food from our menu and add some delicious items to your cart</p>
                <div class="empty-cart-actions">
                    <a href="<?= e(url('food.php')) ?>" class="btn btn-primary btn-large">
                        <i class="fas fa-utensils"></i>
                        Explore Menu
                    </a>
                    <a href="<?= e(url('index.php')) ?>" class="btn btn-outline btn-large">
                        <i class="fas fa-home"></i>
                        Go Home
                    </a>
                </div>
                <div class="cart-benefits">
                    <div class="benefit">
                        <i class="fas fa-truck"></i>
                        <span>Free Delivery</span>
                    </div>
                    <div class="benefit">
                        <i class="fas fa-clock"></i>
                        <span>30min or Free</span>
                    </div>
                    <div class="benefit">
                        <i class="fas fa-shield-alt"></i>
                        <span>Secure Payment</span>
                    </div>
                    <div class="benefit">
                        <i class="fas fa-star"></i>
                        <span>Quality Guarantee</span>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            <!-- Cart Items Section -->
            <div class="cart-items-section">
                <form method="post" class="cart-form">
                    <?= csrf_field() ?>

                    <div class="cart-header">
                        <div class="cart-title">
                            <h2><i class="fas fa-shopping-bag"></i> Your Items</h2>
                            <span class="item-count"><?= $itemCount ?> item<?= $itemCount > 1 ? 's' : '' ?></span>
                        </div>
                        <div class="cart-actions">
                            <button type="submit" class="btn btn-outline update-btn">
                                <i class="fas fa-sync-alt"></i>
                                Update Cart
                            </button>
                        </div>
                    </div>

                    <div class="cart-items">
                        <?php foreach ($foods as $food):
                            $foodId = (int) $food['food_id'];
                            $qty = (int) ($cart[$foodId] ?? 0);
                            if ($qty <= 0)
                                continue;
                            $lineTotal = ((float) $food['price']) * $qty;
                            $img = (string) ($food['image'] ?? '');
                            $imgUrl = get_food_image_url($img);
                            ?>
                            <div class="cart-item" data-food-id="<?= $foodId ?>">
                                <div class="item-image">
                                    <img src="<?= e($imgUrl) ?>"
                                        onerror="this.onerror=null;this.src='<?= e(url('assets/img/food-placeholder.svg')) ?>';"
                                        alt="<?= e($food['name']) ?>" loading="lazy">
                                    <?php if ((int) $food['status'] !== 1): ?>
                                        <div class="item-unavailable">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            Unavailable
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="item-details">
                                    <div class="item-header">
                                        <h3 class="item-title">
                                            <a
                                                href="<?= e(url('food_details.php?food_id=' . $foodId)) ?>"><?= e($food['name']) ?></a>
                                        </h3>
                                        <button type="submit" name="remove_food_id" value="<?= $foodId ?>" class="remove-btn"
                                            title="Remove item">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>

                                    <div class="item-meta">
                                        <div class="item-price">KSh <?= number_format((float) $food['price'], 2) ?> each</div>
                                        <div class="item-rating">
                                            <i class="fas fa-star"></i>
                                            <span>4.5</span>
                                            <span class="reviews-count">(120 reviews)</span>
                                        </div>
                                    </div>

                                    <div class="item-customizations">
                                        <span class="customization-tag">Regular Size</span>
                                        <span class="customization-tag">Medium Spice</span>
                                    </div>
                                </div>

                                <div class="item-controls">
                                    <div class="quantity-selector">
                                        <button type="button" class="qty-btn minus-btn"
                                            onclick="updateQuantity(<?= $foodId ?>, -1)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" name="qty[<?= $foodId ?>]" value="<?= $qty ?>" min="0" max="99"
                                            class="qty-input" readonly>
                                        <button type="button" class="qty-btn plus-btn"
                                            onclick="updateQuantity(<?= $foodId ?>, 1)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>

                                    <div class="item-total">
                                        <span class="total-amount">KSh <?= number_format($lineTotal, 2) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="cart-footer">
                        <div class="cart-notice">
                            <i class="fas fa-info-circle"></i>
                            <span>Prices and availability are subject to change</span>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Cart Summary Sidebar -->
            <div class="cart-summary">
                <div class="summary-sticky">
                    <div class="summary-card">
                        <div class="summary-header">
                            <h3><i class="fas fa-receipt"></i> Order Summary</h3>
                            <div class="order-estimate">
                                <i class="fas fa-clock"></i>
                                <span>Estimated delivery: 25-35 min</span>
                            </div>
                        </div>

                        <div class="summary-breakdown">
                            <div class="summary-row">
                                <span>Subtotal (<?= $itemCount ?> items)</span>
                                <span>KSh <?= number_format($total, 2) ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Delivery Fee</span>
                                <span class="free-text">FREE</span>
                            </div>
                            <div class="summary-row">
                                <span>Service Fee</span>
                                <span>KSh <?= number_format($total * 0.05, 2) ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Tax</span>
                                <span>KSh <?= number_format($total * 0.08, 2) ?></span>
                            </div>

                            <div class="summary-divider"></div>

                            <div class="summary-row total-row">
                                <span>Total</span>
                                <span class="total-amount">KSh <?= number_format($total * 1.13, 2) ?></span>
                            </div>
                        </div>

                        <div class="promo-section">
                            <div class="promo-input-group">
                                <input type="text" placeholder="Enter promo code" class="promo-input" id="promoCode">
                                <button type="button" class="btn btn-outline promo-btn" onclick="applyPromo()">
                                    Apply
                                </button>
                            </div>
                            <div class="promo-suggestions">
                                <span class="promo-tag" onclick="applyPromoCode('WELCOME10')">WELCOME10</span>
                                <span class="promo-tag" onclick="applyPromoCode('FOODIE20')">FOODIE20</span>
                            </div>
                        </div>

                        <a href="<?= e(url('checkout.php')) ?>" class="btn btn-primary checkout-btn">
                            <i class="fas fa-credit-card"></i>
                            Proceed to Checkout
                        </a>

                        <div class="checkout-benefits">
                            <div class="benefit">
                                <i class="fas fa-lock"></i>
                                <span>Secure SSL</span>
                            </div>
                            <div class="benefit">
                                <i class="fas fa-shield-alt"></i>
                                <span>Protected</span>
                            </div>
                            <div class="benefit">
                                <i class="fas fa-truck"></i>
                                <span>Fast Delivery</span>
                            </div>
                        </div>
                    </div>

                    <!-- Recommendations -->
                    <div class="recommendations-card">
                        <h4><i class="fas fa-lightbulb"></i> You might also like</h4>
                        <div class="recommendations-grid">
                            <?php foreach (array_slice($recommendedFoods, 0, 3) as $recFood):
                                $recImg = (string) ($recFood['image'] ?? '');
                                $recImgUrl = get_food_image_url($recImg);
                                ?>
                                <div class="recommendation-item" onclick="addToCart(<?= $recFood['food_id'] ?>)">
                                    <div class="rec-image">
                                        <img src="<?= e($recImgUrl) ?>"
                                            onerror="this.onerror=null;this.src='<?= e(url('assets/img/food-placeholder.svg')) ?>';"
                                            alt="<?= e($recFood['name']) ?>">
                                        <div class="rec-overlay">
                                            <i class="fas fa-plus"></i>
                                        </div>
                                    </div>
                                    <div class="rec-info">
                                        <h5><?= e($recFood['name']) ?></h5>
                                        <div class="rec-price">KSh <?= number_format((float) $recFood['price'], 2) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Continue Shopping Modal -->
<div class="modal-overlay" id="continueModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Continue Shopping?</h3>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p>You have items in your cart. Would you like to continue shopping or proceed to checkout?</p>
            <div class="modal-actions">
                <a href="<?= e(url('food.php')) ?>" class="btn btn-outline">Continue Shopping</a>
                <a href="<?= e(url('checkout.php')) ?>" class="btn btn-primary">Checkout Now</a>
            </div>
        </div>
    </div>
</div>

<style>
    /* Cart Hero */
    .cart-hero {
        background: linear-gradient(135deg, rgba(76, 175, 80, 0.95) 0%, rgba(255, 159, 67, 0.9) 100%);
        padding: 60px 0;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .cart-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?auto=format&fit=crop&w=1920&q=80') center/cover;
        opacity: 0.1;
    }

    .hero-content {
        position: relative;
        z-index: 2;
        text-align: center;
        max-width: 800px;
        margin: 0 auto;
    }

    .hero-text h1 {
        font-size: 3rem;
        font-weight: 900;
        margin-bottom: 16px;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    .hero-text p {
        font-size: 1.25rem;
        opacity: 0.9;
        margin-bottom: 32px;
    }

    .hero-actions {
        display: flex;
        justify-content: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .progress-indicator {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-top: 40px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    .progress-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        position: relative;
    }

    .progress-step.active .step-icon {
        background: white;
        color: var(--brand-primary);
    }

    .progress-step.active span {
        color: white;
        font-weight: 600;
    }

    .step-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        color: rgba(255, 255, 255, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        transition: all 0.3s;
    }

    .progress-step span {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.7);
        font-weight: 500;
    }

    .progress-line {
        width: 80px;
        height: 2px;
        background: rgba(255, 255, 255, 0.3);
        margin: 0 16px;
    }

    .progress-line.active {
        background: white;
    }

    /* Empty Cart */
    .empty-cart {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        align-items: center;
        background: white;
        border-radius: 24px;
        padding: 60px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        margin: 40px 0;
    }

    .empty-cart-illustration {
        position: relative;
        text-align: center;
    }

    .cart-icon {
        font-size: 120px;
        color: #F3F4F6;
        position: relative;
        display: inline-block;
    }

    .cart-icon i {
        position: relative;
        z-index: 2;
    }

    .floating-elements {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
    }

    .food-item {
        position: absolute;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        overflow: hidden;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        animation: float 6s ease-in-out infinite;
    }

    .food-item.pizza {
        top: 20px;
        left: 20px;
        animation-delay: 0s;
    }

    .food-item.burger {
        top: 40px;
        right: 30px;
        animation-delay: 2s;
    }

    .food-item.sushi {
        bottom: 30px;
        left: 40px;
        animation-delay: 4s;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0px) rotate(0deg);
        }

        25% {
            transform: translateY(-10px) rotate(5deg);
        }

        50% {
            transform: translateY(-20px) rotate(0deg);
        }

        75% {
            transform: translateY(-10px) rotate(-5deg);
        }
    }

    .empty-cart-content {
        text-align: center;
    }

    .empty-cart-content h2 {
        font-size: 2.5rem;
        font-weight: 800;
        color: #1F2937;
        margin-bottom: 16px;
    }

    .empty-cart-content p {
        font-size: 1.125rem;
        color: #6B7280;
        margin-bottom: 32px;
        line-height: 1.6;
    }

    .empty-cart-actions {
        display: flex;
        gap: 16px;
        justify-content: center;
        margin-bottom: 40px;
        flex-wrap: wrap;
    }

    .cart-benefits {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .benefit {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        background: #F9FAFB;
        border-radius: 12px;
        font-weight: 600;
        color: #374151;
    }

    .benefit i {
        color: var(--brand-primary);
        font-size: 18px;
    }

    /* Cart Layout */
    .cart-container {
        padding: 40px 0;
    }

    .cart-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 40px;
        align-items: start;
    }

    .cart-items-section {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }

    .cart-header {
        padding: 32px;
        border-bottom: 1px solid #E5E7EB;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .cart-title h2 {
        font-size: 1.75rem;
        font-weight: 800;
        color: #1F2937;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .item-count {
        background: #F3F4F6;
        color: #6B7280;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
    }

    .cart-actions {
        display: flex;
        gap: 12px;
    }

    /* Cart Items */
    .cart-items {
        padding: 0;
    }

    .cart-item {
        display: flex;
        align-items: center;
        padding: 32px;
        border-bottom: 1px solid #F3F4F6;
        transition: all 0.3s ease;
        position: relative;
    }

    .cart-item:hover {
        background: #FAFAFA;
        transform: translateX(4px);
    }

    .cart-item:last-child {
        border-bottom: none;
    }

    .item-image {
        position: relative;
        width: 100px;
        height: 100px;
        border-radius: 16px;
        overflow: hidden;
        margin-right: 24px;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .item-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s;
    }

    .cart-item:hover .item-image img {
        transform: scale(1.05);
    }

    .item-unavailable {
        position: absolute;
        inset: 0;
        background: rgba(239, 68, 68, 0.9);
        color: white;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        text-align: center;
    }

    .item-details {
        flex: 1;
        margin-right: 24px;
    }

    .item-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
    }

    .item-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1F2937;
        margin: 0;
    }

    .item-title a {
        color: inherit;
        text-decoration: none;
        transition: color 0.3s;
    }

    .item-title a:hover {
        color: var(--brand-primary);
    }

    .remove-btn {
        background: #FEE2E2;
        color: #DC2626;
        border: none;
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s;
        opacity: 0;
    }

    .cart-item:hover .remove-btn {
        opacity: 1;
    }

    .remove-btn:hover {
        background: #FECACA;
        transform: scale(1.1);
    }

    .item-meta {
        display: flex;
        flex-direction: column;
        gap: 4px;
        margin-bottom: 12px;
    }

    .item-price {
        color: #6B7280;
        font-size: 14px;
    }

    .item-rating {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 14px;
        color: #F59E0B;
    }

    .reviews-count {
        color: #9CA3AF;
        font-size: 12px;
    }

    .item-customizations {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .customization-tag {
        background: #F0F9FF;
        color: #0369A1;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }

    .item-controls {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 12px;
        min-width: 120px;
    }

    .quantity-selector {
        display: flex;
        align-items: center;
        background: #F9FAFB;
        border-radius: 12px;
        padding: 4px;
        border: 1px solid #E5E7EB;
    }

    .qty-btn {
        background: white;
        border: none;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #6B7280;
        transition: all 0.2s;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .qty-btn:hover {
        color: var(--brand-primary);
        transform: scale(1.1);
    }

    .qty-input {
        width: 50px;
        text-align: center;
        border: none;
        background: transparent;
        font-weight: 700;
        font-size: 16px;
        color: #1F2937;
        outline: none;
    }

    .item-total .total-amount {
        font-size: 1.125rem;
        font-weight: 800;
        color: var(--brand-secondary);
    }

    .cart-footer {
        padding: 24px 32px;
        background: #F9FAFB;
        border-top: 1px solid #E5E7EB;
    }

    .cart-notice {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #6B7280;
        font-size: 14px;
    }

    .cart-notice i {
        color: #F59E0B;
    }

    /* Cart Summary */
    .cart-summary {
        position: sticky;
        top: 100px;
    }

    .summary-sticky {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .summary-card {
        background: white;
        border-radius: 20px;
        padding: 32px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }

    .summary-header {
        margin-bottom: 24px;
    }

    .summary-header h3 {
        font-size: 1.5rem;
        font-weight: 800;
        color: #1F2937;
        margin: 0 0 12px 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .order-estimate {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #6B7280;
        font-size: 14px;
    }

    .order-estimate i {
        color: #10B981;
    }

    .summary-breakdown {
        margin-bottom: 24px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        font-size: 15px;
    }

    .summary-row span:first-child {
        color: #6B7280;
    }

    .summary-row span:last-child {
        font-weight: 600;
        color: #1F2937;
    }

    .free-text {
        color: #10B981;
        font-weight: 700;
    }

    .summary-divider {
        height: 1px;
        background: #E5E7EB;
        margin: 16px 0;
    }

    .total-row {
        font-size: 18px;
        font-weight: 800;
        color: #1F2937;
        padding-top: 16px;
        border-top: 2px solid #E5E7EB;
    }

    .total-row .total-amount {
        color: var(--brand-secondary);
        font-size: 20px;
    }

    .promo-section {
        margin-bottom: 24px;
    }

    .promo-input-group {
        display: flex;
        gap: 8px;
        margin-bottom: 12px;
    }

    .promo-input {
        flex: 1;
        padding: 12px 16px;
        border: 2px solid #E5E7EB;
        border-radius: 12px;
        outline: none;
        font-size: 14px;
        transition: border-color 0.3s;
    }

    .promo-input:focus {
        border-color: var(--brand-primary);
    }

    .promo-btn {
        padding: 12px 20px;
        white-space: nowrap;
    }

    .promo-suggestions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .promo-tag {
        background: #F3F4F6;
        color: var(--brand-primary);
        padding: 6px 12px;
        border-radius: 16px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .promo-tag:hover {
        background: var(--brand-primary);
        color: white;
    }

    .checkout-btn {
        width: 100%;
        padding: 16px;
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 24px;
    }

    .checkout-benefits {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }

    .checkout-benefits .benefit {
        flex: 1;
        min-width: 80px;
        justify-content: center;
        padding: 12px 8px;
        background: #F9FAFB;
        border-radius: 8px;
        font-size: 12px;
    }

    /* Recommendations */
    .recommendations-card {
        background: white;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }

    .recommendations-card h4 {
        font-size: 1.125rem;
        font-weight: 700;
        color: #1F2937;
        margin: 0 0 20px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .recommendations-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .recommendation-item {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px;
        background: #FAFAFA;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s;
        border: 1px solid transparent;
    }

    .recommendation-item:hover {
        background: white;
        border-color: rgba(76, 175, 80, 0.35);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(76, 175, 80, 0.14);
    }

    .rec-image {
        position: relative;
        width: 60px;
        height: 60px;
        border-radius: 12px;
        overflow: hidden;
        flex-shrink: 0;
    }

    .rec-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .rec-overlay {
        position: absolute;
        inset: 0;
        background: rgba(76, 175, 80, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .recommendation-item:hover .rec-overlay {
        opacity: 1;
    }

    .rec-info {
        flex: 1;
    }

    .rec-info h5 {
        font-size: 14px;
        font-weight: 600;
        color: #1F2937;
        margin: 0 0 4px 0;
    }

    .rec-price {
        font-size: 14px;
        font-weight: 700;
        color: var(--brand-secondary);
    }

    /* Modal */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        backdrop-filter: blur(4px);
    }

    .modal-overlay.active {
        display: flex;
    }

    .modal-content {
        background: white;
        border-radius: 20px;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    }

    .modal-header {
        padding: 24px 24px 0 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h3 {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1F2937;
        margin: 0;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 20px;
        color: #6B7280;
        cursor: pointer;
        padding: 4px;
        border-radius: 50%;
        transition: all 0.3s;
    }

    .modal-close:hover {
        background: #F3F4F6;
        color: #1F2937;
    }

    .modal-body {
        padding: 24px;
    }

    .modal-body p {
        color: #6B7280;
        margin-bottom: 24px;
        line-height: 1.6;
    }

    .modal-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }

    /* Buttons */
    .btn {
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 14px;
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

    .btn-outline {
        background: transparent;
        border: 2px solid #D1D5DB;
        color: #6B7280;
    }

    .btn-outline:hover {
        border-color: var(--brand-primary);
        color: var(--text-primary);
    }

    .btn-large {
        padding: 16px 32px;
        font-size: 16px;
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .cart-layout {
            grid-template-columns: 1fr;
            gap: 32px;
        }

        .cart-summary {
            position: static;
        }
    }

    @media (max-width: 768px) {
        .cart-hero {
            padding: 40px 0;
        }

        .hero-text h1 {
            font-size: 2rem;
        }

        .hero-text p {
            font-size: 1rem;
        }

        .hero-actions {
            flex-direction: column;
            align-items: center;
        }

        .progress-indicator {
            flex-wrap: wrap;
            gap: 8px;
        }

        .progress-line {
            width: 40px;
        }

        .empty-cart {
            grid-template-columns: 1fr;
            gap: 32px;
            padding: 32px;
        }

        .cart-benefits {
            grid-template-columns: 1fr;
        }

        .cart-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 20px;
            padding: 24px;
        }

        .item-controls {
            width: 100%;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }

        .quantity-selector {
            order: 1;
        }

        .item-total {
            order: 2;
        }

        .summary-breakdown {
            font-size: 14px;
        }

        .checkout-benefits {
            flex-direction: column;
        }

        .checkout-benefits .benefit {
            min-width: auto;
        }
    }
</style>

<script>
    // Quantity management
    function updateQuantity(foodId, delta) {
        const input = document.querySelector(`input[name="qty[${foodId}]"]`);
        const currentValue = parseInt(input.value) || 0;
        const newValue = Math.max(0, currentValue + delta);

        input.value = newValue;

        // Update item total
        updateItemTotal(foodId, newValue);

        // Update overall total
        updateCartTotal();

        // Show/hide remove button
        const cartItem = input.closest('.cart-item');
        const removeBtn = cartItem.querySelector('.remove-btn');
        if (newValue === 0) {
            removeBtn.style.opacity = '1';
            cartItem.style.opacity = '0.6';
        } else {
            removeBtn.style.opacity = '0';
            cartItem.style.opacity = '1';
        }
    }

    function updateItemTotal(foodId, quantity) {
        const cartItem = document.querySelector(`[data-food-id="${foodId}"]`);
        const priceText = cartItem.querySelector('.item-price').textContent;
        const price = parseFloat(priceText.replace('KSh ', '').replace(',', ''));

        const totalElement = cartItem.querySelector('.total-amount');
        const newTotal = price * quantity;
        totalElement.textContent = 'KSh ' + newTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function updateCartTotal() {
        let subtotal = 0;
        let itemCount = 0;

        document.querySelectorAll('.cart-item').forEach(item => {
            const qtyInput = item.querySelector('.qty-input');
            const quantity = parseInt(qtyInput.value) || 0;

            if (quantity > 0) {
                const priceText = item.querySelector('.item-price').textContent;
                const price = parseFloat(priceText.replace('KSh ', '').replace(',', ''));
                subtotal += price * quantity;
                itemCount += quantity;
            }
        });

        const deliveryFee = 0; // Free delivery
        const serviceFee = subtotal * 0.05;
        const tax = subtotal * 0.08;
        const total = subtotal + deliveryFee + serviceFee + tax;

        // Update summary
        document.querySelector('.summary-row:nth-child(1) span:last-child').textContent = 'KSh ' + subtotal.toFixed(2);
        document.querySelector('.summary-row:nth-child(3) span:last-child').textContent = 'KSh ' + serviceFee.toFixed(2);
        document.querySelector('.summary-row:nth-child(4) span:last-child').textContent = 'KSh ' + tax.toFixed(2);
        document.querySelector('.total-amount').textContent = 'KSh ' + total.toFixed(2);

        // Update item count
        document.querySelector('.item-count').textContent = itemCount + ' item' + (itemCount > 1 ? 's' : '');
    }

    // Promo code functionality
    function applyPromo() {
        const promoCode = document.getElementById('promoCode').value.toUpperCase();
        if (promoCode) {
            showToast('Promo code applied: ' + promoCode, 'success');
        }
    }

    function applyPromoCode(code) {
        document.getElementById('promoCode').value = code;
        applyPromo();
    }

    // Add to cart from recommendations
    function addToCart(foodId) {
        // Mock AJAX call - in real app, this would make an API call
        showToast('Added to cart!', 'success');

        // Animate the recommendation item
        const item = event.currentTarget;
        item.style.transform = 'scale(0.95)';
        setTimeout(() => {
            item.style.transform = 'scale(1)';
        }, 150);
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

    // Modal functionality
    function closeModal() {
        document.getElementById('continueModal').classList.remove('active');
    }

    // Progress indicator
    function updateProgress() {
        const steps = document.querySelectorAll('.progress-step');
        const lines = document.querySelectorAll('.progress-line');

        steps.forEach((step, index) => {
            if (index === 0) { // Cart step
                step.classList.add('active');
            }
        });

        lines.forEach(line => line.classList.add('active'));
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function () {
        updateProgress();

        // Add smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
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