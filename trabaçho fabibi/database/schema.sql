-- ============================================================
-- Project: FormSecure
-- File: schema.sql
-- Description: Database schema for user registration system
-- MySQL 5.7+ / 8.0+
-- ============================================================

CREATE DATABASE IF NOT EXISTS `formsecure`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `formsecure`;

-- ------------------------------------------------------------
-- Table: users
-- Stores user registration data with bcrypt-hashed passwords
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(100)  NOT NULL,
  `email`         VARCHAR(254)  NOT NULL,
  `password_hash` CHAR(60)      NOT NULL COMMENT 'bcrypt hash via password_hash()',
  `message`       VARCHAR(250)  NOT NULL,
  `ip_address`    VARCHAR(45)   DEFAULT NULL COMMENT 'IPv4 or IPv6',
  `user_agent`    VARCHAR(512)  DEFAULT NULL,
  `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  INDEX `idx_users_created_at` (`created_at`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Registered users — passwords stored as bcrypt hashes';

-- ------------------------------------------------------------
-- Table: submission_logs
-- Audit trail for every form submission attempt
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `submission_logs` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        INT UNSIGNED DEFAULT NULL COMMENT 'NULL when registration fails',
  `ip_address`     VARCHAR(45)  NOT NULL,
  `status`         ENUM('success','failure') NOT NULL,
  `failure_reason` VARCHAR(255) DEFAULT NULL,
  `submitted_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  INDEX `idx_logs_ip`           (`ip_address`),
  INDEX `idx_logs_submitted_at` (`submitted_at`),
  CONSTRAINT `fk_logs_user`
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Audit log of every submission attempt (success or failure)';
