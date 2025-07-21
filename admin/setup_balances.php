<?php
require_once '../api/config.php';

try {
    echo "<h3>Database Setup for User Balances</h3>";
    
    // Check if user_balances table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_balances'");
    $tableExists = $stmt->rowCount() > 0;
    echo "User balances table exists: " . ($tableExists ? "YES" : "NO") . "<br>";
    
    if (!$tableExists) {
        // Create user_balances table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS user_balances (
            user_id INT PRIMARY KEY,
            available_balance DECIMAL(18,2) DEFAULT 0,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $pdo->exec($sql);
        echo "✅ Created user_balances table<br>";
    } else {
        echo "✅ User balances table already exists<br>";
    }
    
    // Get all users
    $stmt = $pdo->query("SELECT id, username FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($users) . " users in database<br>";
    
    $insertedCount = 0;
    $updatedCount = 0;
    
    foreach ($users as $user) {
        // Check if balance record exists for this user
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_balances WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        
        if (!$exists) {
            // Insert default balance record
            $stmt = $pdo->prepare("INSERT INTO user_balances (user_id, available_balance) VALUES (?, 0)");
            $result = $stmt->execute([$user['id']]);
            if ($result) {
                echo "✅ Created balance record for user: " . $user['username'] . " (ID: " . $user['id'] . ")<br>";
                $insertedCount++;
            } else {
                echo "❌ Failed to create balance record for user: " . $user['username'] . "<br>";
            }
        } else {
            echo "ℹ️ Balance record already exists for user: " . $user['username'] . "<br>";
            $updatedCount++;
        }
    }
    
    // Verify the data was inserted
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user_balances");
    $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<br><strong>Verification:</strong><br>";
    echo "Total records in user_balances: " . $totalRecords . "<br>";
    echo "New records inserted: " . $insertedCount . "<br>";
    echo "Existing records found: " . $updatedCount . "<br>";
    
    // Show sample data
    $stmt = $pdo->query("SELECT ub.user_id, u.username, ub.available_balance 
                         FROM user_balances ub 
                         JOIN users u ON ub.user_id = u.id 
                         LIMIT 5");
    $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($sampleData) > 0) {
        echo "<br><strong>Sample Data:</strong><br>";
        foreach ($sampleData as $data) {
            echo "- User: " . $data['username'] . " | Balance: SOL " . $data['available_balance'] . "<br>";
        }
    }
    
    echo "<br><strong>Setup completed successfully!</strong><br>";
    
} catch (PDOException $e) {
    echo "<br><strong>Database error:</strong> " . $e->getMessage() . "<br>";
    echo "Error code: " . $e->getCode() . "<br>";
}
?> 