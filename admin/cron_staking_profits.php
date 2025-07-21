<?php
require_once '../api/config.php';

// 1. Drop today's profit for all active stakes
$stmt = $pdo->query("
    SELECT us.id AS stake_id, us.user_id, us.amount, us.plan_id, us.started_at, p.daily_roi, p.roi_mode
    FROM user_stakes us
    JOIN plans p ON us.plan_id = p.id
    WHERE us.status = 'active'
");
$stakes = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($stakes as $stake) {
    // Check if a profit drop for today already exists
    $profitCheck = $pdo->prepare("SELECT COUNT(*) FROM user_stake_profits WHERE stake_id = ? AND DATE(created_at) = CURDATE()");
    $profitCheck->execute([$stake['stake_id']]);
    if ($profitCheck->fetchColumn() > 0) continue; // Already dropped today

    // Calculate profit
    if ($stake['roi_mode'] === 'percent') {
        $profit = $stake['amount'] * ($stake['daily_roi'] / 100);
    } else { // fixed
        $profit = $stake['daily_roi'];
    }

    // Insert profit drop
    $insert = $pdo->prepare("INSERT INTO user_stake_profits (user_id, stake_id, amount, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
    $insert->execute([$stake['user_id'], $stake['stake_id'], $profit]);
}

// 2. Update matured profits to 'withdrawable'
$update = $pdo->prepare("UPDATE user_stake_profits SET status = 'withdrawable' WHERE status = 'pending' AND created_at <= (NOW() - INTERVAL 48 HOUR)");
$update->execute();

echo "Staking profit drops processed and matured profits updated.\n"; 