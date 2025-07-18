<?php
header('Content-Type: application/json');
require_once '../config.php';

try {
    $stmt = $pdo->query("SELECT * FROM plans WHERE status = 'active' ORDER BY created_at DESC");
    $plans = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $plans[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'dailyRewards' => $row['daily_roi'] . '%',
            'minStake' => $row['min_investment'],
            'lockPeriod' => $row['lock_in_duration'] . ' days',
            'monthlyROI' => $row['monthly_roi'] . '%',
            'maxStake' => $row['max_investment'],
            'bonus' => $row['bonus'],
            'referralReward' => $row['referral_reward'],
            'features' => [
                'Monthly ROI: ' . $row['monthly_roi'] . '%',
                'Bonus: ' . $row['bonus'],
                'Referral Reward: ' . $row['referral_reward'],
                'Lock-in: ' . $row['lock_in_duration'] . ' days',
            ],
            'premium' => false,
            'popular' => false,
            'icon' => 'Star',
        ];
    }
    echo json_encode($plans);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch plans.']);
} 