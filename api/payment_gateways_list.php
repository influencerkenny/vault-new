<?php
require_once 'config.php';
header('Content-Type: application/json');
$stmt = $pdo->query('SELECT * FROM payment_gateways WHERE status = "enabled" ORDER BY created_at DESC');
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC)); 