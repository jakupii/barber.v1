<?php

declare(strict_types=1);

function login_rate_identity(string $username): string
{
    return request_ip() . '|' . mb_strtolower(trim($username), 'UTF-8');
}

function login_rate_limited(string $username = ''): bool
{
    $now = time();
    $attempts = array_values(array_filter(
        $_SESSION['login_attempts'] ?? [],
        static fn (int $time): bool => $time > $now - 600
    ));
    $_SESSION['login_attempts'] = $attempts;
    return count($attempts) >= 7
        || rate_limit_exceeded('staff_login', login_rate_identity($username), 7, 600)
        || rate_limit_exceeded('staff_login_ip', request_ip(), 25, 600);
}

function record_login_failure(string $username = ''): void
{
    $_SESSION['login_attempts'][] = time();
    record_rate_limit_event('staff_login', login_rate_identity($username));
    record_rate_limit_event('staff_login_ip', request_ip());
}

function attempt_login(string $username, string $password): bool
{
    $username = trim($username);
    if (login_rate_limited($username)) {
        return false;
    }

    $statement = db()->prepare('SELECT * FROM users WHERE username = ? AND active = 1 LIMIT 1');
    $statement->execute([$username]);
    $user = $statement->fetch();

    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
        record_login_failure($username);
        usleep(250000);
        return false;
    }

    if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $update = db()->prepare('UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $update->execute([$hash, $user['id']]);
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['last_activity'] = time();
    $_SESSION['login_attempts'] = [];
    clear_rate_limit_events('staff_login', login_rate_identity($username));
    return true;
}

function current_user(): ?array
{
    static $loaded = false;
    static $user;

    if ($loaded) {
        return $user;
    }
    $loaded = true;

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    if (isset($_SESSION['last_activity']) && (int) $_SESSION['last_activity'] < time() - 7200) {
        logout_user();
        return null;
    }

    $statement = db()->prepare(
        'SELECT u.*, b.name AS barber_name, b.image_path AS barber_image
         FROM users u LEFT JOIN barbers b ON b.id = u.barber_id
         WHERE u.id = ? AND u.active = 1 LIMIT 1'
    );
    $statement->execute([(int) $_SESSION['user_id']]);
    $record = $statement->fetch();
    if (!$record) {
        logout_user();
        return null;
    }

    $_SESSION['last_activity'] = time();
    $user = $record;
    return $user;
}

function require_login(array $roles = []): array
{
    $user = current_user();
    if (!$user) {
        redirect('/barber/');
    }
    if ($roles !== [] && !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('Nuk keni leje për këtë veprim.');
    }
    return $user;
}

function logout_user(): void
{
    unset($_SESSION['user_id'], $_SESSION['last_activity']);
    session_regenerate_id(true);
}
