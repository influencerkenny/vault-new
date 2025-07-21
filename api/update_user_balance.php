<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

function calculateUserBalance($pdo, $user_id) {
    // Total Deposits (sum of all completed deposits)
    $stmt = $pdo->prepare("SELECT SUM(amount) AS total_deposits FROM transactions WHERE user_id = ? AND type = 'deposit' AND status = 'completed'");
    $stmt->execute([$user_id]);
    $totalDeposits = 0.00;
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $totalDeposits = (float)$row['total_deposits'] ?: 0.00;
    }

    // Staked Amount (sum of all active stakes)
    $stmt = $pdo->prepare("SELECT SUM(amount) AS staked FROM user_stakes WHERE user_id = ? AND status = 'active'");
    $stmt->execute([$user_id]);
    $stakedAmount = 0.00;
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stakedAmount = (float)$row['staked'] ?: 0.00;
    }

    // Total Withdrawals (sum of all completed withdrawals)
    $stmt = $pdo->prepare("SELECT SUM(amount) AS total_withdrawals FROM transactions WHERE user_id = ? AND type = 'withdrawal' AND status = 'completed'");
    $stmt->execute([$user_id]);
    $totalWithdrawals = 0.00;
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $totalWithdrawals = (float)$row['total_withdrawals'] ?: 0.00;
    }

    // Total Rewards
    $stmt = $pdo->prepare('SELECT SUM(amount) AS rewards FROM user_rewards WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $totalRewards = 0.00;
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $totalRewards = (float)$row['rewards'] ?: 0.00;
    }

    // Calculate Available Balance: Total Deposits - Staked Amount - Total Withdrawals + Total Rewards
    $availableBalance = $totalDeposits - $stakedAmount - $totalWithdrawals + $totalRewards;

    // Ensure available balance doesn't go negative
    if ($availableBalance < 0) {
        $availableBalance = 0.00;
    }

    return [
        'total_deposits' => $totalDeposits,
        'staked_amount' => $stakedAmount,
        'total_withdrawals' => $totalWithdrawals,
        'total_rewards' => $totalRewards,
        'available_balance' => $availableBalance
    ];
}

function updateUserBalance($pdo, $user_id) {
    $balance = calculateUserBalance($pdo, $user_id);
    
    // Update or insert user balance record (only available_balance column exists)
    $stmt = $pdo->prepare('INSERT INTO user_balances (user_id, available_balance) 
                           VALUES (?, ?) 
                           ON DUPLICATE KEY UPDATE 
                           available_balance = VALUES(available_balance)');
    
    $stmt->execute([
        $user_id,
        $balance['available_balance']
    ]);
    
    return $balance;
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    try {
        switch ($action) {
            case 'update_balance':
                $balance = updateUserBalance($pdo, $_SESSION['user_id']);
                echo json_encode([
                    'success' => true,
                    'balance' => $balance
                ]);
                break;
                
            case 'get_balance':
                $balance = calculateUserBalance($pdo, $_SESSION['user_id']);
                echo json_encode([
                    'success' => true,
                    'balance' => $balance
                ]);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?> 