<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

$user = require_login(['admin', 'barber']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/barber/dashboard.php?tab=bookings');
}
require_csrf();

$bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
$newStatus = (string) ($_POST['status'] ?? '');
$returnTab = in_array($_POST['return_tab'] ?? '', ['overview', 'bookings'], true) ? (string) $_POST['return_tab'] : 'bookings';
if (!$bookingId || !in_array($newStatus, ['accepted', 'rejected', 'cancelled'], true)) {
    flash('error', 'Kërkesa nuk është e vlefshme.');
    redirect('/barber/dashboard.php?tab=' . $returnTab);
}

$pdo = db();
$driver = (string) config('database.driver', 'sqlite');
try {
    db_begin($pdo, $driver);

    $sql = 'SELECT * FROM bookings WHERE id = ?';
    if ($driver === 'mysql') {
        $sql .= ' FOR UPDATE';
    }
    $statement = $pdo->prepare($sql);
    $statement->execute([$bookingId]);
    $booking = $statement->fetch();

    if (!$booking || ($user['role'] === 'barber' && (int) $booking['barber_id'] !== (int) $user['barber_id'])) {
        throw new RuntimeException('Rezervimi nuk u gjet ose nuk ju përket.');
    }

    $allowed = ($booking['status'] === 'pending' && in_array($newStatus, ['accepted', 'rejected'], true))
        || ($user['role'] === 'admin' && $booking['status'] === 'accepted' && $newStatus === 'cancelled');
    if (!$allowed) {
        throw new RuntimeException('Statusi i këtij rezervimi është ndryshuar tashmë.');
    }

    $update = $pdo->prepare('UPDATE bookings SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND status = ?');
    $update->execute([$newStatus, $bookingId, $booking['status']]);
    if ($update->rowCount() !== 1) {
        throw new RuntimeException('Statusi u ndryshua nga një përdorues tjetër. Rifresko faqen.');
    }

    if (in_array($newStatus, ['rejected', 'cancelled'], true)) {
        $delete = $pdo->prepare('DELETE FROM appointment_slots WHERE booking_id = ?');
        $delete->execute([$bookingId]);
    }

    db_commit($pdo, $driver);
    flash('success', $newStatus === 'accepted' ? 'Rezervimi u pranua.' : ($newStatus === 'rejected' ? 'Rezervimi u refuzua dhe ora u lirua.' : 'Rezervimi u anulua dhe ora u lirua.'));
} catch (Throwable $exception) {
    db_rollback($pdo, $driver);
    flash('error', $exception instanceof RuntimeException ? $exception->getMessage() : 'Statusi nuk u ndryshua. Provo përsëri.');
}

redirect('/barber/dashboard.php?tab=' . $returnTab);
