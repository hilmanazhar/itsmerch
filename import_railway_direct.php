<?php
// Import schema directly to 'railway' database
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'switchback.proxy.rlwy.net';
$port = 53877;
$user = 'root';
$pass = 'HqvDdwHCaGzKoRAEEeFpMolvqkEPwrJJ';
$db   = 'railway';

echo "Connecting to Railway MySQL (database: $db)...\n";

try {
    $mysqli = new mysqli($host, $user, $pass, $db, $port);
    $mysqli->set_charset('utf8mb4');
    echo "✅ Connected!\n\n";
} catch (Exception $e) {
    die("❌ Connection failed: " . $e->getMessage() . "\n");
}

// Create tables directly (not from file to avoid CREATE DATABASE statements)
$sql = <<<SQL

-- Categories
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT 'bi-tag',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(12,2) NOT NULL,
    stock INT DEFAULT 0,
    image_url VARCHAR(500),
    category_id INT,
    weight INT DEFAULT 500,
    is_featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User Addresses
CREATE TABLE IF NOT EXISTS user_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    label VARCHAR(50) DEFAULT 'Rumah',
    recipient_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    province_id VARCHAR(10),
    province_name VARCHAR(100),
    city_id VARCHAR(10),
    city_name VARCHAR(100),
    district_id VARCHAR(10),
    district_name VARCHAR(100),
    postal_code VARCHAR(10),
    full_address TEXT NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Orders
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'completed', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(12,2) NOT NULL,
    shipping_cost DECIMAL(12,2) DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    coupon_code VARCHAR(50),
    shipping_address TEXT,
    shipping_courier VARCHAR(50),
    tracking_number VARCHAR(100),
    payment_status ENUM('unpaid', 'paid', 'failed', 'refunded') DEFAULT 'unpaid',
    snap_token VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order Details
CREATE TABLE IF NOT EXISTS order_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    variant_info TEXT,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Carts
CREATE TABLE IF NOT EXISTS carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    variant_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Coupons
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(12,2) NOT NULL,
    min_purchase DECIMAL(12,2) DEFAULT 0,
    max_discount DECIMAL(12,2),
    usage_limit INT,
    times_used INT DEFAULT 0,
    valid_from DATE,
    valid_until DATE,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Coupon Usages
CREATE TABLE IF NOT EXISTS coupon_usages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Product Variants
CREATE TABLE IF NOT EXISTS product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    size VARCHAR(20),
    color VARCHAR(50),
    stock INT DEFAULT 0,
    price_adjustment DECIMAL(12,2) DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Variant Options
CREATE TABLE IF NOT EXISTS variant_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('size', 'color') NOT NULL,
    value VARCHAR(50) NOT NULL,
    display_name VARCHAR(100)
);

-- Reviews
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Wishlist
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, product_id)
);

-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    type VARCHAR(50) DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    link VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

SQL;

echo "Creating tables...\n";
$mysqli->multi_query($sql);
do {
    if ($result = $mysqli->store_result()) {
        $result->free();
    }
} while ($mysqli->next_result());

echo "✅ Tables created!\n\n";

// Insert sample data
echo "Inserting sample data...\n";

// Categories
$mysqli->query("INSERT IGNORE INTO categories (id, name, slug, icon) VALUES 
    (1, 'Pakaian', 'pakaian', 'bi-tshirt'),
    (2, 'Aksesoris', 'aksesoris', 'bi-watch'),
    (3, 'Alat Tulis', 'alat-tulis', 'bi-pencil'),
    (4, 'Tas', 'tas', 'bi-bag')
");

// Admin user (password: admin123)
$mysqli->query("INSERT IGNORE INTO users (id, name, email, password, role) VALUES 
    (1, 'Admin ITS', 'admin@itsmerch.id', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
");

// Sample products
$mysqli->query("INSERT IGNORE INTO products (id, name, description, price, stock, image_url, category_id, weight) VALUES 
    (1, 'Kaos ITS Logo Classic', 'Kaos cotton combed 30s dengan logo ITS di dada', 85000, 50, 'https://via.placeholder.com/400x400?text=Kaos+ITS', 1, 200),
    (2, 'Hoodie ITS Navy', 'Hoodie fleece tebal warna navy dengan bordir ITS', 175000, 30, 'https://via.placeholder.com/400x400?text=Hoodie+ITS', 1, 500),
    (3, 'Topi ITS Baseball', 'Topi baseball dengan logo ITS bordir', 65000, 40, 'https://via.placeholder.com/400x400?text=Topi+ITS', 2, 150),
    (4, 'Tumbler ITS 500ml', 'Tumbler stainless steel dengan logo ITS', 95000, 25, 'https://via.placeholder.com/400x400?text=Tumbler+ITS', 2, 300),
    (5, 'Notebook ITS A5', 'Notebook hardcover A5 80 lembar', 35000, 100, 'https://via.placeholder.com/400x400?text=Notebook+ITS', 3, 200),
    (6, 'Tas Ransel ITS', 'Tas ransel laptop 15 inch dengan logo ITS', 250000, 20, 'https://via.placeholder.com/400x400?text=Tas+ITS', 4, 800)
");

// Sample coupon
$mysqli->query("INSERT IGNORE INTO coupons (code, discount_type, discount_value, min_purchase, usage_limit, valid_from, valid_until) VALUES 
    ('WELCOME10', 'percentage', 10, 100000, 100, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY))
");

echo "✅ Sample data inserted!\n\n";

// Verify
echo "📋 Tables in database:\n";
$result = $mysqli->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    echo "   - " . $row[0] . "\n";
}

$mysqli->close();
echo "\n🎉 Done!\n";
?>
