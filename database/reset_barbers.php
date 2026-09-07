<?php

declare(strict_types=1);

/**
 * Fshin të gjithë berberët nga një databazë ekzistuese.
 *
 * bookings.barber_id dhe appointment_slots.barber_id janë NOT NULL me
 * ON DELETE RESTRICT, prandaj berberët nuk fshihen dot pa u fshirë më parë
 * rezervimet e tyre. Ky skript e bën atë brenda një transaksioni të vetëm:
 * ose kryhet i gjithë pastrimi, ose nuk ndryshon asgjë.
 *
 * Përdorimi:   php database/reset_barbers.php --yes
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found');
}

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/database.php';

$pdo = db();
$count = static fn (string $table): int => (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();

$barbers = $count('barbers');
$bookings = $count('bookings');
$barberUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'barber'")->fetchColumn();

echo "Databaza përmban:\n";
echo "  berberë:            {$barbers}\n";
echo "  llogari berberësh:  {$barberUsers}\n";
echo "  rezervime:          {$bookings}\n\n";

if ($barbers === 0) {
    echo "Nuk ka berberë për të fshirë.\n";
    exit(0);
}

if (!in_array('--yes', $argv, true)) {
    echo "Ky veprim fshin PËRGJITHMONË të gjithë berberët, llogaritë e tyre\n";
    echo "dhe TË GJITHA rezervimet ({$bookings}), sepse çdo rezervim i përket\n";
    echo "një berberi. Nuk ka kthim prapa — merr një kopje të databazës së pari.\n\n";
    echo "Për ta kryer: php database/reset_barbers.php --yes\n";
    exit(1);
}

$pdo->beginTransaction();
try {
    // Radha ndjek varësitë: blocked_slots.created_by -> users me RESTRICT,
    // ndaj ato duhen hequr para llogarive të berberëve.
    $pdo->exec('DELETE FROM blocked_slots');
    $pdo->exec('DELETE FROM bookings');
    $pdo->exec('DELETE FROM appointment_slots');
    $pdo->exec("DELETE FROM users WHERE role = 'barber'");
    $pdo->exec('DELETE FROM barbers');
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Pastrimi dështoi, asgjë nuk ndryshoi: ' . $exception->getMessage() . "\n");
    exit(1);
}

echo "U fshinë {$barbers} berberë, {$barberUsers} llogari dhe {$bookings} rezervime.\n";
echo "Shto berberët e rinj nga: Dashboard -> Berberët.\n";
