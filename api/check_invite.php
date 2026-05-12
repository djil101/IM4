<?php
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../system/config.php';

$token = $_GET['token'] ?? '';

if (!$token) {
    echo json_encode(["status" => "invalid"]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT fi.email, f.name as family_name
    FROM family_invites fi
    JOIN families f ON fi.family_id = f.id
    WHERE fi.token = :token AND fi.used = 0
");
$stmt->execute([':token' => $token]);
$invite = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invite) {
    echo json_encode(["status" => "invalid"]);
    exit;
}

echo json_encode([
    "status"      => "valid",
    "email"       => $invite['email'],
    "family_name" => $invite['family_name']
]);