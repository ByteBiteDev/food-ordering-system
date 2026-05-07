<?php
require_once __DIR__ . '/../includes/init.php';
$pdo = db();

$placeholders = [
    'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=800&q=80'
];

$foods = $pdo->query("SELECT food_id, image FROM foods")->fetchAll();

foreach ($foods as $food) {
    $img = $food['image'];
    $isUrl = str_starts_with($img, 'http');
    $isFile = !empty($img) && file_exists(__DIR__ . '/../uploads/foods/' . $img);
    
    if (!$isUrl && !$isFile) {
        $newImg = $placeholders[array_rand($placeholders)];
        $pdo->prepare("UPDATE foods SET image = ? WHERE food_id = ?")->execute([$newImg, $food['food_id']]);
        echo "Updated food #{$food['food_id']} with placeholder.\n";
    }
}
echo "Done fixing images.\n";
