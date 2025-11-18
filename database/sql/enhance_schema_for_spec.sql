-- SQL to enhance existing schema for spec requirements
-- Run this in phpMyAdmin for database: u5021d9810_mtldb

-- ========================================
-- PART 1: Add columns to existing tables
-- ========================================

-- Students - add spec-required fields
ALTER TABLE `students` 
ADD COLUMN `country_of_origin` VARCHAR(255) NULL AFTER `phone`,
ADD COLUMN `city_of_residence` VARCHAR(255) NULL AFTER `country_of_origin`,
ADD COLUMN `dob` DATE NULL AFTER `city_of_residence`,
ADD COLUMN `languages` JSON NULL AFTER `dob`,
ADD COLUMN `previous_courses` TEXT NULL AFTER `languages`;

-- Course Offerings - add economic fields and type
ALTER TABLE `course_offerings` 
ADD COLUMN `teacher_hourly_rate` DECIMAL(10, 2) NULL AFTER `price`,
ADD COLUMN `classroom_cost` DECIMAL(10, 2) NULL AFTER `teacher_hourly_rate`,
ADD COLUMN `admin_overhead` DECIMAL(10, 2) NULL AFTER `classroom_cost`,
ADD COLUMN `type` VARCHAR(255) NULL AFTER `program`,
ADD COLUMN `book_included` TINYINT(1) DEFAULT 1 AFTER `type`;

-- Enrollments - add mid-course assessment and trial fields
ALTER TABLE `enrollments` 
ADD COLUMN `dropped_at` TIMESTAMP NULL AFTER `enrolled_at`,
ADD COLUMN `mid_course_level` VARCHAR(255) NULL AFTER `status`,
ADD COLUMN `mid_course_notes` TEXT NULL AFTER `mid_course_level`,
ADD COLUMN `is_trial` TINYINT(1) DEFAULT 0 AFTER `mid_course_notes`;

-- Invoices - add student link and discount fields
ALTER TABLE `invoices` 
ADD COLUMN `student_id` BIGINT UNSIGNED NULL AFTER `billing_contact_id`,
ADD COLUMN `discount_percent` DECIMAL(5, 2) DEFAULT 0.00 AFTER `total`,
ADD COLUMN `discount_reason` VARCHAR(255) NULL AFTER `discount_percent`,
ADD CONSTRAINT `invoices_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL;

-- Payments - add status and refund flag
ALTER TABLE `payments` 
ADD COLUMN `status` ENUM('pending','completed','refunded','failed') DEFAULT 'pending' AFTER `amount`,
ADD COLUMN `is_refund` TINYINT(1) DEFAULT 0 AFTER `status`;

-- ========================================
-- PART 2: Create new tables
-- ========================================

-- Bookings (level checks, Cal.com integration)
CREATE TABLE `bookings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lead_id` BIGINT UNSIGNED NULL,
  `student_id` BIGINT UNSIGNED NULL,
  `booking_provider` VARCHAR(255) DEFAULT 'cal.com',
  `external_booking_id` VARCHAR(255) NULL,
  `booking_type` VARCHAR(255) DEFAULT 'level_check',
  `scheduled_at` TIMESTAMP NULL,
  `assigned_teacher_id` BIGINT UNSIGNED NULL,
  `assigned_level` VARCHAR(255) NULL,
  `teacher_notes` TEXT NULL,
  `status` ENUM('scheduled','completed','cancelled','no_show') DEFAULT 'scheduled',
  `webhook_payload` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bookings_external_booking_id_unique` (`external_booking_id`),
  KEY `bookings_lead_id_foreign` (`lead_id`),
  KEY `bookings_student_id_foreign` (`student_id`),
  KEY `bookings_assigned_teacher_id_foreign` (`assigned_teacher_id`),
  CONSTRAINT `bookings_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bookings_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bookings_assigned_teacher_id_foreign` FOREIGN KEY (`assigned_teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhook events (idempotency and retry queue)
CREATE TABLE `webhook_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider` VARCHAR(255) NOT NULL,
  `event_type` VARCHAR(255) NOT NULL,
  `external_id` VARCHAR(255) NOT NULL,
  `payload` JSON NOT NULL,
  `status` ENUM('pending','processed','failed','ignored') DEFAULT 'pending',
  `retry_count` INT DEFAULT 0,
  `last_retry_at` TIMESTAMP NULL,
  `error_message` TEXT NULL,
  `processed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `webhook_events_external_id_unique` (`external_id`),
  KEY `webhook_events_provider_event_type_index` (`provider`, `event_type`),
  KEY `webhook_events_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email logs (outgoing communications)
CREATE TABLE `email_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `recipient_email` VARCHAR(255) NOT NULL,
  `recipient_name` VARCHAR(255) NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body_html` TEXT NULL,
  `body_text` TEXT NULL,
  `template_name` VARCHAR(255) NULL,
  `related_entity_type` VARCHAR(255) NULL,
  `related_entity_id` BIGINT UNSIGNED NULL,
  `status` ENUM('queued','sent','failed','bounced') DEFAULT 'queued',
  `sent_at` TIMESTAMP NULL,
  `error_message` TEXT NULL,
  `sent_by_user_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `email_logs_related_entity_index` (`related_entity_type`, `related_entity_id`),
  KEY `email_logs_sent_by_user_id_foreign` (`sent_by_user_id`),
  CONSTRAINT `email_logs_sent_by_user_id_foreign` FOREIGN KEY (`sent_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit logs (change history for critical entities)
CREATE TABLE `audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `auditable_type` VARCHAR(255) NOT NULL,
  `auditable_id` BIGINT UNSIGNED NOT NULL,
  `event` VARCHAR(255) NOT NULL,
  `old_values` JSON NULL,
  `new_values` JSON NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_auditable_index` (`auditable_type`, `auditable_id`),
  KEY `audit_logs_user_id_index` (`user_id`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tasks/Activities (coordinator follow-ups, reminders)
CREATE TABLE `tasks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `body` TEXT NULL,
  `assigned_to_user_id` BIGINT UNSIGNED NULL,
  `related_entity_type` VARCHAR(255) NULL,
  `related_entity_id` BIGINT UNSIGNED NULL,
  `due_at` TIMESTAMP NULL,
  `status` ENUM('pending','completed','cancelled') DEFAULT 'pending',
  `priority` ENUM('low','medium','high') DEFAULT 'medium',
  `completed_at` TIMESTAMP NULL,
  `created_by_user_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_related_entity_index` (`related_entity_type`, `related_entity_id`),
  KEY `tasks_status_index` (`status`),
  KEY `tasks_assigned_to_user_id_foreign` (`assigned_to_user_id`),
  KEY `tasks_created_by_user_id_foreign` (`created_by_user_id`),
  CONSTRAINT `tasks_assigned_to_user_id_foreign` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Discount rules (configurable discounts)
CREATE TABLE `discount_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `percent` DECIMAL(5, 2) NOT NULL,
  `rule_type` VARCHAR(255) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `description` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Certificate exports (track certificate generation eligibility)
CREATE TABLE `certificate_exports` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT UNSIGNED NOT NULL,
  `course_offering_id` BIGINT UNSIGNED NOT NULL,
  `attendance_percent` DECIMAL(5, 2) NOT NULL,
  `eligible` TINYINT(1) DEFAULT 0,
  `exported_at` TIMESTAMP NULL,
  `issued_at` TIMESTAMP NULL,
  `certificate_url` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `certificate_exports_eligible_index` (`eligible`),
  KEY `certificate_exports_student_id_foreign` (`student_id`),
  KEY `certificate_exports_course_offering_id_foreign` (`course_offering_id`),
  CONSTRAINT `certificate_exports_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `certificate_exports_course_offering_id_foreign` FOREIGN KEY (`course_offering_id`) REFERENCES `course_offerings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- PART 3: Insert sample discount rules
-- ========================================

INSERT INTO `discount_rules` (`name`, `percent`, `rule_type`, `is_active`, `description`, `created_at`, `updated_at`) VALUES
('Returning Student 5%', 5.00, 'returning', 1, 'Standard 5% discount for returning students', NOW(), NOW()),
('Returning Student 10%', 10.00, 'returning', 1, '10% discount for returning students (special cases)', NOW(), NOW()),
('Referral Discount', 5.00, 'referral', 1, 'Discount for students referred by third parties', NOW(), NOW()),
('Ad-hoc Discount', 0.00, 'ad_hoc', 1, 'Custom percentage discount determined manually', NOW(), NOW());

-- ========================================
-- END OF SQL
-- ========================================
