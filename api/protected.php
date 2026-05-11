<?php
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../system/config.php';

$token = $_COOKIE['auth_token'] ?? '';

if (!$token) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT u.id, u.email, u.name
    FROM users u
    JOIN user_sessions s ON u.id = s.user_id
    WHERE s.token = :token
");
$stmt->execute([':token' => $token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

echo json_encode([
    "status"  => "success",
    "user_id" => $user['id'],
    "email"   => $user['email'],
    "name"    => $user['name'],
]);
