<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db.php';

try {
    // Get all categories
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order, name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get product count per category
    foreach ($categories as &$cat) {
        $countStmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
        $countStmt->execute([$cat['id']]);
        $result = $countStmt->fetch(PDO::FETCH_ASSOC);
        $cat['product_count'] = (int)$result['count'];
    }
    
    echo json_encode($categories);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
