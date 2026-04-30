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
            redirect('index.php');
        }
    }
}

// 1. Categories for shortcuts
$categories = $pdo->query("SELECT * FROM categories WHERE status = 1 ORDER BY name LIMIT 8")->fetchAll();

// 2. Trending Now (Most ordered)
if ($userId !== null) {
    $stmt = $pdo->prepare("
        SELECT f.*, c.name as category_name, COUNT(oi.food_id) as order_count, MAX(fav.favorite_id IS NOT NULL) as is_favorite
        FROM foods f
        JOIN categories c ON f.category_id = c.category_id
        LEFT JOIN order_items oi ON f.food_id = oi.food_id
        LEFT JOIN favorites fav ON fav.food_id = f.food_id AND fav.user_id = ?
        WHERE f.status = 1
        GROUP BY f.food_id
        ORDER BY order_count DESC, f.food_id DESC
        LIMIT 4
    ");
    $stmt->execute([$userId]);
    $trending = $stmt->fetchAll();
} else {
    $trending = $pdo->query("
        SELECT f.*, c.name as category_name, COUNT(oi.food_id) as order_count 
        FROM foods f
        JOIN categories c ON f.category_id = c.category_id
        LEFT JOIN order_items oi ON f.food_id = oi.food_id
        WHERE f.status = 1
        GROUP BY f.food_id
        ORDER BY order_count DESC, f.food_id DESC
        LIMIT 4
    ")->fetchAll();
}

// 3. Recommended (random selection for now)
if ($userId !== null) {
    $stmt = $pdo->prepare("
        SELECT f.*, c.name as category_name, (fav.favorite_id IS NOT NULL) as is_favorite
        FROM foods f
        JOIN categories c ON f.category_id = c.category_id
        LEFT JOIN favorites fav ON fav.food_id = f.food_id AND fav.user_id = ?
        WHERE f.status = 1
        ORDER BY RAND()
        LIMIT 6
    ");
    $stmt->execute([$userId]);
    $recommended = $stmt->fetchAll();
} else {
    $recommended = $pdo->query("
        SELECT f.*, c.name as category_name 
        FROM foods f
        JOIN categories c ON f.category_id = c.category_id
        WHERE f.status = 1
        ORDER BY RAND()
        LIMIT 6
    ")->fetchAll();
}

// 4. New Arrivals (Most recently added)
if ($userId !== null) {
    $stmt = $pdo->prepare("
        SELECT f.*, c.name as category_name, (fav.favorite_id IS NOT NULL) as is_favorite
        FROM foods f
        JOIN categories c ON f.category_id = c.category_id
        LEFT JOIN favorites fav ON fav.food_id = f.food_id AND fav.user_id = ?
        WHERE f.status = 1
        ORDER BY f.food_id DESC
        LIMIT 4
    ");
    $stmt->execute([$userId]);
    $new_arrivals = $stmt->fetchAll();
} else {
    $new_arrivals = $pdo->query("
        SELECT f.*, c.name as category_name 
        FROM foods f
        JOIN categories c ON f.category_id = c.category_id
        WHERE f.status = 1
        ORDER BY f.food_id DESC
        LIMIT 4
    ")->fetchAll();
}

$page_title = "Premium Food Delivery - " . APP_NAME;
require APP_ROOT . '/backend/includes/layout_top.php';
?>

<!-- Hero Section -->
<section class="hero">
  <div class="container hero__inner">
    <div class="hero__content">
      <h1 class="hero__title">
        Fresh Food <span>Delivered</span> to Your Door
      </h1>
      <p class="hero__subtitle">
        Discover amazing food from your favorite local cafes and restaurants, delivered fast and fresh to your doorstep.
      </p>
      
      <form action="<?= e(url('food.php')) ?>" method="GET" class="hero-search glass">
        <i class="fas fa-search"></i>
        <input type="text" name="q" placeholder="What are you craving today?">
        <button type="submit" class="btn btn-primary">Find Food</button>
      </form>

      <div class="hero-features">
        <div class="h-feature">
          <i class="fas fa-check-circle"></i> <span>Top Rated</span>
        </div>
        <div class="h-feature">
          <i class="fas fa-clock"></i> <span>Fast Delivery</span>
        </div>
        <div class="h-feature">
          <i class="fas fa-shield-halved"></i> <span>Secure Pay</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Premium Features Section -->
<section class="content-section">
  <div class="container">
    <div class="features-grid">
      <div class="feature-card glass">
        <div class="feature-icon-wrapper">
          <i class="fas fa-truck-fast"></i>
        </div>
        <div class="feature-content">
          <h3>Lightning Fast</h3>
          <p>Hot food delivered in under 30 mins</p>
        </div>
      </div>

      <div class="feature-card glass">
        <div class="feature-icon-wrapper">
          <i class="fas fa-leaf"></i>
        </div>
        <div class="feature-content">
          <h3>Fresh & Organic</h3>
          <p>Premium ingredients, no preservatives</p>
        </div>
      </div>

      <div class="feature-card glass">
        <div class="feature-icon-wrapper">
          <i class="fas fa-shield-heart"></i>
        </div>
        <div class="feature-content">
          <h3>Safe Payments</h3>
          <p>100% secure TeleBirr & Chapa</p>
        </div>
      </div>

      <div class="feature-card glass">
        <div class="feature-icon-wrapper">
          <i class="fas fa-headset"></i>
        </div>
        <div class="feature-content">
          <h3>24/7 Support</h3>
          <p>Dedicated team always ready to help</p>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="container main-content">

  <!-- Recommended For You Section -->
  <section class="content-section">
    <div class="section-header">
      <div class="section-header-content">
        <div class="section-icon">
          <i class="fas fa-sparkles"></i>
        </div>
        <div>
          <h2 class="section-title">Recommended For You</h2>
          <p class="section-subtitle">Curated based on your preferences</p>
        </div>
      </div>
      <div class="section-controls">
        <button class="slider-btn" id="rec-prev">
          <i class="fas fa-chevron-left"></i>
        </button>
        <button class="slider-btn" id="rec-next">
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </div>
    <div class="horizontal-slider" id="rec-slider">
      <?php if (empty($recommended)): ?>
        <?php for ($i = 0; $i < 4; $i++): ?>
          <div class="slider-item">
            <?php render_skeleton_card(); ?>
          </div>
        <?php endfor; ?>
      <?php else: ?>
        <?php foreach ($recommended as $food): ?>
          <div class="slider-item">
            <?php render_premium_card($food); ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- Trending Now Section -->
  <section class="content-section">
    <div class="section-header">
      <div class="section-header-content">
        <div class="section-icon">
          <i class="fas fa-fire"></i>
        </div>
        <div>
          <h2 class="section-title">Trending Now</h2>
          <p class="section-subtitle">Popular picks people are ordering right now</p>
        </div>
      </div>
    </div>

    <div class="food-grid">
      <?php foreach ($trending as $food): ?>
        <?php render_premium_card($food); ?>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- New Arrivals Section -->
  <section class="content-section">
    <div class="section-header">
      <div class="section-header-content">
        <div class="section-icon">
          <i class="fas fa-star"></i>
        </div>
        <div>
          <h2 class="section-title">New Arrivals</h2>
          <p class="section-subtitle">Fresh from our kitchen to yours</p>
        </div>
      </div>
    </div>
    <div class="food-grid">
      <?php foreach ($new_arrivals as $food): ?>
        <?php render_premium_card($food); ?>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Categories Grid -->
  <section class="content-section">
    <div class="section-header">
      <div class="section-header-content">
        <div class="section-icon">
          <i class="fas fa-th-large"></i>
        </div>
        <div>
          <h2 class="section-title">Top Categories</h2>
          <p class="section-subtitle">Explore our diverse menu categories</p>
        </div>
      </div>
    </div>
    <div class="categories-grid">
      <?php
      $cat_imgs = [
        'Burgers' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd',
        'Pizza' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591',
        'Desserts' => 'https://images.unsplash.com/photo-1551024506-0bccd828d307',
        'Drinks' => 'https://images.unsplash.com/photo-1544145945-f904253d0c7b',
        'Healthy' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c'
      ];
      foreach ($categories as $cat):
        $img = $cat_imgs[$cat['name']] ?? 'https://images.unsplash.com/photo-1498837167922-ddd27525d352';
      ?>
        <a href="<?= e(url('food.php?category_id=' . $cat['category_id'])) ?>" class="category-card">
          <div class="category-image">
            <img src="<?= e($img) ?>?auto=format&fit=crop&w=800&q=80" onerror="this.onerror=null;this.src='<?= e(url('assets/img/food-placeholder.svg')) ?>';" alt="<?= e($cat['name']) ?>" loading="lazy">
          </div>
          <div class="category-overlay"></div>
          <div class="category-content">
            <h3 class="category-name"><?= e($cat['name']) ?></h3>
            <span class="category-arrow">
              <i class="fas fa-arrow-right"></i>
            </span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Testimonials Section -->
  <section class="content-section">
    <div class="section-header text-center">
      <div class="section-header-content">
        <div class="section-icon">
          <i class="fas fa-heart"></i>
        </div>
        <div>
          <h2 class="section-title">Wall of Love</h2>
          <p class="section-subtitle">Read what our happy foodies have to say</p>
        </div>
      </div>
    </div>
    <div class="testimonials-grid">
      <div class="testimonial-card glass">
        <div class="testimonial-content">
          <div class="testimonial-quote">
            <i class="fas fa-quote-left"></i>
          </div>
          <p class="testimonial-text">"The food arrived steaming hot and earlier than expected. The burgers are definitely the best in town!"</p>
        </div>
        <div class="testimonial-author">
          <img src="https://i.pravatar.cc/150?u=1" alt="Alex Johnson" class="author-avatar">
          <div class="author-info">
            <h4 class="author-name">Alex Johnson</h4>
            <div class="author-rating">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="testimonial-card glass">
        <div class="testimonial-content">
          <div class="testimonial-quote">
            <i class="fas fa-quote-left"></i>
          </div>
          <p class="testimonial-text">"Premium service at its best. The healthy bowl category is my favorite for my daily office lunch."</p>
        </div>
        <div class="testimonial-author">
          <img src="https://i.pravatar.cc/150?u=2" alt="Sarah Williams" class="author-avatar">
          <div class="author-info">
            <h4 class="author-name">Sarah Williams</h4>
            <div class="author-rating">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="testimonial-card glass">
        <div class="testimonial-content">
          <div class="testimonial-quote">
            <i class="fas fa-quote-left"></i>
          </div>
          <p class="testimonial-text">"I love how I can track my order live. No more guessing when my pizza will arrive. Highly recommended!"</p>
        </div>
        <div class="testimonial-author">
          <img src="https://i.pravatar.cc/150?u=3" alt="Michael Chen" class="author-avatar">
          <div class="author-info">
            <h4 class="author-name">Michael Chen</h4>
            <div class="author-rating">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

</div>

<script>
    document.getElementById('rec-next').onclick = () => document.getElementById('rec-slider').scrollLeft += 352;
    document.getElementById('rec-prev').onclick = () => document.getElementById('rec-slider').scrollLeft -= 352;
</script>

<?php require APP_ROOT . '/backend/includes/layout_bottom.php'; ?>