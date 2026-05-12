<?php
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../system/config.php';

$data      = json_decode(file_get_contents("php://input"), true);
$token     = trim($data['token']     ?? '');
$firstName = trim($data['firstName'] ?? '');
$email     = trim($data['email']     ?? '');
$password  = trim($data['password']  ?? '');

if (!$token || !$firstName || !$email || !$password) {
    echo json_encode(["status" => "error", "message" => "Alle Felder sind erforderlich."]);
    exit;
}

if (mb_strlen($password) < 8) {
    echo json_encode(["status" => "error", "message" => "Passwort muss mindestens 8 Zeichen lang sein."]);
    exit;
}

// Einladung prüfen
$stmt = $pdo->prepare("
    SELECT fi.id, fi.family_id, fi.email, f.name as last_name
    FROM family_invites fi
    JOIN families f ON fi.family_id = f.id
    WHERE fi.token = :token AND fi.used = 0
");
$stmt->execute([':token' => $token]);
$invite = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invite || $invite['email'] !== $email) {
    echo json_encode(["status" => "error", "message" => "Ungültige oder abgelaufene Einladung."]);
    exit;
}

// E-Mail bereits vergeben?
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
$stmt->execute([':email' => $email]);
if ($stmt->fetch()) {
    echo json_encode(["status" => "error", "message" => "Diese E-Mail-Adresse wird bereits verwendet."]);
    exit;
}

try {
    $pdo->beginTransaction();

    // User erstellen (kein Admin, Familienname von Familie)
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $userStmt = $pdo->prepare("
        INSERT INTO users (name, last_name, email, password, family_id, is_admin)
        VALUES (:name, :last_name, :email, :password, :family_id, 0)
    ");
    $userStmt->execute([
        ':name'      => $firstName,
        ':last_name' => $invite['last_name'],
        ':email'     => $email,
        ':password'  => $hashedPassword,
        ':family_id' => $invite['family_id'],
    ]);

    $userId = $pdo->lastInsertId();

    // Session-Token erstellen
    $sessionToken = bin2hex(random_bytes(32));
    $pdo->prepare("
        INSERT INTO user_sessions (user_id, token)
        VALUES (:uid, :token)
    ")->execute([':uid' => $userId, ':token' => $sessionToken]);

    // Einladung als verwendet markieren
    $pdo->prepare("UPDATE family_invites SET used = 1 WHERE id = :id")
        ->execute([':id' => $invite['id']]);

    $pdo->commit();

    // Cookie setzen
    $expires = gmdate('D, d M Y H:i:s T', time() + 86400);
    header("Set-Cookie: auth_token=$sessionToken; Expires=$expires; Path=/; HttpOnly; SameSite=Lax");

    echo json_encode(["status" => "success"]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["status" => "error", "message" => "Registrierung fehlgeschlagen."]);
}