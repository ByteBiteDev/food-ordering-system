<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/backend/includes/init.php';

require_login();

$cart = cart_items();
if (!$cart) {
    flash_set('error', 'Your cart is empty.');
    redirect('cart.php');
}

$ids = array_map('intval', array_keys($cart));
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = db()->prepare("SELECT food_id, name, price, status FROM foods WHERE food_id IN ($placeholders) AND status = 1");
$stmt->execute($ids);
$foods = $stmt->fetchAll();

$items = [];
$total = 0.0;
foreach ($foods as $food) {
    $foodId = (int) $food['food_id'];
    $qty = (int) ($cart[$foodId] ?? 0);
    if ($qty <= 0) {
        continue;
    }

    $line = ((float) $food['price']) * $qty;
    $total += $line;
    $items[] = [
        'food_id' => $foodId,
        'name' => (string) $food['name'],
        'unit_price' => (float) $food['price'],
        'quantity' => $qty,
        'line_total' => $line,
    ];
}

if (!$items) {
    flash_set('error', 'No available items in your cart.');
    cart_clear();
    redirect('cart.php');
}

$fullName = '';
$phone = '';
$country = '';
$addressLine = '';
$paymentMethod = 'telebirr';
$errors = [];

if (is_post()) {
    verify_csrf();
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $country = trim((string) ($_POST['country'] ?? ''));
    $addressLine = trim((string) ($_POST['address_line'] ?? ''));
    $paymentMethod = trim((string) ($_POST['payment_method'] ?? 'telebirr'));

    $address = $fullName . "\n" . $phone . "\n" . $country . "\n" . $addressLine;

    if ($fullName === '' || $phone === '' || $country === '' || $addressLine === '') {
        $errors[] = 'All delivery address fields are required.';
    }

    if (!in_array($paymentMethod, ['telebirr', 'chapa'], true)) {
        $errors[] = 'Please select a valid payment method.';
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $user = current_user();
            // Create the order first, then mark it as paid on `payment.php`.
            // This keeps admin workflows intact (only paid orders become `Pending`).
            $stmtOrder = $pdo->prepare('INSERT INTO orders (user_id, total, status, address, payment_method, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
            $stmtOrder->execute([(int) $user['user_id'], $total, 'Pending Payment', $address, $paymentMethod]);
            $orderId = (int) $pdo->lastInsertId();

            $stmtItem = $pdo->prepare('INSERT INTO order_items (order_id, food_id, quantity, unit_price, line_total) VALUES (?, ?, ?, ?, ?)');
            foreach ($items as $it) {
                $stmtItem->execute([$orderId, $it['food_id'], $it['quantity'], $it['unit_price'], $it['line_total']]);
            }

            $pdo->commit();
            cart_clear();
            redirect('payment.php?order_id=' . $orderId . '&method=' . $paymentMethod);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Failed to place order. Please try again.';
        }
    }
}

$page_title = 'Secure Checkout - ' . APP_NAME;
require APP_ROOT . '/backend/includes/layout_top.php';
?>

<!-- Enhanced Checkout Hero -->
<section class="checkout-hero">
    <div class="checkout-hero-bg">
        <div class="checkout-hero-pattern"></div>
    </div>
    <div class="container checkout-hero__inner">
        <div class="checkout-hero-content">
            <div class="checkout-breadcrumb">
                <a href="cart.php" class="breadcrumb-link">
                    <i class="fas fa-arrow-left"></i>
                    Back to Cart
                </a>
            </div>

            <div class="checkout-hero-text">
                <span class="checkout-eyebrow">Secure Checkout</span>
                <h1 class="checkout-title">Complete Your Order</h1>
                <p class="checkout-subtitle">Confirm delivery details and choose your payment method.</p>

                <div class="checkout-trust-indicators">
                    <div class="trust-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>SSL Secured</span>
                    </div>
                    <div class="trust-item">
                        <i class="fas fa-clock"></i>
                        <span>Reliable delivery</span>
                    </div>
                    <div class="trust-item">
                        <i class="fas fa-utensils"></i>
                        <span>Order tracking</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="checkout-progress-wrapper">
            <div class="checkout-progress">
                <div class="progress-step active completed">
                    <div class="step-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="step-content">
                        <span class="step-number">1</span>
                        <span class="step-title">Cart</span>
                        <span class="step-desc">Items selected</span>
                    </div>
                </div>

                <div class="progress-connector active"></div>

                <div class="progress-step active">
                    <div class="step-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="step-content">
                        <span class="step-number">2</span>
                        <span class="step-title">Checkout</span>
                        <span class="step-desc">Delivery details</span>
                    </div>
                </div>

                <div class="progress-connector"></div>

                <div class="progress-step">
                    <div class="step-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="step-content">
                        <span class="step-number">3</span>
                        <span class="step-title">Payment</span>
                        <span class="step-desc">Secure payment</span>
                    </div>
                </div>

                <div class="progress-connector"></div>

                <div class="progress-step">
                    <div class="step-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="step-content">
                        <span class="step-number">4</span>
                        <span class="step-title">Complete</span>
                        <span class="step-desc">Order confirmed</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container checkout-layout">
    <main class="checkout-main">
        <!-- Delivery Information Card -->
        <div class="checkout-section-card">
            <div class="section-header">
                <div class="section-header-content">
                    <div class="section-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div>
                        <h2 class="section-title">Delivery Information</h2>
                        <p class="section-subtitle">Where should we deliver your order?</p>
                    </div>
                </div>
                <div class="section-badge">
                    <i class="fas fa-clock"></i>
                    <span>30-45 min</span>
                </div>
            </div>

            <?php if ($errors): ?>
                <div class="checkout-alert checkout-alert--error">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="alert-content">
                        <h4>Please fix the following errors:</h4>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li>
                                    <?= e($error) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" class="checkout-form" id="checkoutForm">
                <?= csrf_field() ?>

                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fas fa-user"></i>
                        Personal Details
                    </h3>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="full_name" class="form-label">
                                Full Name <span class="required">*</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" id="full_name" name="full_name" class="form-input"
                                    placeholder="Enter your full name" value="<?= e($fullName) ?>" required />
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label">
                                Phone Number <span class="required">*</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fas fa-phone input-icon"></i>
                                <input type="tel" id="phone" name="phone" class="form-input"
                                    placeholder="+251 911 23 45 67" value="<?= e($phone) ?>" required />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fas fa-map-marker-alt"></i>
                        Delivery Address
                    </h3>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="country" class="form-label">
                                Country/Region <span class="required">*</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fas fa-globe input-icon"></i>
                                <input type="text" id="country" name="country" class="form-input" placeholder="Ethiopia"
                                    value="<?= e($country) ?>" required />
                            </div>
                        </div>

                        <div class="form-group form-group--full">
                            <label for="address_line" class="form-label">
                                Street Address <span class="required">*</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fas fa-home input-icon"></i>
                                <input type="text" id="address_line" name="address_line" class="form-input"
                                    placeholder="House number, street name, landmark, city"
                                    value="<?= e($addressLine) ?>" required />
                            </div>
                            <small class="form-help">Include specific details to help our delivery partner find you
                                easily</small>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fas fa-credit-card"></i>
                        Payment Method
                    </h3>

                    <div class="payment-methods">
                        <label class="payment-method-card <?= $paymentMethod === 'telebirr' ? 'active' : '' ?>">
                            <input type="radio" name="payment_method" value="telebirr" <?= $paymentMethod === 'telebirr' ? 'checked' : '' ?>
                            class="payment-radio"
                            />
                            <div class="payment-method-content">
                                <div class="payment-method-header">
                                    <div class="payment-method-logo">
                                        <img src="assets/img/telebirr-logo.svg" alt="TeleBirr"
                                            onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiByeD0iOCIgZmlsbD0iIzMzMzMzMyIvPgo8dGV4dCB4PSIyMCIgeT0iMjUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiPkI8L3RleHQ+Cjwvc3ZnPg=='" />
                                    </div>
                                    <div class="payment-method-info">
                                        <h4>TeleBirr</h4>
                                        <p>Mobile wallet payment</p>
                                    </div>
                                </div>
                                <div class="payment-method-features">
                                    <span class="feature-tag">Instant</span>
                                    <span class="feature-tag">Secure</span>
                                </div>
                            </div>
                            <div class="payment-checkmark">
                                <i class="fas fa-check"></i>
                            </div>
                        </label>

                        <label class="payment-method-card <?= $paymentMethod === 'chapa' ? 'active' : '' ?>">
                            <input type="radio" name="payment_method" value="chapa" <?= $paymentMethod === 'chapa' ? 'checked' : '' ?>
                            class="payment-radio"
                            />
                            <div class="payment-method-content">
                                <div class="payment-method-header">
                                    <div class="payment-method-logo">
                                        <img src="assets/img/chapa-logo.svg" alt="Chapa"
                                            onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiByeD0iOCIgZmlsbD0iIzMzMzMzMyIvPgo8dGV4dCB4PSIyMCIgeT0iMjUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiPkM8L3RleHQ+Cjwvc3ZnPg=='" />
                                    </div>
                                    <div class="payment-method-info">
                                        <h4>Chapa</h4>
                                        <p>Cards & mobile money</p>
                                    </div>
                                </div>
                                <div class="payment-method-features">
                                    <span class="feature-tag">Multiple Options</span>
                                    <span class="feature-tag">Trusted</span>
                                </div>
                            </div>
                            <div class="payment-checkmark">
                                <i class="fas fa-check"></i>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-large" id="submitBtn">
                        <span class="btn-text">
                            <i class="fas fa-lock"></i>
                            Continue to Payment
                        </span>
                        <span class="btn-loading" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i>
                            Processing...
                        </span>
                    </button>

                    <a href="cart.php" class="btn btn-secondary btn-large">
                        <i class="fas fa-arrow-left"></i>
                        Back to Cart
                    </a>
                </div>
            </form>
        </div>
    </main>

    <!-- Enhanced Order Summary Sidebar -->
    <aside class="checkout-sidebar">
        <div class="order-summary-card">
            <div class="summary-header">
                <div class="summary-icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <div>
                    <h3 class="summary-title">Order Summary</h3>
                    <p class="summary-subtitle">
                        <?= count($items) ?> item
                        <?= count($items) === 1 ? '' : 's' ?> in your cart
                    </p>
                </div>
            </div>

            <div class="order-items">
                <?php foreach ($items as $item): ?>
                    <div class="order-item">
                        <div class="item-info">
                            <h4 class="item-name">
                                <?= e($item['name']) ?>
                            </h4>
                            <span class="item-quantity">Qty:
                                <?= (int) $item['quantity'] ?>
                            </span>
                        </div>
                        <div class="item-price">
                            <span class="price-amount">KSh
                                <?= number_format((float) $item['line_total'], 2) ?>
                            </span>
                            <span class="price-unit">KSh
                                <?= number_format((float) $item['unit_price'], 2) ?> each
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="order-breakdown">
                <div class="breakdown-row">
                    <span class="breakdown-label">Subtotal</span>
                    <span class="breakdown-value">KSh
                        <?= number_format($total, 2) ?>
                    </span>
                </div>

                <div class="breakdown-row">
                    <span class="breakdown-label">
                        Delivery Fee
                        <i class="fas fa-info-circle" title="Free delivery on orders over KSh 500"></i>
                    </span>
                    <span class="breakdown-value free">FREE</span>
                </div>

                <div class="breakdown-row">
                    <span class="breakdown-label">Service Fee (8%)</span>
                    <span class="breakdown-value">KSh
                        <?= number_format($total * 0.08, 2) ?>
                    </span>
                </div>

                <div class="breakdown-divider"></div>

                <div class="breakdown-row breakdown-total">
                    <span class="breakdown-label">Total</span>
                    <span class="breakdown-value">KSh
                        <?= number_format($total * 1.08, 2) ?>
                    </span>
                </div>
            </div>

            <div class="order-benefits">
                <div class="benefit-item">
                    <i class="fas fa-shipping-fast"></i>
                    <span>Free delivery within 45 minutes</span>
                </div>
                <div class="benefit-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>100% secure payment</span>
                </div>
                <div class="benefit-item">
                    <i class="fas fa-undo-alt"></i>
                    <span>Clear order status</span>
                </div>
            </div>
        </div>

        <!-- Order Preview -->
        <div class="order-preview-card">
            <div class="preview-header">
                <i class="fas fa-eye"></i>
                <h4>Order Preview</h4>
            </div>

            <div class="preview-content">
                <div class="preview-item">
                    <span class="preview-label">Estimated Delivery:</span>
                    <span class="preview-value">30-45 minutes</span>
                </div>
                <div class="preview-item">
                    <span class="preview-label">Payment Method:</span>
                    <span class="preview-value payment-method-display">
                        <?= $paymentMethod === 'telebirr' ? 'TeleBirr' : 'Chapa' ?>
                    </span>
                </div>
                <div class="preview-item">
                    <span class="preview-label">Order Type:</span>
                    <span class="preview-value">Delivery</span>
                </div>
            </div>
        </div>
    </aside>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay" style="display: none;">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <h3>Processing Your Order</h3>
        <p>Please wait while we secure your payment...</p>
    </div>
</div>

<script>
    // Form submission handling
    document.getElementById('checkoutForm').addEventListener('submit', function (e) {
        const submitBtn = document.getElementById('submitBtn');
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoading = submitBtn.querySelector('.btn-loading');
        const loadingOverlay = document.getElementById('loadingOverlay');

        // Show loading state
        submitBtn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline-flex';
        loadingOverlay.style.display = 'flex';

        // Add loading class to body for additional styling
        document.body.classList.add('loading');
    });

    // Payment method selection
    document.querySelectorAll('.payment-radio').forEach(radio => {
        radio.addEventListener('change', function () {
            // Update preview
            const methodDisplay = document.querySelector('.payment-method-display');
            methodDisplay.textContent = this.value === 'telebirr' ? 'TeleBirr' : 'Chapa';

            // Update active state
            document.querySelectorAll('.payment-method-card').forEach(card => {
                card.classList.remove('active');
            });
            this.closest('.payment-method-card').classList.add('active');
        });
    });

    // Form validation enhancement
    document.querySelectorAll('.form-input').forEach(input => {
        input.addEventListener('blur', function () {
            if (this.value.trim() !== '') {
                this.classList.add('has-value');
            } else {
                this.classList.remove('has-value');
            }
        });

        // Set initial state
        if (input.value.trim() !== '') {
            input.classList.add('has-value');
        }
    });
</script>

<?php require APP_ROOT . '/backend/includes/layout_bottom.php'; ?>