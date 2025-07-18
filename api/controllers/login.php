<?php
// api/controllers/login.php
require_once __DIR__ . '/../config.php';

$data = json_decode(file_get_contents('php://input'), true);

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Email and password are required.']);
    exit;
}

// Find user
$stmt = $pdo->prepare("SELECT id, username, email, password_hash FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid email or password.']);
    exit;
}

// Generate a simple token (for demo; use JWT for production)
$token = base64_encode(random_bytes(32));

// You should store the token in a sessions table or similar for real apps

echo json_encode([
    'token' => $token,
    'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email']
    ]
]); 