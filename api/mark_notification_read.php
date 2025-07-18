<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$notification_id = isset($input['notification_id']) ? (int)$input['notification_id'] : 0;

if ($notification_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit;
}

try {
    // Mark notification as read
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    $success = $stmt->execute([$notification_id, $_SESSION['user_id']]);
    
    if ($success && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Notification not found or already read']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?> 