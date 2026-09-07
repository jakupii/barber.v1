<?php

declare(strict_types=1);

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $driver = (string) config('database.driver', 'sqlite');
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    if ($driver === 'mysql') {
        $host = (string) config('database.host');
        $port = (int) config('database.port', 3306);
        $name = (string) config('database.name');
        $charset = (string) config('database.charset', 'utf8mb4');
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
        $pdo = new PDO($dsn, (string) config('database.username'), (string) config('database.password'), $options);
    } else {
        $path = (string) config('database.sqlite_path');
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Nuk u krijua dosja e databazës.');
        }
        $pdo = new PDO('sqlite:' . $path, null, null, $options);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 5000');
        $pdo->exec('PRAGMA journal_mode = WAL');
    }

    initialize_database($pdo, $driver);
    return $pdo;
}

/**
 * Bumped whenever install/migration work below has to run again on an
 * already-deployed database.
 */
const SCHEMA_VERSION = 2;

function initialize_database(PDO $pdo, string $driver): void
{
    // Installing the schema and replaying legacy migrations used to run on
    // every single request (~10 queries, including four password UPDATEs).
    // A stored version marker reduces the steady state to one cheap read.
    if (schema_version($pdo, $driver) < SCHEMA_VERSION) {
        install_database_schema($pdo, $driver);
        ensure_database_compatibility($pdo, $driver);
        rotate_legacy_seed_passwords($pdo);
        write_schema_version($pdo, $driver, SCHEMA_VERSION);
    }

    try {
        purge_expired_bookings($pdo);
    } catch (Throwable $exception) {
        // Maintenance must never make the booking site unavailable. The next
        // request retries the idempotent cleanup automatically.
        error_log('Booking cleanup failed: ' . $exception->getMessage());
    }
}

/**
 * SQLite keeps the marker in the database header, which costs no table read.
 * MySQL uses a one-row table; its absence simply means "not migrated yet".
 */
function schema_version(PDO $pdo, string $driver): int
{
    try {
        if ($driver === 'mysql') {
            $statement = $pdo->query("SELECT version FROM schema_state WHERE id = 1");
            return $statement ? (int) $statement->fetchColumn() : 0;
        }
        return (int) $pdo->query('PRAGMA user_version')->fetchColumn();
    } catch (Throwable) {
        return 0;
    }
}

function write_schema_version(PDO $pdo, string $driver, int $version): void
{
    if ($driver === 'mysql') {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_state (
                id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
                version INT UNSIGNED NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $statement = $pdo->prepare(
            'INSERT INTO schema_state (id, version) VALUES (1, ?)
             ON DUPLICATE KEY UPDATE version = VALUES(version)'
        );
        $statement->execute([$version]);
        return;
    }

    // PRAGMA does not accept bound parameters; the value is an int constant.
    $pdo->exec('PRAGMA user_version = ' . $version);
}

function install_database_schema(PDO $pdo, string $driver): void
{
    $requiredTables = [
        'barbers',
        'users',
        'services',
        'bookings',
        'appointment_slots',
        'blocked_slots',
        'rate_limit_events',
    ];

    $placeholders = implode(', ', array_fill(0, count($requiredTables), '?'));
    $statement = $pdo->prepare(
        $driver === 'mysql'
            ? "SELECT table_name FROM information_schema.tables
               WHERE table_schema = DATABASE() AND table_name IN ({$placeholders})"
            : "SELECT name FROM sqlite_master WHERE type = 'table' AND name IN ({$placeholders})"
    );
    $statement->execute($requiredTables);
    $existingTables = array_map('strtolower', $statement->fetchAll(PDO::FETCH_COLUMN));

    if (array_diff($requiredTables, $existingTables) === []) {
        return;
    }

    $schemaFile = dirname(__DIR__) . '/database/' . ($driver === 'mysql' ? 'mysql.sql' : 'sqlite.sql');
    $schema = file_get_contents($schemaFile);
    if ($schema === false) {
        throw new RuntimeException('Skema e databazës mungon.');
    }

    $statements = preg_split('/;\s*(?:\r?\n|$)/', $schema) ?: [];
    foreach ($statements as $sql) {
        $sql = trim($sql);
        if ($sql !== '') {
            $pdo->exec($sql);
        }
    }

    if ($driver === 'mysql') {
        seed_mysql_database($pdo);
    }
}

/**
 * Rotate only untouched demo credentials. Passwords changed by a user have a
 * different hash and are never modified by this migration.
 */
function rotate_legacy_seed_passwords(PDO $pdo): void
{
    $credentials = [
        ['admin', '$2y$12$rDRViSEn9y.G/8FAK0uXG.W0z9LFjFh44d53ZQKS7tR8pGP8tx42a', '$2y$12$wyc2ktoZe65cVZZvAumahOPORRZZH4ImqXXMsRbMUGfeAHyzMYP.m'],
        ['admin', '$2y$12$0s3HquvKz808ZxrSq68KMe8r31Wb6gqguJl1NuRfEsME0YgoE4ZRy', '$2y$12$wyc2ktoZe65cVZZvAumahOPORRZZH4ImqXXMsRbMUGfeAHyzMYP.m'],
    ];
    $update = $pdo->prepare(
        'UPDATE users
         SET password_hash = ?, updated_at = CURRENT_TIMESTAMP
         WHERE username = ? AND password_hash = ?'
    );
    foreach ($credentials as [$username, $oldHash, $newHash]) {
        $update->execute([$newHash, $username, $oldHash]);
    }
}

function purge_expired_bookings(PDO $pdo, ?DateTimeImmutable $today = null): int
{
    $cutoff = booking_history_cutoff($today);

    // Avoid opening a write transaction on normal requests when there is
    // nothing old enough to remove.
    $exists = $pdo->prepare('SELECT 1 FROM bookings WHERE appointment_date < ? LIMIT 1');
    $exists->execute([$cutoff]);
    if ($exists->fetchColumn() === false) {
        return 0;
    }

    // The DELETE is atomic and idempotent. Related appointment slots are
    // removed by the database's ON DELETE CASCADE foreign key.
    $statement = $pdo->prepare('DELETE FROM bookings WHERE appointment_date < ?');
    $statement->execute([$cutoff]);
    return $statement->rowCount();
}

/**
 * Repairs databases created before these indexes were part of the schema
 * files. Missing *tables* are already handled by install_database_schema(),
 * which replays the full schema, so only index repair is left here. Runs once
 * per database, gated by SCHEMA_VERSION.
 */
function ensure_database_compatibility(PDO $pdo, string $driver): void
{
    $indexes = [
        'idx_bookings_phone_date' => 'bookings(phone, appointment_date)',
        'idx_bookings_appointment_date' => 'bookings(appointment_date)',
    ];

    foreach ($indexes as $name => $definition) {
        if ($driver !== 'mysql') {
            $pdo->exec("CREATE INDEX IF NOT EXISTS {$name} ON {$definition}");
            continue;
        }

        // MySQL has no CREATE INDEX IF NOT EXISTS.
        $existing = $pdo->query("SHOW INDEX FROM bookings WHERE Key_name = '{$name}'");
        if (!$existing || !$existing->fetchColumn()) {
            $pdo->exec("CREATE INDEX {$name} ON {$definition}");
        }
    }
}

function seed_mysql_database(PDO $pdo): void
{
    $serviceCount = (int) $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn();
    $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

    if ($serviceCount > 0 && $userCount > 0) {
        return;
    }

    $pdo->beginTransaction();
    try {
        if ($serviceCount === 0) {
            $service = $pdo->prepare(
                'INSERT INTO services (name_sq, name_mk, name_en, description_sq, description_mk, description_en, price_cents, duration_minutes, display_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $services = [
                ['Prerje Gentleman', 'Gentleman шишање', 'Gentleman Cut', 'Konsultë, prerje dhe stilim.', 'Консултација, шишање и стилизирање.', 'Consultation, cut and styling.', 1500, 60, 1],
                ['Mjekër & ritual', 'Брада и ритуал', 'Beard Ritual', 'Formësim, peshqir i ngrohtë dhe vaj premium.', 'Обликување, топла крпа и премиум масло.', 'Shaping, hot towel and premium oil.', 1000, 60, 2],
                ['Paketa e plotë', 'Комплетен пакет', 'Full Experience', 'Prerje, mjekër dhe përfundim premium.', 'Шишање, брада и премиум завршница.', 'Cut, beard and a premium finish.', 2200, 60, 3],
                ['Prerje për fëmijë', 'Детско шишање', 'Junior Cut', 'Prerje e kujdesshme për moshat deri në 12 vjeç.', 'Внимателно шишање за деца до 12 години.', 'A gentle cut for children up to age 12.', 1000, 60, 4],
            ];
            foreach ($services as $row) {
                $service->execute($row);
            }
        }

        if ($userCount === 0) {
            $user = $pdo->prepare('INSERT INTO users (barber_id, role, username, password_hash, full_name) VALUES (?, ?, ?, ?, ?)');
            $user->execute([null, 'admin', 'admin', '$2y$12$wyc2ktoZe65cVZZvAumahOPORRZZH4ImqXXMsRbMUGfeAHyzMYP.m', 'Gentleman Admin']);
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/**
 * Transaction helpers shared by the booking, status and schedule endpoints.
 *
 * SQLite must take the write lock up front with BEGIN IMMEDIATE, otherwise two
 * concurrent bookings can both read a free slot before either writes. MySQL
 * uses a plain transaction; callers add a SELECT ... FOR UPDATE row lock.
 */
function db_begin(PDO $pdo, string $driver): void
{
    if ($driver === 'sqlite') {
        $pdo->exec('BEGIN IMMEDIATE');
        return;
    }
    $pdo->beginTransaction();
}

function db_commit(PDO $pdo, string $driver): void
{
    if ($driver === 'sqlite') {
        $pdo->exec('COMMIT');
        return;
    }
    $pdo->commit();
}

/**
 * Safe to call when no transaction is open, and when the connection already
 * rolled back on its own after an error.
 */
function db_rollback(PDO $pdo, string $driver): void
{
    try {
        if (!$pdo->inTransaction()) {
            return;
        }
        if ($driver === 'sqlite') {
            $pdo->exec('ROLLBACK');
        } else {
            $pdo->rollBack();
        }
    } catch (Throwable) {
        // The database may already have rolled back after a connection error.
    }
}

/**
 * MySQL serialises writes for one barber by locking the barber row; SQLite
 * already holds the database write lock from BEGIN IMMEDIATE.
 */
function db_begin_for_barber(PDO $pdo, string $driver, int $barberId): void
{
    db_begin($pdo, $driver);
    if ($driver === 'sqlite') {
        return;
    }

    $lock = $pdo->prepare('SELECT id FROM barbers WHERE id = ? FOR UPDATE');
    $lock->execute([$barberId]);
    if (!$lock->fetchColumn()) {
        throw new RuntimeException('Berberi nuk është më i disponueshëm.');
    }
}
