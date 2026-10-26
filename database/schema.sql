-- Student Document Request System: database schema (MySQL 8.0+ / MariaDB 10.5+)
-- Run once on a new database. See database/README.md.

CREATE DATABASE IF NOT EXISTS college_document_request
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE college_document_request;

CREATE TABLE IF NOT EXISTS users (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_number  VARCHAR(30)  NULL UNIQUE,
  first_name      VARCHAR(50)  NOT NULL,
  middle_name     VARCHAR(50)  NULL,
  last_name       VARCHAR(50)  NOT NULL,
  email           VARCHAR(100) NOT NULL UNIQUE,
  password_hash   VARCHAR(255) NOT NULL,
  role            ENUM('student', 'registrar', 'admin') NOT NULL DEFAULT 'student',
  department      VARCHAR(100) NULL,
  course          VARCHAR(100) NULL,
  contact_number  VARCHAR(20)  NULL,
  account_status  ENUM('pending', 'active', 'deactivated') NOT NULL DEFAULT 'active',
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_role_status (role, account_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_types (
  id                         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name                       VARCHAR(100) NOT NULL UNIQUE,
  description                VARCHAR(500) NOT NULL,
  requirements               VARCHAR(500) NULL,
  estimated_processing_days  INT UNSIGNED NOT NULL DEFAULT 3,
  active                     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at                 TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at                 TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_requests (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference_number  VARCHAR(20)  NOT NULL UNIQUE,
  student_id        INT UNSIGNED NOT NULL,
  document_type_id  INT UNSIGNED NOT NULL,
  copies            INT UNSIGNED NOT NULL DEFAULT 1,
  purpose           VARCHAR(255) NOT NULL,
  remarks           TEXT         NULL,
  status            ENUM('Submitted', 'Under Review', 'Approved', 'Processing',
                         'Ready for Release', 'Released', 'Rejected', 'Cancelled')
                    NOT NULL DEFAULT 'Submitted',
  submitted_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  released_at       DATETIME     NULL,
  processed_by      INT UNSIGNED NULL,
  CONSTRAINT fk_requests_student FOREIGN KEY (student_id) REFERENCES users (id) ON DELETE RESTRICT,
  CONSTRAINT fk_requests_type FOREIGN KEY (document_type_id) REFERENCES document_types (id) ON DELETE RESTRICT,
  CONSTRAINT fk_requests_processed_by FOREIGN KEY (processed_by) REFERENCES users (id) ON DELETE SET NULL,
  INDEX idx_requests_status (status, submitted_at),
  INDEX idx_requests_student (student_id, submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Every status change is kept here. Deleting a request removes its history;
-- deleting a user is blocked while they still have history, so the trail stays intact.
CREATE TABLE IF NOT EXISTS request_status_history (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_id      INT UNSIGNED NOT NULL,
  previous_status VARCHAR(50)  NULL,
  new_status      VARCHAR(50)  NOT NULL,
  remarks         TEXT         NULL,
  changed_by      INT UNSIGNED NOT NULL,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_history_request FOREIGN KEY (request_id) REFERENCES document_requests (id) ON DELETE CASCADE,
  CONSTRAINT fk_history_user FOREIGN KEY (changed_by) REFERENCES users (id) ON DELETE RESTRICT,
  INDEX idx_history_request (request_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  request_id  INT UNSIGNED NULL,
  title       VARCHAR(150) NOT NULL,
  message     TEXT         NOT NULL,
  is_read     TINYINT(1)   NOT NULL DEFAULT 0,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_notifications_request FOREIGN KEY (request_id) REFERENCES document_requests (id) ON DELETE SET NULL,
  INDEX idx_notifications_user (user_id, is_read, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED NULL,
  action       VARCHAR(100) NOT NULL,
  entity_type  VARCHAR(50)  NOT NULL,
  entity_id    INT UNSIGNED NULL,
  details      TEXT         NULL,
  ip_address   VARCHAR(45)  NULL,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
  INDEX idx_audit_action (action),
  INDEX idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Session storage (required for Vercel and other multi-instance hosting).
CREATE TABLE IF NOT EXISTS sessions (
  id              VARCHAR(128) NOT NULL PRIMARY KEY,
  payload         MEDIUMTEXT   NOT NULL,
  last_activity   INT UNSIGNED NOT NULL,
  INDEX idx_sessions_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
