<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
  echo json_encode(['error' => 'Not authenticated.']);
  exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['error' => 'Invalid request.']);
  exit;
}
$pdo = new PDO('mysql:host=localhost;dbname=vault_db', 'root', '');
$user_id = $_SESSION['user_id'];
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$avatar = trim($_POST['avatar_url'] ?? '');
// Debug: Output received data
file_put_contents('profile_debug.log', print_r([
  'user_id' => $user_id,
  'first_name' => $first_name,
  'last_name' => $last_name,
  'email' => $email,
  'avatar_url' => $avatar,
  'avatar_file' => isset($_FILES['avatar_file']) ? $_FILES['avatar_file'] : null,
  'session' => $_SESSION
], true), FILE_APPEND);
// Handle avatar upload
if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
  $ext = strtolower(pathinfo($_FILES['avatar_file']['name'], PATHINFO_EXTENSION));
  if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
    $filename = 'avatar_' . uniqid() . '.' . $ext;
    $dest = dirname(__DIR__, 2) . '/public/avatars/' . $filename;
    $webDest = 'public/avatars/' . $filename;
    $tmp = $_FILES['avatar_file']['tmp_name'];
    $dirExists = is_dir(dirname($dest));
    $dirWritable = is_writable(dirname($dest));
    file_put_contents('profile_debug.log', "dest=$dest, dirExists=" . ($dirExists ? 'yes' : 'no') . ", dirWritable=" . ($dirWritable ? 'yes' : 'no') . "\n", FILE_APPEND);
    $moveResult = move_uploaded_file($tmp, $dest);
    // Debug: Log move_uploaded_file result and paths
    file_put_contents('profile_debug.log', "move_uploaded_file: tmp=$tmp, dest=$dest, result=" . ($moveResult ? 'success' : 'fail') . "\n", FILE_APPEND);
    if ($moveResult) {
      $avatar = $webDest;
    } else {
      echo json_encode(['error' => 'Failed to upload avatar.']);
      exit;
    }
  } else {
    echo json_encode(['error' => 'Invalid avatar file type.']);
    exit;
  }
}
if (!$first_name || !$last_name || !$email) {
  echo json_encode(['error' => 'All fields except avatar are required.']);
  exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  echo json_encode(['error' => 'Invalid email address.']);
  exit;
}
// Debug: Output about to update
file_put_contents('profile_debug.log', "About to update: " . print_r([$first_name, $last_name, $email, $avatar, $user_id], true), FILE_APPEND);
$stmt = $pdo->prepare('UPDATE users SET first_name=?, last_name=?, email=?, avatar=? WHERE id=?');
if ($stmt->execute([$first_name, $last_name, $email, $avatar, $user_id])) {
  echo json_encode(['success' => 'Profile updated successfully!', 'avatar' => $avatar]);
} else {
  if ($stmt->errorInfo()[1]) {
    echo json_encode(['error' => 'SQL Error: ' . $stmt->errorInfo()[2]]);
    exit;
  }
  echo json_encode(['error' => 'Failed to update profile.']);
} 