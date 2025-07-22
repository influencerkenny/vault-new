<?php
session_start();
require_once '../api/config.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$action_success = $action_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)$_POST['user_id'];
    $amount = (float)$_POST['amount'];
    $wallet_type = $_POST['wallet_type'];
    $notes = trim($_POST['notes'] ?? '');
    $action = $_POST['action'];
    
    // Validate inputs
    if ($user_id <= 0 || $amount <= 0) {
        $action_error = 'Invalid user ID or amount.';
    } elseif (!in_array($wallet_type, ['deposit', 'interest', 'withdrawable'])) {
        $action_error = 'Invalid wallet type.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Get user information
            $stmt = $pdo->prepare('SELECT username, email FROM users WHERE id = ?');
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                throw new Exception('User not found.');
            }
            
            // Create transaction record for admin action
            $transaction_type = $action === 'top_up' ? 'admin_credit' : 'admin_debit';
            $admin_description = $action === 'top_up' ? 
                "Admin Top Up - $wallet_type" : 
                "Admin Deduction - $wallet_type";
            
            if ($notes) {
                $admin_description .= " - $notes";
            }
            
            $stmt = $pdo->prepare('INSERT INTO transactions (user_id, type, amount, description, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
            $stmt->execute([$user_id, $transaction_type, $amount, $admin_description, 'completed']);
            
            // Update user balance based on wallet type
            if ($action === 'top_up') {
                if ($wallet_type === 'deposit') {
                    // Add to deposits by creating a deposit transaction
                    $stmt = $pdo->prepare('INSERT INTO transactions (user_id, type, amount, description, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
                    $stmt->execute([$user_id, 'deposit', $amount, "Admin Top Up - $wallet_type", 'completed']);
                } else if ($wallet_type === 'interest') {
                    // Add to rewards by creating a reward record
                    $stmt = $pdo->prepare('INSERT INTO user_rewards (user_id, amount, type, description, created_at) VALUES (?, ?, ?, ?, NOW())');
                    $stmt->execute([$user_id, $amount, 'admin_credit', "Admin Top Up - $wallet_type"]);
                } else if ($wallet_type === 'withdrawable') {
                    // Add to withdrawable balance
                    $stmt = $pdo->prepare('UPDATE user_balances SET withdrawable_balance = withdrawable_balance + ? WHERE user_id = ?');
                    $stmt->execute([$amount, $user_id]);
                }
                // Update available balance
                $stmt = $pdo->prepare('INSERT INTO user_balances (user_id, available_balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE available_balance = available_balance + ?');
                $stmt->execute([$user_id, $amount, $amount]);
            } else {
                // Deduct from available balance
                $stmt = $pdo->prepare('UPDATE user_balances SET available_balance = GREATEST(0, available_balance - ?) WHERE user_id = ?');
                $stmt->execute([$amount, $user_id]);
                if ($wallet_type === 'withdrawable') {
                    $stmt = $pdo->prepare('UPDATE user_balances SET withdrawable_balance = GREATEST(0, withdrawable_balance - ?) WHERE user_id = ?');
                    $stmt->execute([$amount, $user_id]);
                }
            }
            
            // Send notification to user
            $notification_title = $action === 'top_up' ? 'Balance Credited' : 'Balance Deducted';
            $notification_message = $action === 'top_up' ? 
                "Your account has been credited with $amount SOL by an administrator." :
                "Your account has been deducted $amount SOL by an administrator.";
            
            $stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
            $stmt->execute([$user_id, $notification_title, $notification_message]);
            
            $pdo->commit();
            
            $action_success = $action === 'top_up' ? 
                "Successfully topped up user balance by $amount SOL." :
                "Successfully deducted $amount SOL from user balance.";
                
        } catch (Exception $e) {
            $pdo->rollBack();
            $action_error = 'Error processing request: ' . $e->getMessage();
        }
    }
}

// Redirect back to users page with result
$redirect_url = 'users.php';
if ($action_success) {
    $redirect_url .= '?success=' . urlencode($action_success);
}
if ($action_error) {
    $redirect_url .= '?error=' . urlencode($action_error);
}

header('Location: ' . $redirect_url);
exit();
?> 