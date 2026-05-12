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
    SELECT u.id, u.family_id FROM users u
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

$data     = json_decode(file_get_contents("php://input"), true);
$serialNr = strtoupper(trim($data['serial_nr'] ?? ''));

if (!$serialNr) {
    echo json_encode(["status" => "error", "message" => "Seriennummer fehlt."]);
    exit;
}

// Gerät mit dieser Seriennummer suchen
$stmt = $pdo->prepare("SELECT id, family_id FROM devices WHERE serial_nr = :serial_nr");
$stmt->execute([':serial_nr' => $serialNr]);
$device = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$device) {
    echo json_encode(["status" => "error", "message" => "Kein Gerät mit dieser Seriennummer gefunden."]);
    exit;
}

// Gerät bereits einer anderen Familie zugewiesen?
if ($device['family_id'] !== null && $device['family_id'] != $user['family_id']) {
    echo json_encode(["status" => "error", "message" => "Dieses Gerät ist bereits mit einer anderen Familie verbunden."]);
    exit;
}

// Gerät mit der Familie des Users verknüpfen
$stmt = $pdo->prepare("UPDATE devices SET family_id = :family_id WHERE id = :id");
$stmt->execute([
    ':family_id' => $user['family_id'],
    ':id'        => $device['id'],
]);

echo json_encode(["status" => "success"]);