<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['ok' => false, 'message' => 'Metoda nuk lejohet.'], 405);
}

$barberId = filter_input(INPUT_GET, 'barber_id', FILTER_VALIDATE_INT);
$serviceId = filter_input(INPUT_GET, 'service_id', FILTER_VALIDATE_INT);
$date = trim((string) ($_GET['date'] ?? ''));

if (!$barberId || !is_valid_booking_date($date)) {
    json_response(['ok' => false, 'message' => 'Zgjidh një berber dhe datë të vlefshme.'], 422);
}

$pdo = db();
$barber = $pdo->prepare('SELECT id FROM barbers WHERE id = ? AND active = 1');
$barber->execute([$barberId]);
if (!$barber->fetch()) {
    json_response(['ok' => false, 'message' => 'Berberi nuk është i disponueshëm.'], 404);
}

$duration = 60;
if ($serviceId) {
    $service = $pdo->prepare('SELECT duration_minutes FROM services WHERE id = ? AND active = 1');
    $service->execute([$serviceId]);
    $durationValue = $service->fetchColumn();
    if ($durationValue === false) {
        json_response(['ok' => false, 'message' => 'Shërbimi nuk është i disponueshëm.'], 404);
    }
    $duration = max(30, min(240, (int) $durationValue));
}

$statement = $pdo->prepare('SELECT slot_start FROM appointment_slots WHERE barber_id = ? AND slot_start LIKE ?');
$statement->execute([$barberId, $date . '%']);
$occupiedStarts = [];
foreach ($statement->fetchAll() as $row) {
    $occupiedStarts[substr((string) $row['slot_start'], 11, 5)] = true;
}

$blockedStatement = $pdo->prepare('SELECT blocked_time FROM blocked_slots WHERE barber_id = ? AND blocked_date = ?');
$blockedStatement->execute([$barberId, $date]);
$blockedStarts = [];
foreach ($blockedStatement->fetchAll() as $row) {
    $blockedStarts[substr((string) $row['blocked_time'], 0, 5)] = true;
}

$now = new DateTimeImmutable();
// Loop invariants: these were previously recomputed for each of the ~26 slots.
$closingTime = booking_closing_datetime($date);
$interval = booking_slot_interval_minutes();
$blocks = (int) ceil($duration / $interval);

$slots = [];
foreach (booking_slots() as $time) {
    $slotDateTime = new DateTimeImmutable($date . ' ' . $time);
    $available = $slotDateTime > $now && $slotDateTime->modify('+' . $duration . ' minutes') <= $closingTime;
    $scheduleBlocked = false;
    $bookingOccupied = false;
    for ($i = 0; $i < $blocks; $i++) {
        $block = $slotDateTime->modify('+' . ($i * $interval) . ' minutes')->format('H:i');
        if (isset($blockedStarts[$block])) {
            $scheduleBlocked = true;
            $available = false;
        }
        if (isset($occupiedStarts[$block])) {
            $bookingOccupied = true;
            $available = false;
        }
    }
    $slots[] = [
        'time' => $time,
        'available' => $available,
        'blocked' => $scheduleBlocked,
        'booked' => $bookingOccupied,
    ];
}

json_response(['ok' => true, 'date' => $date, 'slots' => $slots]);
