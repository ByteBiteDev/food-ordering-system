<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/backend/includes/init.php';
require APP_ROOT . '/backend/includes/layout_top.php';
?>

<section class="page-hero" style="--hero-image: url('https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=1600&q=80');">
  <div class="container page-hero__inner">
    <h1 class="page-hero__title">About <?= e(APP_NAME) ?></h1>
    <p class="page-hero__subtitle">Simple ordering. Fresh food. A clean, modern experience.</p>
  </div>
</section>

<section class="content-section">
  <div class="container">
    <div class="about-split">
      <div class="card about-card">
        <h2 class="section-title">Our Story</h2>
        <p class="muted">
          <?= e(APP_NAME) ?> is built for people who want great food without the noise. Browse a clean menu, order in seconds,
          and enjoy a smooth checkout experience.
        </p>

        <div class="about-highlights">
          <div class="about-highlight"><i class="fas fa-bolt"></i> Fast checkout</div>
          <div class="about-highlight"><i class="fas fa-shield-halved"></i> Secure payment</div>
          <div class="about-highlight"><i class="fas fa-leaf"></i> Fresh options</div>
        </div>
      </div>

      <div class="card about-media">
        <img
          src="https://images.unsplash.com/photo-1600565193348-f74bd3c7ccdf?auto=format&fit=crop&w=1200&q=80"
          onerror="this.onerror=null;this.src='<?= e(url('assets/img/food-placeholder.svg')) ?>';"
          alt="Chefs preparing food"
          loading="lazy"
        >
      </div>
    </div>
  </div>
</section>

<section class="content-section">
  <div class="container">
    <div class="card contact-card--dark about-cta">
      <h2>Ready to order?</h2>
      <p class="muted">Explore the menu and find something you’ll love.</p>
      <a href="<?= e(url('food.php')) ?>" class="btn btn-primary btn-large"><i class="fas fa-utensils"></i> View Menu</a>
    </div>
  </div>
</section>

<?php require APP_ROOT . '/backend/includes/layout_bottom.php'; ?>
