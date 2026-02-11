<?php
/**
 * Create Test Users for DailyCup
 * Run this file once to create admin and customer test accounts
 */

require_once __DIR__ . '/config/database.php';

try {
    // Check if users already exist
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email IN (?, ?)");
    $stmt->execute(['admin@dailycup.com', 'customer@dailycup.com']);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        echo "Test users already exist!\n";
        echo "Updating passwords...\n";
        
        // Update admin password
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$adminPassword, 'admin@dailycup.com']);
        
        // Update customer password
        $customerPassword = password_hash('customer123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$customerPassword, 'customer@dailycup.com']);
        
        echo "✅ Passwords updated!\n";
        echo "   Admin: admin@dailycup.com / admin123\n";
        echo "   Customer: customer@dailycup.com / customer123\n";
    } else {
        echo "Creating test users...\n";
        
        // Create admin user
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, phone, role, is_active, loyalty_points, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            'Admin User',
            'admin@dailycup.com',
            $adminPassword,
            '081234567890',
            'admin',
            1,
            0
        ]);
        echo "✅ Admin user created\n";

        // Create customer user
        $customerPassword = password_hash('customer123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, phone, role, is_active, loyalty_points, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            'Customer Test',
            'customer@dailycup.com',
            $customerPassword,
            '081234567891',
            'customer',
            1,
            100
        ]);
        echo "✅ Customer user created\n";
    }

    // Ensure specific test user for debugging exists (always run)
    $ridhoEmail = 'ridhohuman11@gmail.com';
    $ridhoPasswordPlain = 'Aria$1234.';
    $ridhoHashed = password_hash($ridhoPasswordPlain, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$ridhoEmail]);
    $ridhoExists = $stmt->fetch();

    if ($ridhoExists) {
        $stmt = $pdo->prepare("UPDATE users SET password = ?, is_active = 1 WHERE email = ?");
        $stmt->execute([$ridhoHashed, $ridhoEmail]);
        echo "✅ Updated existing test user: {$ridhoEmail}\n";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, role, is_active, loyalty_points, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute(['Ridho Test', $ridhoEmail, $ridhoHashed, '081234567899', 'customer', 1, 50]);
        echo "✅ Created test user: {$ridhoEmail}\n";
    }

    echo "\n📋 Test Accounts:\n";
    echo "   Admin: admin@dailycup.com / admin123\n";
    echo "   Customer: customer@dailycup.com / customer123\n";
    echo "   Ridho: ridhohuman11@gmail.com / Aria$1234.\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
}
