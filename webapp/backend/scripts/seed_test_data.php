<?php
require_once __DIR__ . '/../config/database.php';

try {
    // Ensure categories
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
    $stmt->execute(['Coffee']);
    $cat = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cat) {
        $ins = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $ins->execute(['Coffee']);
        $categoryId = $pdo->lastInsertId();
    } else {
        $categoryId = $cat['id'];
    }

    // Insert or update products we rely on in tests
    $products = [
        ['Cappuccino', 'Classic cappuccino with perfect foam', 35000, 'products/prod_cappuccino.jfif', 1, 10],
        ['Iced Special', 'Seasonal iced special', 40000, 'products/prod_iced_special.jfif', 0, 0],
        ['Filter Brew', 'Manual filter brew', 30000, 'products/prod_filter_brew.jfif', 0, 2]
    ];

    foreach ($products as $p) {
        $check = $pdo->prepare("SELECT id FROM products WHERE name = ? LIMIT 1");
        $check->execute([$p[0]]);
        $exists = $check->fetch(PDO::FETCH_ASSOC);

        if ($exists) {
            $upd = $pdo->prepare("UPDATE products SET description = ?, base_price = ?, image = ?, is_featured = ?, stock = ?, category_id = ?, is_active = 1 WHERE id = ?");
            $upd->execute([$p[1], $p[2], $p[3], $p[4], $p[5], $categoryId, $exists['id']]);
        } else {
            $ins = $pdo->prepare("INSERT INTO products (name, description, base_price, image, is_featured, stock, category_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $ins->execute([$p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $categoryId]);
        }
    }

    // Create a test user if not exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute(['test@example.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $passwordHash = password_hash('password123', PASSWORD_DEFAULT);
        $ins = $pdo->prepare("INSERT INTO users (name, email, password, is_active) VALUES (?, ?, ?, 1)");
        $ins->execute(['Test User', 'test@example.com', $passwordHash]);
        echo "Created test user test@example.com\n";
    } else {
        echo "Test user already exists\n";
    }

    echo "Seed complete\n";
} catch (Exception $e) {
    echo "Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
