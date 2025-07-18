<?php
// --- CORS headers for local development ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
// --- END CORS headers ---

// api/index.php

header('Content-Type: application/json');

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Simple router
if ($uri === '/api/register' && $method === 'POST') {
    require __DIR__ . '/controllers/register.php';
    exit;
}
if ($uri === '/api/login' && $method === 'POST') {
    require __DIR__ . '/controllers/login.php';
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Endpoint not found']); 