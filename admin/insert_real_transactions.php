<?php
require_once '../api/config.php';

try {
    echo "<h3>Inserting Real Transaction Data</h3>";
    
    // Get all users
    $stmt = $pdo->query("SELECT id, username FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) == 0) {
        echo "❌ No users found in database. Please create some users first.<br>";
        exit;
    }
    
    echo "Found " . count($users) . " users. Adding real transaction data...<br><br>";
    
    foreach ($users as $index => $user) {
        $userId = $user['id'];
        $username = $user['username'];
        
        echo "<strong>Processing user: " . $username . "</strong><br>";
        
        // 1. Add a deposit
        $depositAmount = rand(1000, 5000); // Random deposit between 1000-5000
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, status, description, created_at) VALUES (?, 'deposit', ?, 'completed', 'Initial deposit', NOW())");
        $stmt->execute([$userId, $depositAmount]);
        echo "✅ Added deposit: SOL " . number_format($depositAmount, 2) . "<br>";
        
        // 2. Add a stake (if deposit is large enough)
        if ($depositAmount > 1500) {
            $stakeAmount = rand(500, $depositAmount - 500);
            $stmt = $pdo->prepare("INSERT INTO user_stakes (user_id, plan_id, amount, status, started_at) VALUES (?, 1, ?, 'active', NOW())");
            $stmt->execute([$userId, $stakeAmount]);
            echo "✅ Added stake: SOL " . number_format($stakeAmount, 2) . "<br>";
        }
        
        // 3. Add some rewards
        $rewardAmount = rand(50, 200);
        $stmt = $pdo->prepare("INSERT INTO user_rewards (user_id, amount, type, description) VALUES (?, ?, 'interest', 'Interest earned')");
        $stmt->execute([$userId, $rewardAmount]);
        echo "✅ Added reward: SOL " . number_format($rewardAmount, 2) . "<br>";
        
        // 4. Add a withdrawal (if balance allows)
        if ($depositAmount > 2000) {
            $withdrawalAmount = rand(100, 500);
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, status, description, created_at) VALUES (?, 'withdrawal', ?, 'completed', 'Withdrawal request', NOW())");
            $stmt->execute([$userId, $withdrawalAmount]);
            echo "✅ Added withdrawal: SOL " . number_format($withdrawalAmount, 2) . "<br>";
        }
        
        echo "<br>";
    }
    
    // Show the calculated balances
    echo "<strong>Real Balance Calculations:</strong><br>";
    $stmt = $pdo->query("SELECT u.id, u.username,
        COALESCE((SELECT SUM(amount) FROM transactions WHERE user_id = u.id AND type = 'deposit' AND status = 'completed'), 0) as deposits,
        COALESCE((SELECT SUM(amount) FROM user_stakes WHERE user_id = u.id AND status = 'active'), 0) as stakes,
        COALESCE((SELECT SUM(amount) FROM transactions WHERE user_id = u.id AND type = 'withdrawal' AND status = 'completed'), 0) as withdrawals,
        COALESCE((SELECT SUM(amount) FROM user_rewards WHERE user_id = u.id), 0) as rewards,
        GREATEST(
            COALESCE((SELECT SUM(amount) FROM transactions WHERE user_id = u.id AND type = 'deposit' AND status = 'completed'), 0) -
            COALESCE((SELECT SUM(amount) FROM user_stakes WHERE user_id = u.id AND status = 'active'), 0) -
            COALESCE((SELECT SUM(amount) FROM transactions WHERE user_id = u.id AND type = 'withdrawal' AND status = 'completed'), 0) +
            COALESCE((SELECT SUM(amount) FROM user_rewards WHERE user_id = u.id), 0), 0
        ) as available_balance
        FROM users u");
    
    $balances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($balances as $balance) {
        echo "<br><strong>" . $balance['username'] . ":</strong><br>";
        echo "- Deposits: SOL " . number_format($balance['deposits'], 2) . "<br>";
        echo "- Stakes: SOL " . number_format($balance['stakes'], 2) . "<br>";
        echo "- Withdrawals: SOL " . number_format($balance['withdrawals'], 2) . "<br>";
        echo "- Rewards: SOL " . number_format($balance['rewards'], 2) . "<br>";
        echo "- <strong>Available Balance: SOL " . number_format($balance['available_balance'], 2) . "</strong><br>";
    }
    
    echo "<br><strong>Real transaction data insertion completed!</strong><br>";
    echo "You can now test the admin users page to see the real calculated balances in the modal.<br>";
    
} catch (PDOException $e) {
    echo "<br><strong>Database error:</strong> " . $e->getMessage() . "<br>";
    echo "Error code: " . $e->getCode() . "<br>";
}
?> 