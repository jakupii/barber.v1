<?php

declare(strict_types=1);

/**
 * Vendos fjalëkalimin e adminit në një databazë ekzistuese.
 *
 * Migrimi te rotate_legacy_seed_passwords() e ndryshon fjalëkalimin vetëm
 * nëse ai nuk është prekur kurrë. Nëse admini e ka ndryshuar tashmë vetë,
 * hash-i nuk përputhet dhe migrimi — me qëllim — nuk e prek. Ky skript e
 * vendos pa kushte.
 *
 * Përdorimi:   php database/set_admin_password.php 'fjalekalimi-i-ri'
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found');
}

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/database.php';

$password = (string) ($argv[1] ?? '');
if ($password === '') {
    fwrite(STDERR, "Përdorimi: php database/set_admin_password.php 'fjalekalimi-i-ri'\n");
    exit(1);
}

if (mb_strlen($password) < 10) {
    // Ndryshimi nga faqja kërkon së paku 10 karaktere; këtu vetëm paralajmërojmë,
    // që një fjalëkalim i vendosur me qëllim të mos bllokohet.
    fwrite(STDERR, "Kujdes: fjalëkalimi ka nën 10 karaktere — më i shkurtër se sa kërkon vetë faqja.\n");
}

$statement = db()->prepare(
    "UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP
     WHERE username = 'admin' AND role = 'admin'"
);
$statement->execute([password_hash($password, PASSWORD_DEFAULT)]);

if ($statement->rowCount() === 0) {
    fwrite(STDERR, "Llogaria 'admin' nuk u gjet ose fjalëkalimi ishte i njëjti.\n");
    exit(1);
}

echo "Fjalëkalimi i adminit u vendos.\n";
