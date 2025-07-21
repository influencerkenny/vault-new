<?php
require_once '../api/config.php';

try {
    echo "<h3>Inserting Sample Balance Data</h3>";
    
    // Get all users
    $stmt = $pdo->query("SELECT id, username FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) == 0) {
        echo "❌ No users found in database. Please create some users first.<br>";
        exit;
    }
    
    echo "Found " . count($users) . " users. Adding sample balances...<br><br>";
    
    $sampleBalances = [1000.00, 2500.50, 750.25, 3000.75, 500.00, 1500.25, 800.50, 2200.00];
    $balanceIndex = 0;
    
    foreach ($users as $user) {
        // Get a sample balance (cycle through the array)
        $sampleBalance = $sampleBalances[$balanceIndex % count($sampleBalances)];
        $balanceIndex++;
        
        // Check if balance record exists
        $stmt = $pdo->prepare("SELECT available_balance FROM user_balances WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $existingBalance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingBalance) {
            // Update existing balance
            $stmt = $pdo->prepare("UPDATE user_balances SET available_balance = ? WHERE user_id = ?");
            $result = $stmt->execute([$sampleBalance, $user['id']]);
            if ($result) {
                echo "✅ Updated balance for " . $user['username'] . ": SOL " . number_format($sampleBalance, 2) . "<br>";
            } else {
                echo "❌ Failed to update balance for " . $user['username'] . "<br>";
            }
        } else {
            // Insert new balance record
            $stmt = $pdo->prepare("INSERT INTO user_balances (user_id, available_balance) VALUES (?, ?)");
            $result = $stmt->execute([$user['id'], $sampleBalance]);
            if ($result) {
                echo "✅ Created balance for " . $user['username'] . ": SOL " . number_format($sampleBalance, 2) . "<br>";
            } else {
                echo "❌ Failed to create balance for " . $user['username'] . "<br>";
            }
        }
    }
    
    // Verify the data
    echo "<br><strong>Verification:</strong><br>";
    $stmt = $pdo->query("SELECT ub.user_id, u.username, ub.available_balance 
                         FROM user_balances ub 
                         JOIN users u ON ub.user_id = u.id 
                         ORDER BY ub.available_balance DESC");
    $allBalances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($allBalances) > 0) {
        echo "All user balances:<br>";
        foreach ($allBalances as $balance) {
            echo "- " . $balance['username'] . ": SOL " . number_format($balance['available_balance'], 2) . "<br>";
        }
    }
    
    echo "<br><strong>Sample data insertion completed!</strong><br>";
    echo "You can now test the admin users page to see the balances in the modal.<br>";
    
} catch (PDOException $e) {
    echo "<br><strong>Database error:</strong> " . $e->getMessage() . "<br>";
    echo "Error code: " . $e->getCode() . "<br>";
}
?> 