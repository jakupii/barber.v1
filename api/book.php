<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Cache-Control: no-store, private, max-age=0');
header('Pragma: no-cache');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'message' => 'Metoda nuk lejohet.'], 405);
}

require_csrf(true);
$data = request_data();

$name = trim((string) ($data['customer_name'] ?? ''));
$phone = normalize_phone((string) ($data['phone'] ?? ''));
$email = trim((string) ($data['email'] ?? ''));
$barberId = filter_var($data['barber_id'] ?? null, FILTER_VALIDATE_INT);
$serviceId = filter_var($data['service_id'] ?? null, FILTER_VALIDATE_INT);
$date = trim((string) ($data['appointment_date'] ?? ''));
$time = trim((string) ($data['appointment_time'] ?? ''));
$notes = trim((string) ($data['notes'] ?? ''));
$language = in_array($data['language'] ?? '', ['sq', 'mk', 'en'], true) ? (string) $data['language'] : 'sq';

if (mb_strlen($name) < 2 || mb_strlen($name) > 120 || !$barberId || !$serviceId || !valid_phone($phone)) {
    json_response(['ok' => false, 'message' => 'Plotëso emrin, telefonin, berberin dhe shërbimin.'], 422);
}
if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190)) {
    json_response(['ok' => false, 'message' => 'Email-i nuk është i vlefshëm.'], 422);
}
if (mb_strlen($notes) > 500 || !is_valid_booking_date($date) || !is_valid_slot($time)) {
    json_response(['ok' => false, 'message' => 'Data ose ora nuk është e vlefshme. Lejohen vetëm 3 ditët e ardhshme.'], 422);
}

$start = new DateTimeImmutable($date . ' ' . $time);
if ($start <= new DateTimeImmutable()) {
    json_response(['ok' => false, 'message' => 'Ky orar ka kaluar. Zgjidh një orar tjetër.'], 422);
}

$pdo = db();
$clientIp = request_ip();
if (rate_limit_exceeded('booking_ip', $clientIp, 12, 600)) {
    header('Retry-After: 600');
    json_response([
        'ok' => false,
        'error_code' => 'booking_rate_limit_ip',
        'message' => 'Shumë kërkesa nga kjo pajisje. Prit 10 minuta dhe provo përsëri.',
    ], 429);
}
if (rate_limit_exceeded('booking_phone', $phone, 4, 600)) {
    header('Retry-After: 600');
    json_response([
        'ok' => false,
        'error_code' => 'booking_rate_limit_phone',
        'message' => 'Janë dërguar shumë kërkesa me këtë numër. Prit 10 minuta dhe provo përsëri.',
    ], 429);
}
record_rate_limit_event('booking_ip', $clientIp);
record_rate_limit_event('booking_phone', $phone);

[$phoneCondition, $phoneValues] = phone_lookup_condition('phone', $phone);
$activeBookingStatement = $pdo->prepare(
    "SELECT COUNT(*) FROM bookings
     WHERE {$phoneCondition} AND status IN ('pending', 'accepted') AND appointment_date >= ?"
);
$activeBookingStatement->execute([
    ...$phoneValues,
    (new DateTimeImmutable('today'))->format('Y-m-d'),
]);
if ((int) $activeBookingStatement->fetchColumn() >= 3) {
    header('Retry-After: 600');
    json_response([
        'ok' => false,
        'error_code' => 'active_booking_limit',
        'message' => 'Ky numër ka tashmë 3 rezervime aktive. Prit vendimin ose kontakto sallonin.',
    ], 429);
}

$statement = $pdo->prepare(
    'SELECT b.id AS barber_id, b.name AS barber_name,
            s.id AS service_id, s.name_sq, s.name_mk, s.name_en, s.price_cents, s.duration_minutes
     FROM barbers b CROSS JOIN services s
     WHERE b.id = ? AND b.active = 1 AND s.id = ? AND s.active = 1'
);
$statement->execute([$barberId, $serviceId]);
$selection = $statement->fetch();
if (!$selection) {
    json_response(['ok' => false, 'message' => 'Berberi ose shërbimi nuk është më aktiv.'], 422);
}

$duration = max(30, min(240, (int) $selection['duration_minutes']));
$closingTime = booking_closing_datetime($date);
if ($start->modify('+' . $duration . ' minutes') > $closingTime) {
    json_response(['ok' => false, 'message' => 'Shërbimi nuk përfundon brenda orarit të punës. Zgjidh një orë më të hershme.'], 422);
}

$publicCode = new_booking_code($pdo);
$accessToken = bin2hex(random_bytes(24));
$tokenHash = hash('sha256', $accessToken);
$serviceName = localized_value($selection, 'name', $language);
$driver = (string) config('database.driver', 'sqlite');
$interval = booking_slot_interval_minutes();
$blocks = (int) ceil($duration / $interval);
$slotStarts = [];
$slotTimes = [];
for ($i = 0; $i < $blocks; $i++) {
    $block = $start->modify('+' . ($i * $interval) . ' minutes');
    $slotStarts[] = $block->format('Y-m-d H:i:s');
    $slotTimes[$block->format('H:i')] = true;
}

try {
    // Serialises bookings and schedule changes for this barber.
    db_begin_for_barber($pdo, $driver, (int) $barberId);

    $blockedCheck = $pdo->prepare('SELECT blocked_time FROM blocked_slots WHERE barber_id = ? AND blocked_date = ?');
    $blockedCheck->execute([$barberId, $date]);
    $blockedByAdmin = false;
    foreach ($blockedCheck->fetchAll() as $row) {
        if (isset($slotTimes[substr((string) $row['blocked_time'], 0, 5)])) {
            $blockedByAdmin = true;
            break;
        }
    }
    if ($blockedByAdmin) {
        db_rollback($pdo, $driver);
        json_response(['ok' => false, 'message' => 'Ky orar është bllokuar nga berberi. Zgjidh një orar tjetër.'], 409);
    }

    $insert = $pdo->prepare(
        'INSERT INTO bookings
         (public_code, barber_id, service_id, access_token_hash, customer_name, phone, email,
          appointment_date, appointment_time, language, notes, service_name_snapshot,
          price_cents_snapshot, duration_minutes_snapshot, status, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
    );
    $insert->execute([
        $publicCode,
        $barberId,
        $serviceId,
        $tokenHash,
        $name,
        $phone,
        $email,
        $date,
        $time,
        $language,
        $notes,
        $serviceName,
        (int) $selection['price_cents'],
        $duration,
        'pending',
    ]);
    $bookingId = (int) $pdo->lastInsertId();

    $slotInsert = $pdo->prepare('INSERT INTO appointment_slots (booking_id, barber_id, slot_start) VALUES (?, ?, ?)');
    foreach ($slotStarts as $slotStart) {
        $slotInsert->execute([$bookingId, $barberId, $slotStart]);
    }

    db_commit($pdo, $driver);
} catch (Throwable $exception) {
    db_rollback($pdo, $driver);

    if ($exception instanceof PDOException && in_array((string) $exception->getCode(), ['19', '23000'], true)) {
        json_response(['ok' => false, 'message' => 'Ky orar sapo u rezervua. Zgjidh një orar tjetër.'], 409);
    }
    if ($exception instanceof RuntimeException) {
        json_response(['ok' => false, 'message' => $exception->getMessage()], 409);
    }
    error_log($exception->getMessage());
    json_response(['ok' => false, 'message' => 'Rezervimi nuk u ruajt. Provo përsëri.'], 500);
}

json_response([
    'ok' => true,
    'message' => 'Rezervimi u dërgua dhe është në pritje të konfirmimit.',
], 201);
