<?php
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../system/config.php';

// Auth-Check
$token = $_COOKIE['auth_token'] ?? '';
if (!$token) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT u.id FROM users u
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

// Eingabe lesen
$data = json_decode(file_get_contents("php://input"), true);
$name = trim($data['name'] ?? '');

if (!$name || mb_strlen($name) < 2) {
    echo json_encode(["status" => "error", "message" => "Name muss mindestens 2 Zeichen lang sein."]);
    exit;
}

if (mb_strlen($name) > 50) {
    echo json_encode(["status" => "error", "message" => "Name darf maximal 50 Zeichen lang sein."]);
    exit;
}

// Name in DB aktualisieren
$stmt = $pdo->prepare("UPDATE users SET name = :name WHERE id = :id");
$stmt->execute([':name' => $name, ':id' => $user['id']]);

echo json_encode(["status" => "success"]);