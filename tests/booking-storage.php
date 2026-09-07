<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';

const TEST_BOOKING_COUNT = 10000;

function assert_test(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$databasePath = sys_get_temp_dir() . '/gentlemanbarber-test-' . bin2hex(random_bytes(6)) . '.sqlite';
$sidecars = [$databasePath, $databasePath . '-shm', $databasePath . '-wal'];

try {
    $pdo = new PDO('sqlite:' . $databasePath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $schema = file_get_contents(dirname(__DIR__) . '/database/sqlite.sql');
    assert_test($schema !== false, 'Skema SQLite nuk u lexua.');
    $pdo->exec($schema);

    $formats = [
        '0038970880444',
        '+38970880444',
        '070880444',
        '70880444',
    ];
    $canonical = array_map('normalize_phone', $formats);
    assert_test(count(array_unique($canonical)) === 1, 'Formatet e telefonit nuk japin të njëjtin numër.');
    assert_test($canonical[0] === '38970880444', 'Formati kanonik nuk është ai i pritur.');

    $insertBooking = $pdo->prepare(
        'INSERT INTO bookings
         (public_code, barber_id, service_id, access_token_hash, customer_name, phone, email,
          appointment_date, appointment_time, language, notes, service_name_snapshot,
          price_cents_snapshot, duration_minutes_snapshot, status)
         VALUES (?, 1, 1, ?, ?, ?, "", ?, ?, "sq", "", "Test", 1000, 30, "pending")'
    );
    $insertSlot = $pdo->prepare(
        'INSERT INTO appointment_slots (booking_id, barber_id, slot_start) VALUES (?, 1, ?)'
    );

    $start = new DateTimeImmutable('2000-01-01 00:00:00');
    $pdo->beginTransaction();
    for ($index = 0; $index < TEST_BOOKING_COUNT; $index++) {
        $slot = $start->modify('+' . ($index * 30) . ' minutes');
        $phone = $index === 0 ? '070880444' : '38970000000';
        $insertBooking->execute([
            'TEST-' . str_pad((string) $index, 8, '0', STR_PAD_LEFT),
            hash('sha256', 'token-' . $index),
            'Storage Test ' . $index,
            $phone,
            $slot->format('Y-m-d'),
            $slot->format('H:i'),
        ]);
        $insertSlot->execute([
            (int) $pdo->lastInsertId(),
            $slot->format('Y-m-d H:i:s'),
        ]);
    }
    $pdo->commit();

    $legacyVariants = phone_lookup_variants('+38970880444');
    $placeholders = implode(', ', array_fill(0, count($legacyVariants), '?'));
    $legacyLookup = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE phone IN ({$placeholders})");
    $legacyLookup->execute($legacyVariants);
    assert_test((int) $legacyLookup->fetchColumn() === 1, 'Kërkimi nuk gjeti numrin e ruajtur në formatin e vjetër.');

    $conflictDetected = false;
    try {
        $pdo->beginTransaction();
        $insertBooking->execute([
            'TEST-CONFLICT',
            hash('sha256', 'conflict'),
            'Conflict Test',
            '38970111111',
            '2000-01-01',
            '00:00',
        ]);
        $insertSlot->execute([(int) $pdo->lastInsertId(), '2000-01-01 00:00:00']);
        $pdo->commit();
    } catch (PDOException) {
        $conflictDetected = true;
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
    assert_test($conflictDetected, 'Databaza lejoi dy rezervime për të njëjtin berber dhe orar.');

    unset($insertBooking, $insertSlot, $legacyLookup, $pdo);

    $reopened = new PDO('sqlite:' . $databasePath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $bookingCount = (int) $reopened->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
    $slotCount = (int) $reopened->query('SELECT COUNT(*) FROM appointment_slots')->fetchColumn();
    $integrity = (string) $reopened->query('PRAGMA integrity_check')->fetchColumn();

    assert_test($bookingCount === TEST_BOOKING_COUNT, 'Numri i rezervimeve ndryshoi pas rihapjes.');
    assert_test($slotCount === TEST_BOOKING_COUNT, 'Numri i intervaleve ndryshoi pas rihapjes.');
    assert_test($integrity === 'ok', 'SQLite raportoi problem integriteti.');

    echo "OK: {$bookingCount} rezervime dhe {$slotCount} intervale u ruajtën dhe u rilexuan.\n";
    echo "OK: formatet e telefonit japin {$canonical[0]}.\n";
    echo "OK: konflikti i të njëjtit orar u bllokua.\n";
    echo "OK: PRAGMA integrity_check = {$integrity}.\n";
} finally {
    unset($reopened, $pdo);
    foreach ($sidecars as $path) {
        if (is_file($path)) {
            unlink($path);
        }
    }
}
