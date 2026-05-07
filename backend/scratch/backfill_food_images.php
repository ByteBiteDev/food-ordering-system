<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

$pdo = db();

// Update foods with missing/blank images by assigning a curated Unsplash URL.
// This is safe to run multiple times (it only updates rows with empty images).

$images = [
    // Healthy / bowls / salads
    'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=1200&q=80',
    // Pizza
    'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?auto=format&fit=crop&w=1200&q=80',
    // Burgers
    'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&w=1200&q=80',
    // Desserts / drinks
    'https://images.unsplash.com/photo-1624353365286-3f8d62daad51?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=1200&q=80',
];

$rows = $pdo->query("
    SELECT food_id
    FROM foods
    WHERE status = 1 AND (image IS NULL OR image = '')
    ORDER BY food_id ASC
")->fetchAll();

if (!$rows) {
    echo "No foods with empty images found.\n";
    exit(0);
}

$stmt = $pdo->prepare('UPDATE foods SET image = ? WHERE food_id = ? AND (image IS NULL OR image = \'\')');

$updated = 0;
foreach ($rows as $index => $row) {
    $foodId = (int)$row['food_id'];
    $imageUrl = $images[$index % count($images)];
    $stmt->execute([$imageUrl, $foodId]);
    $updated += $stmt->rowCount();
}

echo "Backfilled images for {$updated} foods.\n";

