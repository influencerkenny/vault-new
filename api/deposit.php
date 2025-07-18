<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
  exit;
}
$user_id = $_SESSION['user_id'];
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
$wallet = trim($_POST['wallet'] ?? '');
$address = trim($_POST['address'] ?? '');
$notes = trim($_POST['notes'] ?? '');
if ($amount <= 0) {
  echo json_encode(['success' => false, 'error' => 'Invalid amount.']);
  exit;
}
if (!$wallet || !$address) {
  echo json_encode(['success' => false, 'error' => 'Wallet and address required.']);
  exit;
}
try {
  $pdo = new PDO('mysql:host=localhost;dbname=vault', 'root', '');
  $stmt = $pdo->prepare('INSERT INTO transactions (user_id, type, amount, status, description, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
  $desc = "Deposit via $wallet to $address" . ($notes ? (". Notes: $notes") : '');
  $stmt->execute([$user_id, 'deposit', $amount, 'pending', $desc]);
  echo json_encode(['success' => true]);
} catch (Exception $e) {
  echo json_encode(['success' => false, 'error' => 'Server error.']);
} 