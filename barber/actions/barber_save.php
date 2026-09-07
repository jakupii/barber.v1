<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

require_login(['admin']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/barber/dashboard.php?tab=barbers');
}
require_csrf();

$barberId = filter_input(INPUT_POST, 'barber_id', FILTER_VALIDATE_INT) ?: 0;
$name = trim((string) ($_POST['name'] ?? ''));
$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$phone = trim((string) ($_POST['phone'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$experience = max(0, min(70, (int) ($_POST['experience_years'] ?? 0)));
$displayOrder = max(0, min(999, (int) ($_POST['display_order'] ?? 0)));
$active = isset($_POST['active']) ? 1 : 0;
$translated = [];
foreach (['title_sq', 'title_mk', 'title_en', 'bio_sq', 'bio_mk', 'bio_en'] as $field) {
    $translated[$field] = trim((string) ($_POST[$field] ?? ''));
}

if (mb_strlen($name) < 2 || mb_strlen($name) > 120
    || !preg_match('/^[A-Za-z0-9._-]{3,40}$/', $username)
    || array_filter([$translated['title_sq'], $translated['title_mk'], $translated['title_en']], static fn (string $value): bool => mb_strlen($value) < 2) !== []) {
    flash('error', 'Kontrollo emrin, username-in dhe titujt në 3 gjuhë.');
    redirect('/barber/dashboard.php?tab=barbers');
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('error', 'Email-i nuk është i vlefshëm.');
    redirect('/barber/dashboard.php?tab=barbers');
}
if (($barberId === 0 && mb_strlen($password) < 10) || ($password !== '' && mb_strlen($password) < 10)) {
    flash('error', 'Fjalëkalimi duhet të ketë së paku 10 karaktere.');
    redirect('/barber/dashboard.php?tab=barbers');
}

$pdo = db();
$existing = null;
if ($barberId > 0) {
    $statement = $pdo->prepare("SELECT b.*, u.id AS user_id FROM barbers b LEFT JOIN users u ON u.barber_id = b.id AND u.role = 'barber' WHERE b.id = ?");
    $statement->execute([$barberId]);
    $existing = $statement->fetch();
    if (!$existing) {
        flash('error', 'Berberi nuk u gjet.');
        redirect('/barber/dashboard.php?tab=barbers');
    }
}

$imagePath = (string) ($existing['image_path'] ?? '');
$previousImagePath = $imagePath;
$uploadedFile = null;
$photo = $_FILES['photo'] ?? null;
if (is_array($photo) && ($photo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    if (($photo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || (int) $photo['size'] > (int) config('uploads.max_bytes')) {
        flash('error', 'Fotografia nuk u ngarkua ose tejkalon 3 MB.');
        redirect('/barber/dashboard.php?tab=barbers');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $photo['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $size = @getimagesize((string) $photo['tmp_name']);
    if (!isset($extensions[$mime]) || !$size || $size[0] > 6000 || $size[1] > 6000) {
        flash('error', 'Lejohen vetëm fotografi JPG, PNG ose WebP të vlefshme.');
        redirect('/barber/dashboard.php?tab=barbers');
    }
    $uploadDirectory = (string) config('uploads.directory');
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
        flash('error', 'Dosja e fotografive nuk është e shkrueshme.');
        redirect('/barber/dashboard.php?tab=barbers');
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $uploadedFile = $uploadDirectory . '/' . $filename;
    if (!move_uploaded_file((string) $photo['tmp_name'], $uploadedFile)) {
        flash('error', 'Fotografia nuk u ruajt.');
        redirect('/barber/dashboard.php?tab=barbers');
    }
    $imagePath = rtrim((string) config('uploads.public_prefix'), '/') . '/' . $filename;
}
if ($barberId === 0 && $imagePath === '') {
    flash('error', 'Zgjidh një fotografi për berberin e ri.');
    redirect('/barber/dashboard.php?tab=barbers');
}

$saveSucceeded = false;
try {
    $pdo->beginTransaction();
    if ($barberId > 0) {
        $statement = $pdo->prepare(
            'UPDATE barbers SET name = ?, title_sq = ?, title_mk = ?, title_en = ?, bio_sq = ?, bio_mk = ?, bio_en = ?, phone = ?, email = ?, experience_years = ?, image_path = ?, active = ?, display_order = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?'
        );
        $statement->execute([$name, $translated['title_sq'], $translated['title_mk'], $translated['title_en'], $translated['bio_sq'], $translated['bio_mk'], $translated['bio_en'], $phone, $email, $experience, $imagePath, $active, $displayOrder, $barberId]);

        if (!empty($existing['user_id'])) {
            if ($password !== '') {
                $userUpdate = $pdo->prepare('UPDATE users SET username = ?, password_hash = ?, full_name = ?, active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
                $userUpdate->execute([$username, password_hash($password, PASSWORD_DEFAULT), $name, $active, $existing['user_id']]);
            } else {
                $userUpdate = $pdo->prepare('UPDATE users SET username = ?, full_name = ?, active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
                $userUpdate->execute([$username, $name, $active, $existing['user_id']]);
            }
        } else {
            if ($password === '') {
                throw new RuntimeException('Vendos një fjalëkalim për të krijuar llogarinë e berberit.');
            }
            $userInsert = $pdo->prepare("INSERT INTO users (barber_id, role, username, password_hash, full_name, active, created_at, updated_at) VALUES (?, 'barber', ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
            $userInsert->execute([$barberId, $username, password_hash($password, PASSWORD_DEFAULT), $name, $active]);
        }
    } else {
        $statement = $pdo->prepare(
            'INSERT INTO barbers (name, title_sq, title_mk, title_en, bio_sq, bio_mk, bio_en, phone, email, experience_years, image_path, active, display_order, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $statement->execute([$name, $translated['title_sq'], $translated['title_mk'], $translated['title_en'], $translated['bio_sq'], $translated['bio_mk'], $translated['bio_en'], $phone, $email, $experience, $imagePath, $active, $displayOrder]);
        $barberId = (int) $pdo->lastInsertId();
        $userInsert = $pdo->prepare("INSERT INTO users (barber_id, role, username, password_hash, full_name, active, created_at, updated_at) VALUES (?, 'barber', ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $userInsert->execute([$barberId, $username, password_hash($password, PASSWORD_DEFAULT), $name, $active]);
    }
    $pdo->commit();
    $saveSucceeded = true;
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($uploadedFile && is_file($uploadedFile)) {
        @unlink($uploadedFile);
    }
    $message = in_array((string) $exception->getCode(), ['19', '23000'], true)
        ? 'Ky username përdoret tashmë. Zgjidh një tjetër.'
        : ($exception instanceof RuntimeException ? $exception->getMessage() : 'Të dhënat nuk u ruajtën.');
    flash('error', $message);
}

if ($saveSucceeded) {
    // A newly uploaded photo is already referenced by the committed row. Only
    // now remove the previous file, and only when it resolves to a direct child
    // of the managed upload directory. Bundled /assets images never match the
    // configured public upload prefix and are therefore left untouched.
    if ($uploadedFile !== null && $previousImagePath !== '' && $previousImagePath !== $imagePath) {
        $uploadDirectory = rtrim((string) config('uploads.directory'), DIRECTORY_SEPARATOR);
        $publicPrefix = rtrim((string) config('uploads.public_prefix'), '/');
        $managedPrefix = $publicPrefix . '/';

        if ($publicPrefix !== '' && str_starts_with($previousImagePath, $managedPrefix)) {
            $relativePath = substr($previousImagePath, strlen($managedPrefix));
            $uploadDirectoryReal = realpath($uploadDirectory);
            $previousFileReal = $relativePath !== '' && basename($relativePath) === $relativePath
                ? realpath($uploadDirectory . DIRECTORY_SEPARATOR . $relativePath)
                : false;

            if ($uploadDirectoryReal !== false
                && $previousFileReal !== false
                && dirname($previousFileReal) === $uploadDirectoryReal
                && is_file($previousFileReal)) {
                @unlink($previousFileReal);
            }
        }
    }

    flash('success', $existing ? 'Profili i berberit u përditësua.' : 'Berberi dhe llogaria e tij u shtuan.');
}

redirect('/barber/dashboard.php?tab=barbers');
