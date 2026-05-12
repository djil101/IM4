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

// Einstellungen laden
$stmt = $pdo->prepare("
    SELECT enabled, quiet_from, quiet_until
    FROM notification_settings
    WHERE user_id = :uid
");
$stmt->execute([':uid' => $user['id']]);
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Falls noch keine Einstellungen existieren: Standardwerte zurückgeben
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