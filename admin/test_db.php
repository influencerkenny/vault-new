<?php
require_once '../api/config.php';

try {
    // Test 1: Check if user_balances table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_balances'");
    $tableExists = $stmt->rowCount() > 0;
    echo "User balances table exists: " . ($tableExists ? "YES" : "NO") . "<br>";
    
    if ($tableExists) {
        // Test 2: Check table structure
        $stmt = $pdo->query("DESCRIBE user_balances");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "User balances table structure:<br>";
        foreach ($columns as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
        }
        
        // Test 3: Check if there's any data
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM user_balances");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "Number of records in user_balances: " . $count . "<br>";
        
        // Test 4: Test the full query
        $sql = "SELECT u.id, u.username, u.email, u.status, u.created_at, 
                COALESCE(b.available_balance,0) AS available_balance,
                (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = u.id AND type = 'deposit' AND status = 'completed') AS total_deposits,
                (SELECT COALESCE(SUM(amount), 0) FROM user_rewards WHERE user_id = u.id) AS total_interest,
                (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = u.id AND type = 'withdrawal' AND status = 'completed') AS total_withdrawals,
                (SELECT COUNT(*) FROM transactions WHERE user_id = u.id) AS transaction_count
                FROM users u 
                LEFT JOIN user_balances b ON u.id = b.user_id 
                ORDER BY u.created_at DESC LIMIT 5";
        
        $stmt = $pdo->query($sql);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Query executed successfully. Found " . count($users) . " users.<br>";
        
        if (count($users) > 0) {
            echo "Sample user data:<br>";
            foreach ($users as $user) {
                echo "- " . $user['username'] . ": Balance = " . $user['available_balance'] . "<br>";
            }
        }
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}
?> 