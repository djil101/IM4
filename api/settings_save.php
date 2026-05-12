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

// Device der Familie holen
$stmt = $pdo->prepare("
    SELECT id FROM devices WHERE family_id = :family_id LIMIT 1
");
$stmt->execute([':family_id' => $user['family_id']]);
$device = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$device) {
    echo json_encode(["status" => "error", "message" => "Kein Gerät verbunden."]);
    exit;
}

// Eingabe lesen
$data        = json_decode(file_get_contents("php://input"), true);
$enabled     = isset($data['enabled']) ? (int) $data['enabled'] : 1;
$quiet_from  = $data['quiet_from']  ?? null;
$quiet_until = $data['quiet_until'] ?? null;

// Zeitformat validieren (HH:MM oder null)
$timeRegex = '/^\d{2}:\d{2}$/';
if ($quiet_from  && !preg_match($timeRegex, $quiet_from))  $quiet_from  = null;
if ($quiet_until && !preg_match($timeRegex, $quiet_until)) $quiet_until = null;

// Upsert: einfügen oder aktualisieren
$stmt = $pdo->prepare("
    INSERT INTO notification_settings (device_id, enabled, quiet_from, quiet_until)
    VALUES (:device_id, :enabled, :from, :until)
    ON DUPLICATE KEY UPDATE
        enabled     = VALUES(enabled),
        quiet_from  = VALUES(quiet_from),
        quiet_until = VALUES(quiet_until)
");
$stmt->execute([
    ':device_id' => $device['id'],
    ':enabled'   => $enabled,
    ':from'      => $quiet_from,
    ':until'     => $quiet_until
]);

echo json_encode(["status" => "success"]);