-- ============================================================
-- FILE: database/database.sql
-- PURPOSE: Complete database schema for SkillSprout platform.
--          Run this file in phpMyAdmin or MySQL CLI to set up
--          all tables, foreign keys, and default data.
-- ============================================================

-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `skillsprout`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

-- Select the database
USE `skillsprout`;

-- ============================================================
-- TABLE: users
-- Stores all registered user accounts including credentials,
-- profile info, Work Point balance, and account status.
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `user_id`      INT AUTO_INCREMENT PRIMARY KEY,
  `name`         VARCHAR(100) NOT NULL,
  `email`        VARCHAR(150) NOT NULL UNIQUE,
  `password`     VARCHAR(255) NOT NULL,               -- Bcrypt hashed password
  `bio`          TEXT DEFAULT NULL,                     -- Optional user biography
  `skills`       VARCHAR(500) DEFAULT NULL,             -- Comma-separated skill tags
  `wp_balance`   INT NOT NULL DEFAULT 100,              -- Work Points balance (starts at 100)
  `role`         ENUM('user','moderator','admin') NOT NULL DEFAULT 'user',
  `status`       ENUM('active','warned','suspended','banned') NOT NULL DEFAULT 'active',
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: tasks
-- Stores every task posted on the platform. Each task has a
-- creator, optional assigned worker, reward amount, deadline,
-- and a status tracking its lifecycle.
-- ============================================================
CREATE TABLE IF NOT EXISTS `tasks` (
  `task_id`          INT AUTO_INCREMENT PRIMARY KEY,
  `creator_id`       INT NOT NULL,                        -- User who posted the task
  `assigned_user_id` INT DEFAULT NULL,                    -- Worker assigned to the task
  `title`            VARCHAR(255) NOT NULL,
  `description`      TEXT NOT NULL,
  `domain`           VARCHAR(100) DEFAULT NULL,            -- Category/domain of the task
  `skills_required`  VARCHAR(500) DEFAULT NULL,            -- Skills needed to complete
  `reward`           INT NOT NULL,                         -- Work Points offered as reward
  `deadline`         DATE DEFAULT NULL,
  `status`           ENUM('OPEN','ASSIGNED','SUBMITTED','COMPLETED','CANCELLED') NOT NULL DEFAULT 'OPEN',
  `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (`creator_id`)       REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: applications
-- When a worker applies for a task, a row is inserted here.
-- The task creator can then accept or reject each application.
-- ============================================================
CREATE TABLE IF NOT EXISTS `applications` (
  `application_id` INT AUTO_INCREMENT PRIMARY KEY,
  `task_id`         INT NOT NULL,
  `user_id`         INT NOT NULL,                         -- The applicant (worker)
  `pitch`           TEXT DEFAULT NULL,                     -- Application message
  `status`          ENUM('PENDING','ACCEPTED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `applied_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (`task_id`) REFERENCES `tasks`(`task_id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: submissions
-- When an assigned worker submits their completed work, the
-- deliverable details are stored here for the creator to review.
-- ============================================================
CREATE TABLE IF NOT EXISTS `submissions` (
  `submission_id` INT AUTO_INCREMENT PRIMARY KEY,
  `task_id`       INT NOT NULL,
  `user_id`       INT NOT NULL,                           -- The worker who submitted
  `work_details`  TEXT NOT NULL,                           -- Description / links of deliverable
  `file_path`     VARCHAR(500) DEFAULT NULL,               -- Optional uploaded file path
  `status`        ENUM('SUBMITTED','APPROVED','REJECTED') NOT NULL DEFAULT 'SUBMITTED',
  `submitted_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at`   TIMESTAMP NULL DEFAULT NULL,

  FOREIGN KEY (`task_id`) REFERENCES `tasks`(`task_id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: transactions
-- Logs every Work Point movement: top-up purchases, escrow
-- locks, payouts, refunds, and admin adjustments.
-- ============================================================
CREATE TABLE IF NOT EXISTS `transactions` (
  `transaction_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`        INT NOT NULL,
  `type`           ENUM('SIGNUP_BONUS','PURCHASE','ESCROW_LOCK','ESCROW_RELEASE','PAYOUT','REFUND','ADMIN_ADJUST') NOT NULL,
  `amount_wp`      INT NOT NULL,                           -- Positive = credit, Negative = debit
  `description`    VARCHAR(500) DEFAULT NULL,
  `reference_id`   INT DEFAULT NULL,                       -- Related task_id or package_id
  `price_paid`     DECIMAL(10,2) DEFAULT NULL,             -- Fiat amount if purchase
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: disputes
-- When a task creator and worker disagree on submission quality,
-- a dispute is filed for admin arbitration.
-- ============================================================
CREATE TABLE IF NOT EXISTS `disputes` (
  `dispute_id`    INT AUTO_INCREMENT PRIMARY KEY,
  `task_id`       INT NOT NULL,
  `filed_by`      INT NOT NULL,                            -- User who filed the dispute
  `reason`        TEXT NOT NULL,
  `evidence`      TEXT DEFAULT NULL,                        -- Links or descriptions of proof
  `status`        ENUM('OPEN','UNDER_REVIEW','RESOLVED') NOT NULL DEFAULT 'OPEN',
  `resolution`    ENUM('WORKER_PAID','CREATOR_REFUNDED','SPLIT','DISMISSED') DEFAULT NULL,
  `admin_notes`   TEXT DEFAULT NULL,
  `resolved_by`   INT DEFAULT NULL,                         -- Admin who resolved it
  `filed_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `resolved_at`   TIMESTAMP NULL DEFAULT NULL,

  FOREIGN KEY (`task_id`)    REFERENCES `tasks`(`task_id`) ON DELETE CASCADE,
  FOREIGN KEY (`filed_by`)   REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`resolved_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: admin_logs
-- Audit trail for every significant action taken by admins.
-- ============================================================
CREATE TABLE IF NOT EXISTS `admin_logs` (
  `log_id`      INT AUTO_INCREMENT PRIMARY KEY,
  `admin_id`    INT NOT NULL,
  `action`      VARCHAR(255) NOT NULL,                     -- e.g. "Banned user #42"
  `details`     TEXT DEFAULT NULL,
  `ip_address`  VARCHAR(45) DEFAULT NULL,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (`admin_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: platform_settings
-- Key-value store for global platform configuration like
-- registration bonus, minimum reward, maintenance mode, etc.
-- ============================================================
CREATE TABLE IF NOT EXISTS `platform_settings` (
  `setting_id`    INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key`   VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT NOT NULL,
  `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: skill_categories
-- Taxonomy for organizing tasks by skill domain/category.
-- ============================================================
CREATE TABLE IF NOT EXISTS `skill_categories` (
  `category_id`   INT AUTO_INCREMENT PRIMARY KEY,
  `name`          VARCHAR(100) NOT NULL UNIQUE,
  `description`   VARCHAR(500) DEFAULT NULL,
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- DEFAULT DATA INSERTS
-- ============================================================

-- Insert default platform settings
INSERT INTO `platform_settings` (`setting_key`, `setting_value`) VALUES
  ('registration_bonus', '100'),
  ('min_task_reward', '5'),
  ('maintenance_mode', 'off'),
  ('announcement', ''),
  ('site_name', 'Skill Sprout');

-- Insert default skill categories
INSERT INTO `skill_categories` (`name`, `description`) VALUES
  ('Web Development', 'HTML, CSS, JavaScript, PHP, React, Node.js'),
  ('Graphic Design', 'Logos, banners, illustrations, UI/UX mockups'),
  ('Content Writing', 'Blog posts, articles, copywriting, SEO content'),
  ('Translation', 'Language translation and localization services'),
  ('Data Entry', 'Spreadsheet work, data processing, form filling'),
  ('Academic Help', 'Tutoring, research assistance, study guides'),
  ('Video Editing', 'Video production, editing, motion graphics'),
  ('Mobile Development', 'Android and iOS application development'),
  ('Digital Marketing', 'SEO, social media management, ad campaigns'),
  ('Other', 'Miscellaneous tasks and services');

-- Insert a default admin account (password: admin123 - change immediately)
-- The hash below is for the password "admin123" using PASSWORD_DEFAULT (bcrypt)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `wp_balance`) VALUES
  ('Admin', 'admin@skillsprout.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 99999);
