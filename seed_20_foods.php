<?php
require_once __DIR__ . '/backend/includes/init.php';

$pdo = db();

// Helper to get category ID by name, or create if it doesn't exist
function getCategoryId($pdo, $name) {
    $stmt = $pdo->prepare('SELECT category_id FROM categories WHERE name = ?');
    $stmt->execute([$name]);
    $cat = $stmt->fetch();
    if ($cat) {
        return (int)$cat['category_id'];
    }
    $stmt = $pdo->prepare('INSERT INTO categories (name, status) VALUES (?, 1)');
    $stmt->execute([$name]);
    return (int)$pdo->lastInsertId();
}

$foods = [
    // Burgers
    ['Burgers', 'Gourmet Truffle Burger', 750.00, 'Premium beef patty, truffle mayo, melted gruyere, and arugula.', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'],
    ['Burgers', 'Spicy Jalapeno Smash', 600.00, 'Double smash patty, pepper jack cheese, fresh jalapeños, and spicy aioli.', 'https://images.unsplash.com/photo-1594212691516-069eade9ca5d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'],
    ['Burgers', 'Classic BBQ Bacon', 650.00, 'Smoked bacon, crispy onion rings, cheddar, and sweet BBQ sauce.', 'https://images.unsplash.com/photo-1550547660-d9450f859349?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'],
    ['Burgers', 'Vegan Beyond Burger', 800.00, 'Plant-based patty, vegan cheese, lettuce, tomato, and vegan mayo.', 'https://images.unsplash.com/photo-1520072959219-c595dc870360?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'],
    ['Burgers', 'Chicken Katsu Burger', 600.00, 'Crispy panko-crusted chicken breast, Asian slaw, and katsu sauce.', 'https://images.unsplash.com/photo-1615719413546-198b25453f85?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'],

    // Pizzas
    ['Pizza', 'Authentic Neapolitan', 900.00, 'San Marzano tomatoes, fresh mozzarella di bufala, and basil.', 'https://images.unsplash.com/photo-1513104890138-7c749659a591?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'],
    ['Pizza', 'Spicy Pepperoni', 950.00, 'Double pepperoni, mozzarella, hot honey drizzle.', 'https://images.unsplash.com/photo-1628840042765-356cda07504e?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'],
    ['Pizza', 'Truffle Mushroom', 1100.00, 'White sauce, wild mushrooms, truffle oil, and parmesan.', 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'],
    ['Pizza', 'Quattro Formaggi', 1050.00, 'Mozzarella, gorgonzola, parmesan, and provolone.', 'https://images.unsplash.com/photo-1593560708920-61dd98c46a4e?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'],
    ['Pizza', 'Prosciutto e Rucola', 1200.00, 'Thin crust with prosciutto crudo, fresh arugula, and balsamic glaze.', 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'],

    // Salads & Healthy
    ['Healthy Bowls', 'Mediterranean Quinoa', 700.00, 'Quinoa, cherry tomatoes, cucumbers, kalamata olives, feta, lemon vinaigrette.', 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'],
    ['Healthy Bowls', 'Grilled Chicken Caesar', 750.00, 'Crisp romaine, grilled chicken breast, parmesan, croutons, creamy dressing.', 'https://images.unsplash.com/photo-1550304943-4f24f54ddde9?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'],
    ['Healthy Bowls', 'Salmon Poke Bowl', 1200.00, 'Fresh raw salmon, sushi rice, avocado, edamame, seaweed salad, ponzu.', 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'],
    ['Healthy Bowls', 'Roasted Veggie Bowl', 650.00, 'Roasted sweet potatoes, broccoli, chickpeas, tahini dressing.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'],
    ['Healthy Bowls', 'Avocado Toast Deluxe', 550.00, 'Sourdough toast, smashed avocado, poached egg, chili flakes.', 'https://images.unsplash.com/photo-1482049016688-2d3e1b311543?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'],

    // Desserts
    ['Desserts', 'Molten Chocolate Lava Cake', 450.00, 'Warm chocolate cake with a gooey center, served with vanilla ice cream.', 'https://images.unsplash.com/photo-1624353365286-3f8d62daad51?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'],
    ['Desserts', 'Classic Tiramisu', 500.00, 'Espresso-soaked ladyfingers layered with mascarpone cream and cocoa.', 'https://images.unsplash.com/photo-1571115177098-24ec42ed204d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'],
    ['Desserts', 'New York Cheesecake', 550.00, 'Creamy vanilla cheesecake with a graham cracker crust and berry compote.', 'https://images.unsplash.com/photo-1533134242443-d4fd215305ad?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'],

    // Drinks
    ['Drinks', 'Artisan Iced Coffee', 300.00, 'Cold-brewed coffee with a splash of oat milk and vanilla syrup.', 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'],
    ['Drinks', 'Fresh Strawberry Lemonade', 250.00, 'Hand-squeezed lemonade blended with fresh strawberries and mint.', 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80']
];

$stmt = $pdo->prepare('INSERT INTO foods (category_id, name, price, description, image, status) VALUES (?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE price=VALUES(price), description=VALUES(description), image=VALUES(image)');

$count = 0;
foreach ($foods as $food) {
    $catId = getCategoryId($pdo, $food[0]);
    $stmt->execute([$catId, $food[1], $food[2], $food[3], $food[4]]);
    $count++;
}

echo "Successfully seeded $count foods with images.\n";
