<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

header('Cache-Control: no-store, private');

$user = current_user();
if (!$user) {
    json_response(['ok' => false, 'message' => 'Duhet të hyni si administrator.'], 401);
}
if ($user['role'] !== 'admin') {
    json_response(['ok' => false, 'message' => 'Vetëm administratori mund ta ndryshojë orarin.'], 403);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    json_response(['ok' => false, 'message' => 'Metoda nuk lejohet.'], 405);
}

$data = $method === 'POST' ? request_data() : $_GET;
if ($method === 'POST') {
    $submittedToken = (string) ($data['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    if (!verify_csrf($submittedToken)) {
        json_response(['ok' => false, 'message' => 'Sesioni ka skaduar. Rifresko faqen dhe provo përsëri.'], 419);
    }
}

$barberId = filter_var($data['barber_id'] ?? null, FILTER_VALIDATE_INT);
$date = trim((string) ($data['date'] ?? ''));
$slots = booking_slots();

if (!$barberId || !is_valid_booking_date($date)) {
    json_response(['ok' => false, 'message' => 'Zgjidh një berber dhe një nga 3 ditët e disponueshme.'], 422);
}
if ($slots === []) {
    json_response(['ok' => false, 'message' => 'Nuk ka orare të konfiguruara.'], 500);
}

$pdo = db();
$barberStatement = $pdo->prepare('SELECT id, name, active FROM barbers WHERE id = ?');
$barberStatement->execute([$barberId]);
$barber = $barberStatement->fetch();
if (!$barber) {
    json_response(['ok' => false, 'message' => 'Berberi nuk u gjet.'], 404);
}

if ($method === 'GET') {
    json_response(schedule_snapshot($pdo, $barber, $date, $slots));
}

$action = trim((string) ($data['action'] ?? ''));
$driver = (string) config('database.driver', 'sqlite');

if (in_array($action, ['toggle_slot', 'block_slot', 'unblock_slot'], true)) {
    $time = trim((string) ($data['time'] ?? ''));
    if (!in_array($time, $slots, true)) {
        json_response(['ok' => false, 'message' => 'Ora e zgjedhur nuk është pjesë e orarit të konfiguruar.'], 422);
    }

    $requestedState = match ($action) {
        'block_slot' => true,
        'unblock_slot' => false,
        default => array_key_exists('blocked', $data) ? schedule_boolean($data['blocked']) : null,
    };
    if (array_key_exists('blocked', $data) && $action === 'toggle_slot' && $requestedState === null) {
        json_response(['ok' => false, 'message' => 'Gjendja e orarit nuk është e vlefshme.'], 422);
    }

    try {
        db_begin_for_barber($pdo, $driver, (int) $barberId);

        if ($requestedState === null) {
            $existing = $pdo->prepare('SELECT COUNT(*) FROM blocked_slots WHERE barber_id = ? AND blocked_date = ? AND blocked_time = ?');
            $existing->execute([$barberId, $date, $time]);
            $requestedState = (int) $existing->fetchColumn() === 0;
        }

        if ($requestedState) {
            $booking = schedule_booking_at($pdo, (int) $barberId, $date, $time);
            if ($booking) {
                db_rollback($pdo, $driver);
                json_response([
                    'ok' => false,
                    'message' => 'Kjo orë ka një rezervim aktiv dhe nuk mund të bllokohet.',
                    'conflict' => schedule_public_booking($booking),
                ], 409);
            }
            schedule_insert_block($pdo, $driver, (int) $barberId, $date, $time, (int) $user['id']);
        } else {
            $delete = $pdo->prepare('DELETE FROM blocked_slots WHERE barber_id = ? AND blocked_date = ? AND blocked_time = ?');
            $delete->execute([$barberId, $date, $time]);
        }

        db_commit($pdo, $driver);
    } catch (Throwable $exception) {
        db_rollback($pdo, $driver);
        error_log($exception->getMessage());
        json_response(['ok' => false, 'message' => 'Orari nuk u përditësua. Provo përsëri.'], 500);
    }

    $payload = schedule_snapshot($pdo, $barber, $date, $slots);
    $payload['message'] = $requestedState ? 'Ora u bllokua.' : 'Ora u lirua.';
    $payload['changed'] = ['time' => $time, 'blocked' => $requestedState];
    json_response($payload);
}

if (in_array($action, ['set_day', 'toggle_day', 'block_day', 'unblock_day'], true)) {
    $requestedState = match ($action) {
        'block_day' => true,
        'unblock_day' => false,
        default => array_key_exists('blocked', $data) ? schedule_boolean($data['blocked']) : null,
    };
    if (array_key_exists('blocked', $data) && $requestedState === null) {
        json_response(['ok' => false, 'message' => 'Gjendja e ditës nuk është e vlefshme.'], 422);
    }
    if ($action === 'set_day' && $requestedState === null) {
        json_response(['ok' => false, 'message' => 'Përcakto nëse dita duhet bllokuar ose liruar.'], 422);
    }

    $conflicts = [];
    try {
        db_begin_for_barber($pdo, $driver, (int) $barberId);

        if ($requestedState === null) {
            $placeholders = implode(',', array_fill(0, count($slots), '?'));
            $blockedCount = $pdo->prepare(
                'SELECT COUNT(*) FROM blocked_slots WHERE barber_id = ? AND blocked_date = ? AND blocked_time IN (' . $placeholders . ')'
            );
            $blockedCount->execute(array_merge([(int) $barberId, $date], $slots));
            $requestedState = (int) $blockedCount->fetchColumn() < count($slots);
        }

        if (!$requestedState) {
            $delete = $pdo->prepare('DELETE FROM blocked_slots WHERE barber_id = ? AND blocked_date = ?');
            $delete->execute([$barberId, $date]);
        } else {
            $bookings = schedule_bookings_for_day($pdo, (int) $barberId, $date);
            foreach ($slots as $time) {
                if (isset($bookings[$time])) {
                    $conflicts[] = schedule_public_booking($bookings[$time]) + ['time' => $time];
                    continue;
                }
                schedule_insert_block($pdo, $driver, (int) $barberId, $date, $time, (int) $user['id']);
            }
        }

        db_commit($pdo, $driver);
    } catch (Throwable $exception) {
        db_rollback($pdo, $driver);
        error_log($exception->getMessage());
        json_response(['ok' => false, 'message' => 'Dita nuk u përditësua. Provo përsëri.'], 500);
    }

    $payload = schedule_snapshot($pdo, $barber, $date, $slots);
    $payload['message'] = !$requestedState
        ? 'Dita u lirua.'
        : ($conflicts === [] ? 'Dita u bllokua.' : 'Dita u bllokua; rezervimet ekzistuese u ruajtën.');
    $payload['conflicts'] = $conflicts;
    json_response($payload);
}

json_response(['ok' => false, 'message' => 'Veprimi i orarit nuk njihet.'], 422);

function schedule_boolean(mixed $value): ?bool
{
    if (is_bool($value)) {
        return $value;
    }
    if (is_int($value) || is_float($value)) {
        return (int) $value === 1 ? true : ((int) $value === 0 ? false : null);
    }
    $value = strtolower(trim((string) $value));
    if (in_array($value, ['1', 'true', 'yes', 'on'], true)) {
        return true;
    }
    if (in_array($value, ['0', 'false', 'no', 'off'], true)) {
        return false;
    }
    return null;
}

function schedule_insert_block(PDO $pdo, string $driver, int $barberId, string $date, string $time, int $userId): void
{
    $sql = $driver === 'sqlite'
        ? 'INSERT OR IGNORE INTO blocked_slots (barber_id, blocked_date, blocked_time, created_by, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)'
        : 'INSERT INTO blocked_slots (barber_id, blocked_date, blocked_time, created_by, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE barber_id = VALUES(barber_id)';
    $insert = $pdo->prepare($sql);
    $insert->execute([$barberId, $date, $time, $userId]);
}

function schedule_booking_at(PDO $pdo, int $barberId, string $date, string $time): ?array
{
    $statement = $pdo->prepare(
        "SELECT bk.id, bk.public_code, bk.customer_name, bk.phone, bk.status, bk.service_name_snapshot
         FROM appointment_slots aps
         JOIN bookings bk ON bk.id = aps.booking_id
         WHERE aps.barber_id = ? AND aps.slot_start = ? AND bk.status IN ('pending', 'accepted')
         LIMIT 1"
    );
    $statement->execute([$barberId, $date . ' ' . $time . ':00']);
    $booking = $statement->fetch();
    return $booking ?: null;
}

function schedule_bookings_for_day(PDO $pdo, int $barberId, string $date): array
{
    $start = $date . ' 00:00:00';
    $end = (new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d') . ' 00:00:00';
    $statement = $pdo->prepare(
        "SELECT aps.slot_start, bk.id, bk.public_code, bk.customer_name, bk.phone, bk.status, bk.service_name_snapshot
         FROM appointment_slots aps
         JOIN bookings bk ON bk.id = aps.booking_id
         WHERE aps.barber_id = ? AND aps.slot_start >= ? AND aps.slot_start < ?
           AND bk.status IN ('pending', 'accepted')"
    );
    $statement->execute([$barberId, $start, $end]);
    $bookings = [];
    foreach ($statement->fetchAll() as $row) {
        $bookings[substr((string) $row['slot_start'], 11, 5)] = $row;
    }
    return $bookings;
}

function schedule_public_booking(array $booking): array
{
    return [
        'id' => (int) $booking['id'],
        'code' => (string) $booking['public_code'],
        'customer' => (string) $booking['customer_name'],
        'phone' => (string) $booking['phone'],
        'service' => (string) $booking['service_name_snapshot'],
        'status' => (string) $booking['status'],
    ];
}

function schedule_snapshot(PDO $pdo, array $barber, string $date, array $slots): array
{
    $availableDates = booking_dates();
    $blockedStatement = $pdo->prepare('SELECT blocked_time FROM blocked_slots WHERE barber_id = ? AND blocked_date = ?');
    $blockedStatement->execute([(int) $barber['id'], $date]);
    $blocked = [];
    foreach ($blockedStatement->fetchAll() as $row) {
        $blocked[substr((string) $row['blocked_time'], 0, 5)] = true;
    }

    $bookings = schedule_bookings_for_day($pdo, (int) $barber['id'], $date);
    $records = [];
    $blockedCount = 0;
    $bookedCount = 0;
    foreach ($slots as $time) {
        $isBlocked = isset($blocked[$time]);
        $booking = $bookings[$time] ?? null;
        $blockedCount += $isBlocked ? 1 : 0;
        $bookedCount += $booking ? 1 : 0;
        $records[] = [
            'time' => $time,
            'state' => $booking ? 'booked' : ($isBlocked ? 'blocked' : 'available'),
            'blocked' => $isBlocked,
            'booked' => (bool) $booking,
            'booking' => $booking ? schedule_public_booking($booking) : null,
        ];
    }

    return [
        'ok' => true,
        'csrf_token' => csrf_token(),
        'barber' => [
            'id' => (int) $barber['id'],
            'name' => (string) $barber['name'],
            'active' => (bool) $barber['active'],
        ],
        'date' => $date,
        'date_range' => [
            'min' => $availableDates[0],
            'max' => $availableDates[count($availableDates) - 1],
        ],
        'slots' => $records,
        'blocked_count' => $blockedCount,
        'booked_count' => $bookedCount,
        'day_blocked' => $blockedCount === count($slots),
        'day_closed' => count(array_filter($records, static fn (array $slot): bool => $slot['state'] !== 'available')) === count($slots),
    ];
}
