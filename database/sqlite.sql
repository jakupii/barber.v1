PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS barbers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    title_sq TEXT NOT NULL,
    title_mk TEXT NOT NULL,
    title_en TEXT NOT NULL,
    bio_sq TEXT NOT NULL DEFAULT '',
    bio_mk TEXT NOT NULL DEFAULT '',
    bio_en TEXT NOT NULL DEFAULT '',
    phone TEXT NOT NULL DEFAULT '',
    email TEXT NOT NULL DEFAULT '',
    experience_years INTEGER NOT NULL DEFAULT 0,
    image_path TEXT NOT NULL DEFAULT '',
    active INTEGER NOT NULL DEFAULT 1,
    display_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    barber_id INTEGER NULL,
    role TEXT NOT NULL CHECK(role IN ('admin', 'barber')),
    username TEXT NOT NULL COLLATE NOCASE UNIQUE,
    password_hash TEXT NOT NULL,
    full_name TEXT NOT NULL,
    active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name_sq TEXT NOT NULL,
    name_mk TEXT NOT NULL,
    name_en TEXT NOT NULL,
    description_sq TEXT NOT NULL DEFAULT '',
    description_mk TEXT NOT NULL DEFAULT '',
    description_en TEXT NOT NULL DEFAULT '',
    price_cents INTEGER NOT NULL CHECK(price_cents >= 0),
    duration_minutes INTEGER NOT NULL DEFAULT 60,
    active INTEGER NOT NULL DEFAULT 1,
    display_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    public_code TEXT NOT NULL UNIQUE,
    barber_id INTEGER NOT NULL,
    service_id INTEGER NOT NULL,
    access_token_hash TEXT NOT NULL,
    customer_name TEXT NOT NULL,
    phone TEXT NOT NULL,
    email TEXT NOT NULL DEFAULT '',
    appointment_date TEXT NOT NULL,
    appointment_time TEXT NOT NULL,
    language TEXT NOT NULL DEFAULT 'sq',
    notes TEXT NOT NULL DEFAULT '',
    service_name_snapshot TEXT NOT NULL,
    price_cents_snapshot INTEGER NOT NULL,
    duration_minutes_snapshot INTEGER NOT NULL DEFAULT 60,
    status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending', 'accepted', 'rejected', 'cancelled')),
    decision_note TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE RESTRICT,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS appointment_slots (
    booking_id INTEGER NOT NULL,
    barber_id INTEGER NOT NULL,
    slot_start TEXT NOT NULL,
    PRIMARY KEY (barber_id, slot_start),
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS blocked_slots (
    barber_id INTEGER NOT NULL,
    blocked_date TEXT NOT NULL,
    blocked_time TEXT NOT NULL,
    created_by INTEGER NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (barber_id, blocked_date, blocked_time),
    FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS rate_limit_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    action TEXT NOT NULL,
    identity_hash TEXT NOT NULL,
    created_at_epoch INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_bookings_barber_date ON bookings(barber_id, appointment_date);
CREATE INDEX IF NOT EXISTS idx_bookings_public_phone ON bookings(public_code, phone);
CREATE INDEX IF NOT EXISTS idx_bookings_phone_date ON bookings(phone, appointment_date);
CREATE INDEX IF NOT EXISTS idx_bookings_appointment_date ON bookings(appointment_date);
CREATE INDEX IF NOT EXISTS idx_slots_booking ON appointment_slots(booking_id);
CREATE INDEX IF NOT EXISTS idx_blocked_slots_date ON blocked_slots(blocked_date);
CREATE INDEX IF NOT EXISTS idx_rate_limit_lookup ON rate_limit_events(action, identity_hash, created_at_epoch);

INSERT OR IGNORE INTO barbers (id, name, title_sq, title_mk, title_en, bio_sq, bio_mk, bio_en, phone, email, experience_years, image_path, display_order) VALUES
    (1, 'Arben Krasniqi', 'Mjeshtër i prerjeve klasike', 'Мајстор за класични фризури', 'Classic cuts specialist', 'Precizitet klasik, fade modern dhe kujdes i plotë për çdo detaj.', 'Класична прецизност, модерен fade и целосно внимание на секој детал.', 'Classic precision, modern fades and complete attention to every detail.', '+389 70 111 201', 'arben@gentlemanbarber.mk', 12, '/assets/images/barbers/arben-v2.jpg', 1),
    (2, 'Dion Mehmeti', 'Specialist i fade & stilimit', 'Специјалист за fade и стилизирање', 'Fade & styling specialist', 'Stil bashkëkohor, linja të pastra dhe këshillim sipas tipareve tuaja.', 'Современ стил, чисти линии и советување според вашите карактеристики.', 'Contemporary style, clean lines and advice tailored to your features.', '+389 70 111 202', 'dion@gentlemanbarber.mk', 8, '/assets/images/barbers/dion-v2.jpg', 2),
    (3, 'Leo Stojanov', 'Ekspert i mjekrës', 'Експерт за брада', 'Beard grooming expert', 'Formësim mjekre, ritual me peshqir të ngrohtë dhe përfundim premium.', 'Обликување брада, ритуал со топла крпа и премиум завршница.', 'Beard shaping, hot-towel ritual and a premium finish.', '+389 70 111 203', 'leo@gentlemanbarber.mk', 10, '/assets/images/barbers/leo-v2.jpg', 3);

INSERT OR IGNORE INTO users (id, barber_id, role, username, password_hash, full_name) VALUES
    (1, NULL, 'admin', 'admin', '$2y$12$s9IC72Jcapjbk1SBMJ0cb.QfbDBILKAGfAOyEUCYUaBO89cGwEhsm', 'Gentleman Admin'),
    (2, 1, 'admin', 'admin', '$2y$12$P3BnmWfheKEgKxbQ6QnNiuYV2SxNxdIqiNytgh1IIrjWtT4.ZpuA.', 'Arben Krasniqi'),
    (3, 2, 'barber', 'dion', '$2y$12$xEQ0aJ44Ggf84iZ.1tDYF.hrxCI6Ct7ITzHu2ZKDbDlDnyPYjLrXm', 'Dion Mehmeti'),
    (4, 3, 'barber', 'leo', '$2y$12$.L3Ik.stTKq/CTWtknnV5OHW1zhNRmR6ABHvUVFUC81Mk4T55mVpG', 'Leo Stojanov');

INSERT OR IGNORE INTO services (id, name_sq, name_mk, name_en, description_sq, description_mk, description_en, price_cents, duration_minutes, display_order) VALUES
    (1, 'Prerje Gentleman', 'Gentleman шишање', 'Gentleman Cut', 'Konsultë, prerje dhe stilim.', 'Консултација, шишање и стилизирање.', 'Consultation, cut and styling.', 1500, 60, 1),
    (2, 'Mjekër & ritual', 'Брада и ритуал', 'Beard Ritual', 'Formësim, peshqir i ngrohtë dhe vaj premium.', 'Обликување, топла крпа и премиум масло.', 'Shaping, hot towel and premium oil.', 1000, 60, 2),
    (3, 'Paketa e plotë', 'Комплетен пакет', 'Full Experience', 'Prerje, mjekër dhe përfundim premium.', 'Шишање, брада и премиум завршница.', 'Cut, beard and a premium finish.', 2200, 60, 3),
    (4, 'Prerje për fëmijë', 'Детско шишање', 'Junior Cut', 'Prerje e kujdesshme për moshat deri në 12 vjeç.', 'Внимателно шишање за деца до 12 години.', 'A gentle cut for children up to age 12.', 1000, 60, 4);
