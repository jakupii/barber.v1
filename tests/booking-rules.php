<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/database.php';

function assert_booking_rule(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

date_default_timezone_set((string) config('timezone', 'Europe/Skopje'));

$dates = booking_dates();
assert_booking_rule(count($dates) === 3, 'Dritarja e rezervimit nuk ka saktësisht 3 ditë.');
assert_booking_rule($dates[0] === (new DateTimeImmutable('today'))->format('Y-m-d'), 'Dritarja nuk fillon sot.');
assert_booking_rule($dates[2] === (new DateTimeImmutable('today'))->modify('+2 days')->format('Y-m-d'), 'Dritarja nuk mbaron pas 3 ditësh kalendarike.');
assert_booking_rule(!is_valid_booking_date((new DateTimeImmutable('today'))->modify('-1 day')->format('Y-m-d')), 'U pranua një datë e kaluar.');
assert_booking_rule(!is_valid_booking_date((new DateTimeImmutable('today'))->modify('+3 days')->format('Y-m-d')), 'U pranua një datë jashtë dritares 3-ditore.');
assert_booking_rule(booking_history_cutoff(new DateTimeImmutable('2030-01-10')) === '2030-01-03', 'Kufiri i historikut nuk është 7 ditë.');

$slots = booking_slots();
assert_booking_rule(count($slots) === 26, 'Orari 09:00–22:00 nuk ka 26 fillime gjysmëorëshe.');
assert_booking_rule($slots[0] === '09:00', 'Ora e parë nuk është 09:00.');
assert_booking_rule($slots[count($slots) - 1] === '21:30', 'Ora e fundit e vlefshme e fillimit nuk është 21:30.');
assert_booking_rule(!in_array('22:00', $slots, true), 'Ora e mbylljes u lejua gabimisht si fillim termini.');
foreach (array_slice($slots, 1) as $index => $slot) {
    $previous = new DateTimeImmutable('2000-01-01 ' . $slots[$index]);
    $current = new DateTimeImmutable('2000-01-01 ' . $slot);
    assert_booking_rule($current->getTimestamp() - $previous->getTimestamp() === 1800, 'Intervali i orarit nuk është 30 minuta.');
}
$lastStart = new DateTimeImmutable('2030-01-10 21:30');
$closing = booking_closing_datetime('2030-01-10');
assert_booking_rule($lastStart->modify('+30 minutes') <= $closing, 'Termini 30-minutësh në 21:30 nuk përfundon në orar.');
assert_booking_rule($lastStart->modify('+60 minutes') > $closing, 'Termini 60-minutësh në 21:30 u lejua pas mbylljes.');

$versionedCss = asset_url('/assets/css/style.css');
assert_booking_rule(str_contains($versionedCss, '?v='), 'CSS-ja nuk ka version cache-busting.');

$databasePath = sys_get_temp_dir() . '/gentlemanbarber-rules-' . bin2hex(random_bytes(6)) . '.sqlite';
$sidecars = [$databasePath, $databasePath . '-shm', $databasePath . '-wal'];

try {
    $pdo = new PDO('sqlite:' . $databasePath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $schema = file_get_contents(dirname(__DIR__) . '/database/sqlite.sql');
    assert_booking_rule($schema !== false, 'Skema SQLite nuk u lexua.');
    $pdo->exec($schema);

    $insertBooking = $pdo->prepare(
        'INSERT INTO bookings
         (public_code, barber_id, service_id, access_token_hash, customer_name, phone, email,
          appointment_date, appointment_time, language, notes, service_name_snapshot,
          price_cents_snapshot, duration_minutes_snapshot, status)
         VALUES (?, 1, 1, ?, ?, "38970000000", "", ?, "09:00", "sq", "", "Test", 1000, 30, "accepted")'
    );
    $insertSlot = $pdo->prepare(
        'INSERT INTO appointment_slots (booking_id, barber_id, slot_start) VALUES (?, 1, ?)'
    );
    $records = [
        ['RETENTION-EXPIRED', '2030-01-02'],
        ['RETENTION-BOUNDARY', '2030-01-03'],
        ['RETENTION-RECENT', '2030-01-09'],
    ];
    foreach ($records as [$code, $date]) {
        $insertBooking->execute([
            $code,
            hash('sha256', $code),
            $code,
            $date,
        ]);
        $insertSlot->execute([(int) $pdo->lastInsertId(), $date . ' 09:00:00']);
    }

    $deleted = purge_expired_bookings($pdo, new DateTimeImmutable('2030-01-10'));
    assert_booking_rule($deleted === 1, 'Pastrimi nuk fshiu vetëm rezervimin më të vjetër se 7 ditë.');
    assert_booking_rule((int) $pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn() === 2, 'Pastrimi la numër të gabuar rezervimesh.');
    assert_booking_rule((int) $pdo->query('SELECT COUNT(*) FROM appointment_slots')->fetchColumn() === 2, 'Intervali i rezervimit të fshirë nuk u hoq me CASCADE.');
    assert_booking_rule(purge_expired_bookings($pdo, new DateTimeImmutable('2030-01-10')) === 0, 'Pastrimi nuk është idempotent.');

    echo "OK: dritarja ka 3 ditë.\n";
    echo "OK: orari 09:00–22:00 ka intervale çdo 30 minuta.\n";
    echo "OK: rezervimet më të vjetra se 7 ditë fshihen me CASCADE dhe në mënyrë idempotente.\n";
} finally {
    unset($insertBooking, $insertSlot, $pdo);
    foreach ($sidecars as $path) {
        if (is_file($path)) {
            unlink($path);
        }
    }
}
