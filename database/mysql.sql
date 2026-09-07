CREATE TABLE IF NOT EXISTS barbers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    title_sq VARCHAR(180) NOT NULL,
    title_mk VARCHAR(180) NOT NULL,
    title_en VARCHAR(180) NOT NULL,
    bio_sq TEXT NOT NULL,
    bio_mk TEXT NOT NULL,
    bio_en TEXT NOT NULL,
    phone VARCHAR(40) NOT NULL DEFAULT '',
    email VARCHAR(190) NOT NULL DEFAULT '',
    experience_years SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    image_path VARCHAR(255) NOT NULL DEFAULT '',
    active TINYINT(1) NOT NULL DEFAULT 1,
    display_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_barbers_active_order (active, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barber_id INT UNSIGNED NULL,
    role ENUM('admin', 'barber') NOT NULL,
    username VARCHAR(80) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_barber FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name_sq VARCHAR(160) NOT NULL,
    name_mk VARCHAR(160) NOT NULL,
    name_en VARCHAR(160) NOT NULL,
    description_sq TEXT NOT NULL,
    description_mk TEXT NOT NULL,
    description_en TEXT NOT NULL,
    price_cents INT UNSIGNED NOT NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    active TINYINT(1) NOT NULL DEFAULT 1,
    display_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_services_active_order (active, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_code VARCHAR(20) NOT NULL UNIQUE,
    barber_id INT UNSIGNED NOT NULL,
    service_id INT UNSIGNED NOT NULL,
    access_token_hash CHAR(64) NOT NULL,
    customer_name VARCHAR(120) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    email VARCHAR(190) NOT NULL DEFAULT '',
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    language ENUM('sq', 'mk', 'en') NOT NULL DEFAULT 'sq',
    notes TEXT NOT NULL,
    service_name_snapshot VARCHAR(160) NOT NULL,
    price_cents_snapshot INT UNSIGNED NOT NULL,
    duration_minutes_snapshot SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    status ENUM('pending', 'accepted', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    decision_note VARCHAR(255) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_barber FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE RESTRICT,
    CONSTRAINT fk_bookings_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
    INDEX idx_bookings_barber_date (barber_id, appointment_date),
    INDEX idx_bookings_public_phone (public_code, phone),
    INDEX idx_bookings_phone_date (phone, appointment_date),
    INDEX idx_bookings_appointment_date (appointment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS appointment_slots (
    booking_id BIGINT UNSIGNED NOT NULL,
    barber_id INT UNSIGNED NOT NULL,
    slot_start DATETIME NOT NULL,
    PRIMARY KEY (barber_id, slot_start),
    INDEX idx_slots_booking (booking_id),
    CONSTRAINT fk_slots_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT fk_slots_barber FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blocked_slots (
    barber_id INT UNSIGNED NOT NULL,
    blocked_date DATE NOT NULL,
    blocked_time TIME NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (barber_id, blocked_date, blocked_time),
    INDEX idx_blocked_slots_date (blocked_date),
    CONSTRAINT fk_blocked_slots_barber FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE CASCADE,
    CONSTRAINT fk_blocked_slots_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limit_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(64) NOT NULL,
    identity_hash CHAR(64) NOT NULL,
    created_at_epoch BIGINT UNSIGNED NOT NULL,
    INDEX idx_rate_limit_lookup (action, identity_hash, created_at_epoch)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
