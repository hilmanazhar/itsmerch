<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php';

// Get input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$user_id = $input['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'User ID required']);
    exit;
}

// Check if password change request
if (isset($input['old_password']) && isset($input['new_password'])) {
    // Verify old password first
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }
    
    if (!password_verify($input['old_password'], $user['password'])) {
        echo json_encode(['success' => false, 'error' => 'Password lama salah']);
        exit;
    }
    
    // Update password
    $new_password_hash = password_hash($input['new_password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $result = $stmt->execute([$new_password_hash, $user_id]);
    
    echo json_encode(['success' => $result]);
    exit;
}

// Update profile info
$name = $input['name'] ?? null;
$phone = $input['phone'] ?? null;

if (!$name) {
    echo json_encode(['success' => false, 'error' => 'Name is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
    $result = $stmt->execute([$name, $phone, $user_id]);
    
    if ($result) {
        // Fetch updated user data
        $stmt = $pdo->prepare("SELECT id, name, email, phone, role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update profile']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
