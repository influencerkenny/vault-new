<?php
session_start();
require_once '../api/config.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
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
    
    // Update or insert user balance record
    $stmt = $pdo->prepare('INSERT INTO user_balances (user_id, available_balance, total_deposits, staked_amount, total_withdrawals, total_rewards, updated_at) 
                           VALUES (?, ?, ?, ?, ?, ?, NOW()) 
                           ON DUPLICATE KEY UPDATE 
                           available_balance = VALUES(available_balance),
                           total_deposits = VALUES(total_deposits),
                           staked_amount = VALUES(staked_amount),
                           total_withdrawals = VALUES(total_withdrawals),
                           total_rewards = VALUES(total_rewards),
                           updated_at = NOW()');
    
    $stmt->execute([
        $user_id,
        $balance['available_balance'],
        $balance['total_deposits'],
        $balance['staked_amount'],
        $balance['total_withdrawals'],
        $balance['total_rewards']
    ]);
    
    return $balance;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $transaction_id = isset($input['transaction_id']) ? (int)$input['transaction_id'] : 0;
    
    if ($transaction_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid transaction ID']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get transaction details before deletion
        $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = ?');
        $stmt->execute([$transaction_id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transaction) {
            throw new Exception('Transaction not found');
        }
        
        $user_id = $transaction['user_id'];
        $transaction_type = $transaction['type'];
        $transaction_amount = $transaction['amount'];
        $transaction_status = $transaction['status'];
        
        // Delete the transaction
        $stmt = $pdo->prepare('DELETE FROM transactions WHERE id = ?');
        $stmt->execute([$transaction_id]);
        
        // Update user balance
        $balance = updateUserBalance($pdo, $user_id);
        
        // Send notification to user about transaction deletion
        $stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
        $stmt->execute([
            $user_id,
            'Transaction Deleted',
            'Your ' . $transaction_type . ' transaction of ' . number_format($transaction_amount, 2) . ' has been deleted by an administrator.'
        ]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Transaction deleted successfully',
            'balance' => $balance
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error deleting transaction: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?> 