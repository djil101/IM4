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

// Kein Gerät verbunden → Standardwerte
if (!$device) {
    echo json_encode([
        "enabled"     => true,
        "quiet_from"  => null,
        "quiet_until" => null,
        "no_device"   => true
    ]);
    exit;
}

// Einstellungen für dieses Device laden
$stmt = $pdo->prepare("
    SELECT enabled, quiet_from, quiet_until
    FROM notification_settings
    WHERE device_id = :device_id
");
$stmt->execute([':device_id' => $device['id']]);
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Noch keine Einstellungen → Standardwerte
if (!$settings) {
    echo json_encode([
        "enabled"     => true,
        "quiet_from"  => null,
        "quiet_until" => null
    ]);
    exit;
}

echo json_encode([
    "enabled"     => (bool) $settings['enabled'],
    "quiet_from"  => $settings['quiet_from'],
    "quiet_until" => $settings['quiet_until']
]);