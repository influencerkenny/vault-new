<?php
// api/controllers/register.php
require_once __DIR__ . '/../config.php';

$data = json_decode(file_get_contents('php://input'), true);

$username = trim($data['username'] ?? '');
$first_name = trim($data['first_name'] ?? '');
$last_name = trim($data['last_name'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$phone = trim($data['phone'] ?? '');
$country = trim($data['country'] ?? '');
$referred_by = $data['referred_by'] ?? null;

if (!$username || !$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Username, email, and password are required.']);
    exit;
}

// Check if user exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
$stmt->execute([$email, $username]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'Email or username already exists.']);
    exit;
}

// Hash password
$hash = password_hash($password, PASSWORD_BCRYPT);

// Insert user
$stmt = $pdo->prepare("INSERT INTO users (username, first_name, last_name, email, password_hash, phone, country, referred_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$username, $first_name, $last_name, $email, $hash, $phone, $country, $referred_by]);

echo json_encode(['success' => true]); 