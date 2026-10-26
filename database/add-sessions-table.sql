-- Adds the table used to store sessions. Run once on an existing database.
CREATE TABLE IF NOT EXISTS sessions (
  id              VARCHAR(128) NOT NULL PRIMARY KEY,
  payload         MEDIUMTEXT   NOT NULL,
  last_activity   INT UNSIGNED NOT NULL,
  INDEX idx_sessions_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
