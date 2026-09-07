<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

require_login(['admin']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/barber/dashboard.php?tab=barbers');
}
require_csrf();

$barberId = filter_input(INPUT_POST, 'barber_id', FILTER_VALIDATE_INT);
if (!$barberId) {
    flash('error', 'Berberi nuk u gjet.');
    redirect('/barber/dashboard.php?tab=barbers');
}

$statement = db()->prepare('SELECT active, name FROM barbers WHERE id = ?');
$statement->execute([$barberId]);
$barber = $statement->fetch();
if (!$barber) {
    flash('error', 'Berberi nuk u gjet.');
    redirect('/barber/dashboard.php?tab=barbers');
}

$active = $barber['active'] ? 0 : 1;
$pdo = db();
try {
    $pdo->beginTransaction();
    $update = $pdo->prepare('UPDATE barbers SET active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    $update->execute([$active, $barberId]);
    $userUpdate = $pdo->prepare("UPDATE users SET active = ?, updated_at = CURRENT_TIMESTAMP WHERE barber_id = ? AND role = 'barber'");
    $userUpdate->execute([$active, $barberId]);
    $pdo->commit();
    flash('success', $active ? 'Berberi u rikthye në faqe dhe mund të marrë rezervime.' : 'Berberi u fsheh nga faqja. Rezervimet historike u ruajtën.');
} catch (Throwable) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'Statusi i berberit nuk u ndryshua.');
}

redirect('/barber/dashboard.php?tab=barbers');
