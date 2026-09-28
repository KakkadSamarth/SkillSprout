-- WorkPoint Database Schema --

CREATE DATABASE IF NOT EXISTS `workpoint` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `workpoint`;

-- Users table--
CREATE TABLE IF NOT EXISTS `users` (
    `user_id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `wp_balance` INT NOT NULL DEFAULT 100,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tasks table --
CREATE TABLE IF NOT EXISTS `tasks` (
    `task_id` INT AUTO_INCREMENT PRIMARY KEY,
    `creator_id` INT NOT NULL,
    `assigned_user_id` INT NULL DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `domain` VARCHAR(100) NOT NULL,
    `required_skills` TEXT NULL,
    `reward_wp` INT NOT NULL,
    `deadline` DATE NOT NULL,
    `status` ENUM('OPEN', 'ASSIGNED', 'SUBMITTED', 'COMPLETED', 'CANCELLED') NOT NULL DEFAULT 'OPEN',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_tasks_creator` FOREIGN KEY (`creator_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tasks_assigned` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Applications table--
CREATE TABLE IF NOT EXISTS `applications` (
    `application_id` INT AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `message` TEXT NULL,
    `status` ENUM('PENDING', 'ACCEPTED', 'REJECTED') NOT NULL DEFAULT 'PENDING',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_applications_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`task_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_applications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Submissions table--
CREATE TABLE IF NOT EXISTS `submissions` (
    `submission_id` INT AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `submission_text` TEXT NOT NULL,
    `status` ENUM('SUBMITTED', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'SUBMITTED',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_submissions_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`task_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_submissions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
