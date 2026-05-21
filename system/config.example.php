<?php
// ──────────────────────────────────────────────
//   * Beispieldatei für config.php -> enthält keine echten Zugangsdaten.
// ──────────────────────────────────────────────

// Datenbankverbindung (für WebApp via PDO)
$host     = 'DEIN_DB_HOST';
$dbname   = 'DEIN_DB_NAME';
$username = 'DEIN_DB_USER';
$password = 'DEIN_DB_PASSWORT';

// API-Konfiguration (für sensor_data.php)
define('API_SECRET', 'DEIN_API_SECRET');
define('DB_HOST',    'DEIN_DB_HOST');
define('DB_NAME',    'DEIN_DB_NAME');
define('DB_USER',    'DEIN_DB_USER');
define('DB_PASS',    'DEIN_DB_PASSWORT');

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    echo "Database connection error: " . $e->getMessage();
    exit;
}
