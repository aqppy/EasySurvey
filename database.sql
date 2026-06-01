-- Survey system bootstrap SQL
-- Database: survey_system

CREATE TABLE IF NOT EXISTS `surveys` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `status` TINYINT NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `questions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `survey_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(500) NOT NULL,
  `type` VARCHAR(20) NOT NULL,
  `options` TEXT DEFAULT NULL,
  `required` TINYINT NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_survey_id` (`survey_id`),
  CONSTRAINT `fk_questions_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `responses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `survey_id` INT UNSIGNED NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_survey_id` (`survey_id`),
  KEY `idx_submitted_at` (`submitted_at`),
  KEY `idx_ip_time` (`ip_address`, `submitted_at`),
  CONSTRAINT `fk_responses_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `answers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `response_id` INT UNSIGNED NOT NULL,
  `question_id` INT UNSIGNED NOT NULL,
  `answer_value` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_response_id` (`response_id`),
  KEY `idx_question_id` (`question_id`),
  CONSTRAINT `fk_answers_response` FOREIGN KEY (`response_id`) REFERENCES `responses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_answers_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `survey_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `survey_id` INT UNSIGNED NOT NULL,
  `settings_json` JSON NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_survey_settings_survey_id` (`survey_id`),
  CONSTRAINT `fk_survey_settings_survey`
    FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `survey_themes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `survey_id` INT UNSIGNED NOT NULL,
  `logo_url` VARCHAR(500) DEFAULT NULL,
  `header_image_url` VARCHAR(500) DEFAULT NULL,
  `theme_color` VARCHAR(20) DEFAULT NULL,
  `background_color` VARCHAR(20) DEFAULT NULL,
  `background_image_url` VARCHAR(500) DEFAULT NULL,
  `submit_button_text` VARCHAR(100) DEFAULT NULL,
  `show_title` TINYINT NOT NULL DEFAULT 1,
  `show_description` TINYINT NOT NULL DEFAULT 1,
  `show_number` TINYINT NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_survey_themes_survey_id` (`survey_id`),
  CONSTRAINT `fk_survey_themes_survey`
    FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `app_settings` (
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `app_settings` (`setting_key`, `setting_value`)
SELECT 'app_name', '问卷调查系统'
WHERE NOT EXISTS (SELECT 1 FROM `app_settings` WHERE `setting_key` = 'app_name');

INSERT INTO `app_settings` (`setting_key`, `setting_value`)
SELECT 'app_logo_url', ''
WHERE NOT EXISTS (SELECT 1 FROM `app_settings` WHERE `setting_key` = 'app_logo_url');

INSERT INTO `app_settings` (`setting_key`, `setting_value`)
SELECT 'browser_title_template', '{page} - {app}'
WHERE NOT EXISTS (SELECT 1 FROM `app_settings` WHERE `setting_key` = 'browser_title_template');

INSERT INTO `app_settings` (`setting_key`, `setting_value`)
SELECT 'web_base_url', 'https://test.gdmrcare.com'
WHERE NOT EXISTS (SELECT 1 FROM `app_settings` WHERE `setting_key` = 'web_base_url');

INSERT INTO `app_settings` (`setting_key`, `setting_value`)
SELECT 'open_wx_mp_login', '0'
WHERE NOT EXISTS (SELECT 1 FROM `app_settings` WHERE `setting_key` = 'open_wx_mp_login');

INSERT INTO `app_settings` (`setting_key`, `setting_value`)
SELECT 'copyright', ''
WHERE NOT EXISTS (SELECT 1 FROM `app_settings` WHERE `setting_key` = 'copyright');
