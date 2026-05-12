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
    SELECT u.id, u.family_id, u.is_admin, u.name, f.name as family_name
    FROM users u
    JOIN user_sessions s ON u.id = s.user_id
    JOIN families f ON u.family_id = f.id
    WHERE s.token = :token
");
$stmt->execute([':token' => $token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// Nur Admins dürfen einladen
if (!$user['is_admin']) {
    http_response_code(403);
    echo json_encode(["error" => "Nur Admins können Familienmitglieder einladen."]);
    exit;
}

$data  = json_decode(file_get_contents("php://input"), true);
$email = trim($data['email'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "error", "message" => "Bitte eine gültige E-Mail-Adresse eingeben."]);
    exit;
}

// Bereits Mitglied?
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND family_id = :fid");
$stmt->execute([':email' => $email, ':fid' => $user['family_id']]);
if ($stmt->fetch()) {
    echo json_encode(["status" => "error", "message" => "Diese Person ist bereits Mitglied deiner Familie."]);
    exit;
}

// Alte Einladung löschen und neue erstellen
$inviteToken = bin2hex(random_bytes(32));
$pdo->prepare("DELETE FROM family_invites WHERE email = :email AND family_id = :fid")
    ->execute([':email' => $email, ':fid' => $user['family_id']]);

$pdo->prepare("
    INSERT INTO family_invites (family_id, email, token)
    VALUES (:fid, :email, :token)
")->execute([
    ':fid'   => $user['family_id'],
    ':email' => $email,
    ':token' => $inviteToken,
]);

$inviteLink = "https://djil.afopulax.myhostpoint.ch/invite.html?token=$inviteToken";

// E-Mail senden mit PHP mail()
$subject = '=?UTF-8?B?' . base64_encode('Du wurdest zu NestSignal eingeladen') . '?=';
$body    = "Hallo!\r\n\r\n"
         . "{$user['name']} hat dich eingeladen, der Familie {$user['family_name']} auf NestSignal beizutreten.\r\n\r\n"
         . "Klicke auf diesen Link um dein Konto zu erstellen:\r\n"
         . "$inviteLink\r\n\r\n"
         . "Falls du diese E-Mail nicht erwartet hast, kannst du sie ignorieren.\r\n\r\n"
         . "NestSignal";

$headers = "From: NestSignal <noreply@djil.afopulax.myhostpoint.ch>\r\n"
         . "Reply-To: noreply@djil.afopulax.myhostpoint.ch\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\n"
         . "Content-Transfer-Encoding: base64\r\n";

$body = base64_encode($body);

if (mail($email, $subject, $body, $headers)) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "E-Mail konnte nicht gesendet werden."]);
}