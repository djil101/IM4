<?php
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../system/config.php';

$token = $_COOKIE['auth_token'] ?? '';

if ($token) {
    try {
        $pdo->prepare("DELETE FROM user_sessions WHERE token = :token")
            ->execute([':token' => $token]);
    } catch (Exception $e) {
        // table may not exist yet — safe to ignore
    }
}

$expires = gmdate('D, d M Y H:i:s T', time() - 3600);
header("Set-Cookie: auth_token=; Expires=$expires; Path=/; HttpOnly; SameSite=Lax");

echo json_encode(["status" => "success"]);
