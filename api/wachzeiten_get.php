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
    SELECT u.family_id FROM users u
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
$stmt = $pdo->prepare("SELECT id FROM devices WHERE family_id = :fid LIMIT 1");
$stmt->execute([':fid' => $user['family_id']]);
$device = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$device) {
    echo json_encode(["events" => [], "week" => [], "avg_time" => null, "top_trigger" => null]);
    exit;
}

// Notification Settings laden (für Ruhefenster-Check)
$stmt = $pdo->prepare("
    SELECT enabled, quiet_from, quiet_until
    FROM notification_settings
    WHERE device_id = :did
");
$stmt->execute([':did' => $device['id']]);
$notif = $stmt->fetch(PDO::FETCH_ASSOC);

// Alle Wake Events der letzten 30 Tage laden
$stmt = $pdo->prepare("
    SELECT id, trigger_type, triggered_at
    FROM wake_events
    WHERE device_id = :did
    AND triggered_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY triggered_at DESC
");
$stmt->execute([':did' => $device['id']]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ruhefenster-Check Funktion
function isInQuietWindow($time, $notif) {
    if (!$notif || !$notif['enabled'] || !$notif['quiet_from'] || !$notif['quiet_until']) return false;
    $t     = substr($time, 11, 5); // HH:MM aus datetime
    $from  = substr($notif['quiet_from'],  0, 5);
    $until = substr($notif['quiet_until'], 0, 5);
    if ($from <= $until) {
        return $t >= $from && $t <= $until;
    } else {
        // Mitternacht überschreitend z.B. 22:00 - 06:00
        return $t >= $from || $t <= $until;
    }
}

// Events aufbereiten
$formattedEvents = [];
foreach ($events as $e) {
    $formattedEvents[] = [
        "id"           => $e['id'],
        "trigger_type" => $e['trigger_type'],
        "triggered_at" => $e['triggered_at'],
        "in_quiet"     => isInQuietWindow($e['triggered_at'], $notif),
    ];
}

// Woche: erste Wachzeit pro Tag (Mo-So dieser Woche)
$weekData = [];
$today = new DateTime();
$monday = clone $today;
$monday->modify('this week monday');

for ($i = 0; $i < 7; $i++) {
    $day = clone $monday;
    $day->modify("+$i days");
    $dayStr = $day->format('Y-m-d');

    $stmt = $pdo->prepare("
        SELECT triggered_at FROM wake_events
        WHERE device_id = :did
        AND DATE(triggered_at) = :day
        ORDER BY triggered_at ASC
        LIMIT 1
    ");
    $stmt->execute([':did' => $device['id'], ':day' => $dayStr]);
    $first = $stmt->fetch(PDO::FETCH_ASSOC);

    $weekData[] = [
        "day"  => ['Mo','Di','Mi','Do','Fr','Sa','So'][$i],
        "time" => $first ? substr($first['triggered_at'], 11, 5) : null,
    ];
}

// Durchschnittliche erste Wachzeit (nur Tage mit Daten)
$times = array_filter(array_column($weekData, 'time'));
$avgTime = null;
if (count($times) > 0) {
    $totalMinutes = 0;
    foreach ($times as $t) {
        [$h, $m] = explode(':', $t);
        $totalMinutes += $h * 60 + $m;
    }
    $avg = round($totalMinutes / count($times));
    $avgTime = sprintf('%02d:%02d', intdiv($avg, 60), $avg % 60);
}

// Häufigster Auslöser (letzte 7 Tage)
$stmt = $pdo->prepare("
    SELECT trigger_type, COUNT(*) as cnt
    FROM wake_events
    WHERE device_id = :did
    AND triggered_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY trigger_type
    ORDER BY cnt DESC
    LIMIT 1
");
$stmt->execute([':did' => $device['id']]);
$top = $stmt->fetch(PDO::FETCH_ASSOC);
$topTrigger = $top ? ucfirst($top['trigger_type']) : null;

echo json_encode([
    "events"      => $formattedEvents,
    "week"        => $weekData,
    "avg_time"    => $avgTime,
    "top_trigger" => $topTrigger,
]);