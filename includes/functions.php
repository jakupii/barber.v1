<?php

declare(strict_types=1);

function config(?string $key = null, mixed $default = null): mixed
{
    static $configuration;
    if ($configuration === null) {
        $configuration = require dirname(__DIR__) . '/config/app.php';
    }

    if ($key === null) {
        return $configuration;
    }

    $value = $configuration;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

function url(string $path = ''): string
{
    $base = '/' . trim((string) config('base_path', ''), '/');
    $base = $base === '/' ? '' : $base;
    $path = '/' . ltrim($path, '/');
    return $base . ($path === '/' ? '/' : $path);
}

/**
 * Return a public asset URL with a deployment-aware cache version.
 *
 * Using the file modification time and size means browsers/CDNs request the
 * new file after a Hostinger deploy, while unchanged assets remain cacheable.
 */
function asset_url(string $path): string
{
    static $resolved = [];
    if (isset($resolved[$path])) {
        return $resolved[$path];
    }

    $publicUrl = url($path);
    $urlPath = parse_url($path, PHP_URL_PATH);
    if (!is_string($urlPath) || $urlPath === '' || str_contains($urlPath, "\0")) {
        return $resolved[$path] = $publicUrl;
    }

    $relativePath = ltrim(str_replace('\\', '/', $urlPath), '/');
    if ($relativePath === '' || in_array('..', explode('/', $relativePath), true)) {
        return $resolved[$path] = $publicUrl;
    }

    $filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $stat = is_file($filePath) ? @stat($filePath) : false;
    if ($stat === false) {
        return $resolved[$path] = $publicUrl;
    }

    $version = (string) $stat['mtime'] . '-' . $stat['size'];
    $separator = str_contains($publicUrl, '?') ? '&' : '?';

    return $resolved[$path] = $publicUrl . $separator . 'v=' . rawurlencode($version);
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function request_data(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($contentType, 'application/json')) {
        $decoded = json_decode((string) file_get_contents('php://input'), true);
        return is_array($decoded) ? $decoded : [];
    }
    return $_POST;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf_token'];
}

function verify_csrf(?string $token = null): bool
{
    $token ??= (string) (request_data()['csrf_token'] ?? '');
    return isset($_SESSION['csrf_token']) && hash_equals((string) $_SESSION['csrf_token'], $token);
}

function require_csrf(bool $json = false): void
{
    if (verify_csrf()) {
        return;
    }
    if ($json) {
        json_response(['ok' => false, 'message' => 'Sesioni ka skaduar. Rifresko faqen dhe provo përsëri.'], 419);
    }
    http_response_code(419);
    exit('Sesioni ka skaduar. Kthehu prapa dhe rifresko faqen.');
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function pull_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function booking_dates(): array
{
    // Memoised per request: the window is derived from "today", which cannot
    // change while a single request is being served.
    static $dates;
    if ($dates !== null) {
        return $dates;
    }

    // The public and staff-facing booking window may never exceed three days.
    $days = min(3, max(1, (int) config('booking.days_ahead', 3)));
    $today = new DateTimeImmutable('today');
    $dates = [];
    for ($i = 0; $i < $days; $i++) {
        $dates[] = $today->modify('+' . $i . ' days')->format('Y-m-d');
    }
    return $dates;
}

function booking_history_cutoff(?DateTimeImmutable $today = null): string
{
    $retentionDays = max(1, (int) config('booking.retention_days', 7));
    $today ??= new DateTimeImmutable('today');
    return $today->modify('-' . $retentionDays . ' days')->format('Y-m-d');
}

function is_valid_booking_date(string $date): bool
{
    return in_array($date, booking_dates(), true);
}

function booking_slot_interval_minutes(): int
{
    static $interval;
    return $interval ??= max(1, min(720, (int) config('booking.slot_interval_minutes', 30)));
}

function booking_slots(): array
{
    static $slots;
    if ($slots !== null) {
        return $slots;
    }

    $opening = trim((string) config('booking.opening_time', '09:00'));
    $closing = trim((string) config('booking.closing_time', '22:00'));
    $start = DateTimeImmutable::createFromFormat('!H:i', $opening);
    $end = DateTimeImmutable::createFromFormat('!H:i', $closing);

    if (
        !$start
        || !$end
        || $start->format('H:i') !== $opening
        || $end->format('H:i') !== $closing
        || $start >= $end
    ) {
        $start = DateTimeImmutable::createFromFormat('!H:i', '09:00');
        $end = DateTimeImmutable::createFromFormat('!H:i', '22:00');
    }

    $slots = [];
    $interval = booking_slot_interval_minutes();
    for ($slot = $start; $slot < $end; $slot = $slot->modify('+' . $interval . ' minutes')) {
        $slots[] = $slot->format('H:i');
    }
    return $slots;
}

/**
 * Look up the configured closing time once instead of re-parsing config for
 * every slot that is checked against it.
 */
function booking_closing_time(): string
{
    static $closing;
    if ($closing !== null) {
        return $closing;
    }

    $closing = trim((string) config('booking.closing_time', '22:00'));
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $closing)) {
        $closing = '22:00';
    }
    return $closing;
}

function is_valid_slot(string $time): bool
{
    return in_array($time, booking_slots(), true);
}

function booking_closing_datetime(string $date): DateTimeImmutable
{
    return new DateTimeImmutable($date . ' ' . booking_closing_time());
}

function normalize_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', trim($phone)) ?? '';
    if ($digits === '') {
        return '';
    }

    // Store North Macedonian numbers in one canonical format: 389 + national
    // number without the local trunk prefix. This makes 00389, +389, 0xx and
    // bare national mobile formats resolve to the same booking.
    if (str_starts_with($digits, '00')) {
        $digits = substr($digits, 2);
    }
    if (str_starts_with($digits, '3890')) {
        $digits = '389' . substr($digits, 4);
    } elseif (str_starts_with($digits, '0')) {
        $digits = '389' . substr($digits, 1);
    } elseif (strlen($digits) === 8) {
        $digits = '389' . $digits;
    }

    return $digits;
}

function phone_lookup_variants(string $phone): array
{
    $canonical = normalize_phone($phone);
    if ($canonical === '') {
        return [];
    }

    $variants = [$canonical];
    if (str_starts_with($canonical, '389') && strlen($canonical) > 3) {
        $national = substr($canonical, 3);
        $variants[] = '0' . $national;
        $variants[] = $national;
        $variants[] = '00' . $canonical;
    }

    return array_values(array_unique($variants));
}

/**
 * Build the "phone IN (...)" fragment plus its bound values, so that the
 * public lookup and the JSON status endpoint cannot drift apart. An unusable
 * phone yields a condition that matches nothing instead of invalid SQL.
 *
 * @return array{0: string, 1: list<string>}
 */
function phone_lookup_condition(string $column, string $phone): array
{
    $variants = phone_lookup_variants($phone);
    if ($variants === []) {
        return ['1 = 0', []];
    }

    return [
        $column . ' IN (' . implode(', ', array_fill(0, count($variants), '?')) . ')',
        $variants,
    ];
}

/**
 * Upcoming appointments first (soonest first), then recent ones (newest
 * first). Shared by the client page and the status endpoint so both render
 * the same order.
 */
function sort_client_bookings(array $bookings): array
{
    $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
    usort($bookings, static function (array $first, array $second) use ($now): int {
        $firstDate = $first['appointment_date'] . ' ' . $first['appointment_time'];
        $secondDate = $second['appointment_date'] . ' ' . $second['appointment_time'];
        $firstUpcoming = strcmp($firstDate, $now) >= 0;
        $secondUpcoming = strcmp($secondDate, $now) >= 0;

        if ($firstUpcoming !== $secondUpcoming) {
            return $firstUpcoming ? -1 : 1;
        }

        $comparison = $firstUpcoming
            ? strcmp($firstDate, $secondDate)
            : strcmp($secondDate, $firstDate);

        return $comparison !== 0
            ? $comparison
            : (int) $first['id'] <=> (int) $second['id'];
    });

    return $bookings;
}

function valid_phone(string $phone): bool
{
    $digits = normalize_phone($phone);
    return strlen($digits) >= 7 && strlen($digits) <= 15;
}

function request_ip(): string
{
    $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
}

function rate_limit_identity_hash(string $identity): string
{
    return hash('sha256', mb_strtolower(trim($identity), 'UTF-8'));
}

function rate_limit_exceeded(
    string $action,
    string $identity,
    int $maximumAttempts,
    int $windowSeconds
): bool {
    $maximumAttempts = max(1, $maximumAttempts);
    $windowSeconds = max(1, $windowSeconds);
    $statement = db()->prepare(
        'SELECT COUNT(*) FROM rate_limit_events
         WHERE action = ? AND identity_hash = ? AND created_at_epoch >= ?'
    );
    $statement->execute([
        substr($action, 0, 64),
        rate_limit_identity_hash($identity),
        time() - $windowSeconds,
    ]);
    return (int) $statement->fetchColumn() >= $maximumAttempts;
}

function record_rate_limit_event(string $action, string $identity): void
{
    $pdo = db();
    $statement = $pdo->prepare(
        'INSERT INTO rate_limit_events (action, identity_hash, created_at_epoch) VALUES (?, ?, ?)'
    );
    $statement->execute([
        substr($action, 0, 64),
        rate_limit_identity_hash($identity),
        time(),
    ]);

    // Opportunistic cleanup keeps this small without adding a scheduled task.
    if (random_int(1, 50) === 1) {
        $cleanup = $pdo->prepare('DELETE FROM rate_limit_events WHERE created_at_epoch < ?');
        $cleanup->execute([time() - 86400]);
    }
}

function clear_rate_limit_events(string $action, string $identity): void
{
    $statement = db()->prepare('DELETE FROM rate_limit_events WHERE action = ? AND identity_hash = ?');
    $statement->execute([
        substr($action, 0, 64),
        rate_limit_identity_hash($identity),
    ]);
}

function new_booking_code(PDO $pdo): string
{
    do {
        $code = 'GB-' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 8));
        $statement = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE public_code = ?');
        $statement->execute([$code]);
    } while ((int) $statement->fetchColumn() > 0);
    return $code;
}

function format_price(int $cents): string
{
    $amount = $cents / 100;
    return number_format($amount, $cents % 100 === 0 ? 0 : 2, ',', '.') . ' €';
}

function status_label(string $status): string
{
    return match ($status) {
        'accepted' => 'E pranuar',
        'rejected' => 'E refuzuar',
        'cancelled' => 'E anuluar',
        default => 'Në pritje',
    };
}

function status_class(string $status): string
{
    return match ($status) {
        'accepted' => 'status-accepted',
        'rejected', 'cancelled' => 'status-rejected',
        default => 'status-pending',
    };
}

function localized_value(array $row, string $field, string $language): string
{
    $language = in_array($language, ['sq', 'mk', 'en'], true) ? $language : 'sq';
    return (string) ($row[$field . '_' . $language] ?? $row[$field . '_sq'] ?? '');
}
