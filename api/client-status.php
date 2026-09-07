<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Cache-Control: no-store, private, max-age=0');
header('Pragma: no-cache');
header('Vary: Cookie');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    json_response(['ok' => false, 'message' => 'Metoda nuk lejohet.'], 405);
}

$phone = normalize_phone((string) ($_SESSION['client_lookup_phone'] ?? ''));
$verifiedAt = (int) ($_SESSION['client_lookup_verified_at'] ?? 0);
if (!valid_phone($phone) || $verifiedAt < time() - 3600) {
    unset(
        $_SESSION['client_lookup_phone'],
        $_SESSION['client_lookup_verified_at']
    );
    json_response(['ok' => false, 'message' => 'Kontrollo numrin e telefonit për të hapur rezervimet e tua.'], 401);
}

[$phoneCondition, $phoneValues] = phone_lookup_condition('bk.phone', $phone);
$statement = db()->prepare(
    "SELECT bk.id, bk.status, bk.appointment_date, bk.appointment_time
     FROM bookings bk
     WHERE {$phoneCondition} AND bk.appointment_date >= ?"
);
$statement->execute([...$phoneValues, booking_history_cutoff()]);
$rows = sort_client_bookings($statement->fetchAll());

$bookings = array_map(static fn (array $booking): array => [
    'id' => (int) $booking['id'],
    'status' => (string) $booking['status'],
    'status_class' => status_class((string) $booking['status']),
], $rows);

json_response([
    'ok' => true,
    'bookings' => $bookings,
]);
