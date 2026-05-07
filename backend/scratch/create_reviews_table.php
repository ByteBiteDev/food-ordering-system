<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

// One-time helper to enable the reviews feature without re-running full setup.
// Visit this file in the browser once (then delete it if desired).

$pdo = db();

$pdo->exec("
CREATE TABLE IF NOT EXISTS food_reviews (
  review_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  food_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  comment TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  PRIMARY KEY (review_id),
  UNIQUE KEY uq_food_reviews_food_user (food_id, user_id),
  KEY idx_food_reviews_food (food_id),
  KEY idx_food_reviews_user (user_id),
  CONSTRAINT fk_food_reviews_food
    FOREIGN KEY (food_id) REFERENCES foods(food_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_food_reviews_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

require __DIR__ . '/../includes/layout_top.php';
?>

<div class="card" style="margin-top:14px;">
  <h1 style="margin:6px 0 10px;">Reviews enabled</h1>
  <div class="muted">The <code>food_reviews</code> table is now available.</div>
  <div class="actions" style="margin-top:14px;">
    <a class="btn btn-primary" href="<?= e(url('food.php')) ?>">Go to Menu</a>
  </div>
</div>

<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>

