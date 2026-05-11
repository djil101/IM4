<?php
session_start();
header('Content-Type: application/json');

require_once '../system/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents("php://input"), true);

    $email     = trim($data['email']     ?? '');
    $password  = trim($data['password']  ?? '');
    $vorname   = trim($data['vorname']   ?? '');
    $nachname  = trim($data['nachname']  ?? '');

    if (!$email || !$password || !$vorname || !$nachname) {
    echo json_encode(["status" => "error", "message" => "Alle Felder sind erforderlich"]);
    exit;
}

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        echo json_encode(["status" => "error", "message" => "Email wird bereits verwendet"]);
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // vorname + nachname hinzugefügt
    $insert = $pdo->prepare("
        INSERT INTO users (email, password, vorname, nachname)
        VALUES (:email, :pass, :vorname, :nachname)
    ");
    $insert->execute([
        ':email'    => $email,
        ':pass'     => $hashedPassword,
        ':vorname'  => $vorname,
        ':nachname' => $nachname
    ]);

    echo json_encode(["status" => "success"]);

} else {
    echo json_encode(["status" => "error", "message" => "Ungültige Anfragemethode"]);
}