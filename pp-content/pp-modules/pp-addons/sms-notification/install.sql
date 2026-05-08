-- PipraPay SMS Notification Addon — Database Schema
-- Run this SQL to create the sms_logs table

CREATE TABLE IF NOT EXISTS `pp_sms_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `addon_id` VARCHAR(50) NOT NULL DEFAULT 'sms_notification',
    `recipient` VARCHAR(20) NOT NULL,
    `message` TEXT NOT NULL,
    `provider` VARCHAR(50) NOT NULL DEFAULT 'bulksmsbd',
    `status` ENUM('sent', 'failed', 'pending') NOT NULL DEFAULT 'pending',
    `response` TEXT NULL,
    `created_date` DATETIME NOT NULL,
    INDEX `idx_addon_id` (`addon_id`),
    INDEX `idx_recipient` (`recipient`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_date` (`created_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Addon settings table (if not exists)
CREATE TABLE IF NOT EXISTS `pp_addon_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `addon_id` VARCHAR(50) NOT NULL UNIQUE,
    `settings` JSON NULL,
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `created_date` DATETIME NOT NULL,
    `updated_date` DATETIME NOT NULL,
    INDEX `idx_addon_id` (`addon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
