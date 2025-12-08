<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET - Get notifications for a user
if ($method === 'GET') {
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $unread_only = isset($_GET['unread']) && $_GET['unread'] === 'true';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'error' => 'User ID required']);
        exit;
    }
    
    try {
        $sql = "SELECT id, type, title, message, link, is_read, created_at 
                FROM notifications 
                WHERE user_id = ?";
        if ($unread_only) {
            $sql .= " AND is_read = 0";
        }
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $limit]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get unread count
        $countStmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $countStmt->execute([$user_id]);
        $unreadCount = $countStmt->fetch()['count'];
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => (int)$unreadCount
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

// POST - Create a new notification (used by system/admin)
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    $user_id = $input['user_id'] ?? 0;
    $type = $input['type'] ?? 'system';
    $title = trim($input['title'] ?? '');
    $message = trim($input['message'] ?? '');
    $link = $input['link'] ?? null;
    
    if (!$user_id || !$title || !$message) {
        echo json_encode(['success' => false, 'error' => 'User ID, title, and message required']);
        exit;
    }
    
    $validTypes = ['order_status', 'payment', 'promo', 'review', 'system'];
    if (!in_array($type, $validTypes)) {
        $type = 'system';
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$user_id, $type, $title, $message, $link]);
        
        if ($result) {
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to create notification']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// PUT - Mark notification(s) as read
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $user_id = $input['user_id'] ?? 0;
    $notification_id = $input['id'] ?? null;
    $mark_all = isset($input['mark_all']) && $input['mark_all'] === true;
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'error' => 'User ID required']);
        exit;
    }
    
    try {
        if ($mark_all) {
            // Mark all as read
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$user_id]);
        } elseif ($notification_id) {
            // Mark single notification as read
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$notification_id, $user_id]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Notification ID or mark_all required']);
            exit;
        }
        
        echo json_encode(['success' => true, 'message' => 'Notification(s) marked as read']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

// DELETE - Delete a notification
if ($method === 'DELETE') {
    parse_str(file_get_contents('php://input'), $input);
    if (empty($input)) {
        $input = $_GET;
    }
    
    $notification_id = $input['id'] ?? 0;
    $user_id = $input['user_id'] ?? 0;
    
    if (!$notification_id || !$user_id) {
        echo json_encode(['success' => false, 'error' => 'Notification ID and User ID required']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$notification_id, $user_id]);
        
        echo json_encode(['success' => true, 'message' => 'Notification deleted']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid method']);
