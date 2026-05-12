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
    SELECT u.id, u.email, u.name, u.last_name, u.is_admin, u.family_id,
           f.name as family_name
    FROM users u
    JOIN user_sessions s ON u.id = s.user_id
    LEFT JOIN families f ON u.family_id = f.id
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
    "status"      => "success",
    "user_id"     => $user['id'],
    "email"       => $user['email'],
    "name"        => $user['name'],
    "last_name"   => $user['last_name'],
    "is_admin"    => (bool) $user['is_admin'],
    "family_id"   => $user['family_id'],
    "family_name" => $user['family_name'],
]);