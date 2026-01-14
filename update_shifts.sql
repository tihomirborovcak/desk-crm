-- Dodaj tip dežurstvo u events tablicu
-- Pokreni u phpMyAdmin

-- Proširi event_type enum da uključuje dežurstvo
ALTER TABLE events MODIFY COLUMN event_type ENUM('press', 'sport', 'kultura', 'politika', 'drustvo', 'dezurstvo', 'ostalo') DEFAULT 'ostalo';

-- Dodaj polje za cijeli dan (za dežurstva)
ALTER TABLE events ADD COLUMN IF NOT EXISTS all_day TINYINT(1) DEFAULT 0 AFTER event_time;

-- Dodaj polje za tip dežurstva
ALTER TABLE events ADD COLUMN IF NOT EXISTS shift_type ENUM('urednik', 'novinar', 'fotograf', 'web', 'dezurni') DEFAULT NULL AFTER all_day;
