<?php
/**
 * NestSignal – Sensor Data API Endpoint
 * Datei: /api/sensor_data.php
 *
 * Empfängt HTTP POST vom ESP32-C6 und schreibt Daten in die Datenbank.
 * Unterstützt: action=sensor_data | action=wake_event
 */

// ──────────────────────────────────────────────
//  KONFIGURATION
// ──────────────────────────────────────────────

require_once _DIR_ . '/../system/config.php';

// ──────────────────────────────────────────────

// ──────────────────────────────────────────────

header('Content-Type: application/json');

function jsonResponse($success, $message, $data = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

function getDB() {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($db->connect_error) {
        jsonResponse(false, 'DB-Verbindung fehlgeschlagen: ' . $db->connect_error);
    }
    $db->set_charset('utf8mb4');
    return $db;
}

// ──────────────────────────────────────────────
//  REQUEST VALIDIERUNG
// ──────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Nur POST erlaubt.');
}

$api_secret  = $_POST['api_secret']  ?? '';
$action      = $_POST['action']      ?? '';
$device_id   = intval($_POST['device_id'] ?? 0);

// API-Secret prüfen
if ($api_secret !== API_SECRET) {
    http_response_code(403);
    jsonResponse(false, 'Ungültiges API-Secret.');
}

// device_id prüfen
if ($device_id <= 0) {
    jsonResponse(false, 'Ungültige device_id.');
}

// ──────────────────────────────────────────────
//  ACTION: sensor_data – INSERT in device_data
// ──────────────────────────────────────────────

if ($action === 'sensor_data') {
    $bewegung = intval($_POST['bewegung'] ?? 0);
    $laerm    = intval($_POST['laerm']    ?? 0);

    // Nur 0 oder 1 erlaubt
    $bewegung = ($bewegung === 1) ? 1 : 0;
    $laerm    = ($laerm    === 1) ? 1 : 0;

    $db = getDB();

    $stmt = $db->prepare(
        "INSERT INTO device_data (device_id, bewegung, laerm)
         VALUES (?, ?, ?)"
    );
    $stmt->bind_param('iii', $device_id, $bewegung, $laerm);

    if ($stmt->execute()) {
        $insertId = $stmt->insert_id;
        $stmt->close();
        $db->close();
        jsonResponse(true, 'device_data gespeichert.', ['insert_id' => $insertId]);
    } else {
        $err = $stmt->error;
        $stmt->close();
        $db->close();
        jsonResponse(false, 'DB-Fehler: ' . $err);
    }
}

// ──────────────────────────────────────────────
//  ACTION: wake_event – INSERT in wake_events
// ──────────────────────────────────────────────

elseif ($action === 'wake_event') {
    $trigger_type = $_POST['trigger_type'] ?? '';

    // Nur erlaubte Werte
    $allowed = ['bewegung', 'laerm', 'beides'];
    if (!in_array($trigger_type, $allowed)) {
        jsonResponse(false, 'Ungültiger trigger_type. Erlaubt: bewegung, laerm, beides');
    }

    $db = getDB();

    $stmt = $db->prepare(
        "INSERT INTO wake_events (device_id, trigger_type, triggered_at)
         VALUES (?, ?, NOW())"
    );
    $stmt->bind_param('is', $device_id, $trigger_type);

    if ($stmt->execute()) {
        $insertId = $stmt->insert_id;
        $stmt->close();
        $db->close();
        jsonResponse(true, 'wake_event gespeichert.', ['insert_id' => $insertId]);
    } else {
        $err = $stmt->error;
        $stmt->close();
        $db->close();
        jsonResponse(false, 'DB-Fehler: ' . $err);
    }
}

// ──────────────────────────────────────────────
//  UNBEKANNTE ACTION
// ──────────────────────────────────────────────

else {
    jsonResponse(false, 'Unbekannte action: ' . htmlspecialchars($action));
}
