<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/backend/includes/init.php';

if (is_post()) {
    verify_csrf();
    flash_set('success', 'Thanks! Your message was sent. We’ll reply soon.');
    redirect('contact.php');
}

require APP_ROOT . '/backend/includes/layout_top.php';
?>

<section class="page-hero" style="--hero-image: url('https://images.unsplash.com/photo-1529692236671-f1f6cf9683ba?auto=format&fit=crop&w=1600&q=80');">
  <div class="container page-hero__inner">
    <h1 class="page-hero__title">Contact</h1>
    <p class="page-hero__subtitle">Questions, feedback, or support—send a message and we’ll get back to you.</p>
  </div>
</section>

<section class="content-section">
  <div class="container">
    <div class="contact-grid">
      <div class="card">
        <div class="section-header contact-section-header">
          <div class="section-header-content">
            <div class="section-icon"><i class="fas fa-paper-plane"></i></div>
            <div>
              <h2 class="section-title">Send a message</h2>
              <p class="section-subtitle">We usually reply within 24 hours.</p>
            </div>
          </div>
        </div>

        <form method="post" class="contact-form">
          <?= csrf_field() ?>
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label" for="contact_name">Name <span class="required">*</span></label>
              <div class="input-wrapper">
                <i class="fas fa-user input-icon"></i>
                <input id="contact_name" class="form-input" type="text" name="name" required placeholder="Your name" autocomplete="name">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label" for="contact_email">Email <span class="required">*</span></label>
              <div class="input-wrapper">
                <i class="fas fa-envelope input-icon"></i>
                <input id="contact_email" class="form-input" type="email" name="email" required placeholder="you@example.com" autocomplete="email">
              </div>
            </div>
          </div>

          <div class="form-group contact-message">
            <label class="form-label" for="contact_message">Message <span class="required">*</span></label>
            <div class="input-wrapper">
              <i class="fas fa-message input-icon"></i>
              <textarea id="contact_message" class="form-input contact-textarea" name="message" rows="5" required placeholder="How can we help?"></textarea>
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-large contact-submit">
            <i class="fas fa-paper-plane"></i> Send
          </button>
        </form>
      </div>

      <div class="card contact-card--dark">
        <h2 class="section-title contact-info-title">Contact information</h2>
        <p class="muted contact-info-subtitle">Prefer a direct line? Reach us here.</p>

        <div class="contact-info-list">
          <div class="contact-info-item">
            <div class="contact-info-label"><i class="fas fa-location-dot"></i> Location</div>
            <div class="muted">123 Food Street, Culinary District<br>Gourmet City</div>
          </div>
          <div class="contact-info-item">
            <div class="contact-info-label"><i class="fas fa-phone"></i> Phone</div>
            <div class="muted">+251 700 000 000</div>
          </div>
          <div class="contact-info-item">
            <div class="contact-info-label"><i class="fas fa-envelope"></i> Email</div>
            <div class="muted">support@<?= e(strtolower(str_replace(' ', '', APP_NAME))) ?>.com</div>
          </div>
          <div class="contact-info-item">
            <div class="contact-info-label"><i class="fas fa-clock"></i> Hours</div>
            <div class="muted">Mon–Sun: 10:00 AM – 11:00 PM</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require APP_ROOT . '/backend/includes/layout_bottom.php'; ?>
