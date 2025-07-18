<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=vault_db', 'root', '');
    
    // Get pending deposits count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE type='deposit' AND status='pending'");
    $stmt->execute();
    $pending_count = (int)$stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'pending_count' => $pending_count
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?> 