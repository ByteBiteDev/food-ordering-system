<?php
declare(strict_types=1);
?>
    </main>

    <footer class="mega-footer">
        <div class="container">
            <div class="footer-grid">
                <!-- Brand Section -->
                <div>
                    <div class="brand footer-brand">
                        <i class="fas fa-burger"></i> <?= e(APP_NAME) ?>
                    </div>
                    <p class="footer-desc">
                        Bringing you the finest culinary experiences right to your doorstep. Quality, freshness, and speed are at the heart of everything we do.
                    </p>
                    <div class="social-links">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>

                <!-- Explore Section -->
                <div>
                    <h4 class="footer-title">Explore</h4>
                    <ul class="footer-links">
                        <li><a href="<?= e(url('index.php')) ?>">Home</a></li>
                        <li><a href="<?= e(url('food.php')) ?>">Menu</a></li>
                        <li><a href="<?= e(url('about.php')) ?>">About Us</a></li>
                        <li><a href="<?= e(url('contact.php')) ?>">Contact Us</a></li>
                    </ul>
                </div>

                <!-- Legal Section -->
                <div>
                    <h4 class="footer-title">Legal</h4>
                    <ul class="footer-links">
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Refund Policy</a></li>
                        <li><a href="#">Cookie Policy</a></li>
                    </ul>
                </div>

                <!-- Contact Section -->
                <div>
                    <h4 class="footer-title">Stay Connected</h4>
                    <p class="footer-desc">Subscribe to our newsletter for exclusive offers and news.</p>
                    <form class="footer-newsletter" action="#" method="post">
                        <input type="email" placeholder="Email address" autocomplete="email">
                        <button class="btn btn-primary" type="submit">Join</button>
                    </form>
                </div>
            </div>

            <div class="footer-bottom">
                <div>&copy; <?= (int)date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.</div>
                <div style="display: flex; gap: 1.5rem;">
                    <span><i class="fas fa-envelope"></i> hello@<?= strtolower(str_replace(' ', '', APP_NAME)) ?>.com</span>
                    <span><i class="fas fa-phone"></i> +1 (555) FOOD-FAST</span>
                </div>
            </div>
        </div>
    </footer>

    <?= csrf_field() ?>
    <script src="<?= e(url('assets/js/main.js')) ?>"></script>
</body>
</html>
