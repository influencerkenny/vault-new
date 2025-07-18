<?php
// delete-expired-reset-tokens.php
// Run as a cron job to clean up expired password reset tokens

$pdo = new PDO('mysql:host=localhost;dbname=vault', 'root', '');
$stmt = $pdo->prepare('DELETE FROM password_resets WHERE expires_at < NOW()');
$stmt->execute();
$deleted = $stmt->rowCount();
echo date('Y-m-d H:i:s') . ": Deleted $deleted expired password reset tokens.\n"; 