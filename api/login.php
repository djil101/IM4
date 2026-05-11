<?php
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../system/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Ungültige Anfragemethode"]);
    exit;
}

$data     = json_decode(file_get_contents("php://input"), true);
$email    = trim($data['email']    ?? '');
$password = trim($data['password'] ?? '');

if (!$email || !$password) {
    echo json_encode(["status" => "error", "message" => "E-Mail und Passwort sind erforderlich"]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, password, name FROM users WHERE email = :email");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(["status" => "error", "message" => "Ungültige Anmeldedaten"]);
    exit;
}

// Ensure token table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS user_sessions (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    token      VARCHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Replace any previous session for this user and create a fresh token
$token = bin2hex(random_bytes(32));
$pdo->prepare("DELETE FROM user_sessions WHERE user_id = :uid")->execute([':uid' => $user['id']]);
$pdo->prepare("INSERT INTO user_sessions (user_id, token) VALUES (:uid, :token)")
    ->execute([':uid' => $user['id'], ':token' => $token]);

// header() works on all PHP versions; setcookie() array form requires PHP 7.3+
$expires = gmdate('D, d M Y H:i:s T', time() + 86400);
header("Set-Cookie: auth_token=$token; Expires=$expires; Path=/; HttpOnly; SameSite=Lax");

echo json_encode(["status" => "success"]);
