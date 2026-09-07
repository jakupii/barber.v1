<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

$user = require_login(['admin', 'barber']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/barber/dashboard.php?tab=account');
}
require_csrf();

$current = (string) ($_POST['current_password'] ?? '');
$new = (string) ($_POST['new_password'] ?? '');
$confirm = (string) ($_POST['confirm_password'] ?? '');

if (!password_verify($current, (string) $user['password_hash'])) {
    flash('error', 'Fjalëkalimi aktual nuk është i saktë.');
} elseif (mb_strlen($new) < 10) {
    flash('error', 'Fjalëkalimi i ri duhet të ketë së paku 10 karaktere.');
} elseif (!hash_equals($new, $confirm)) {
    flash('error', 'Fjalëkalimet e reja nuk përputhen.');
} else {
    $statement = db()->prepare('UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    $statement->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
    session_regenerate_id(true);
    flash('success', 'Fjalëkalimi u ndryshua me sukses.');
}

redirect('/barber/dashboard.php?tab=account');
