-- MySQL 8 only. Referenced tables must use InnoDB and compatible unsigned IDs.
-- Additive schema only: no citizen records, credentials, resets, or file deletion.
-- Select the correct database first and verify a restorable backup.
-- Run serially, STOP on the first error, and inspect all postflight results.
-- Existing tables are NOT repaired/replaced by IF NOT EXISTS.
-- Photos reuse stored_files; object content stays in the approved private B2 disk.
CREATE TABLE IF NOT EXISTS `gallery_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `rt_area_id` BIGINT UNSIGNED NULL,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(180) NOT NULL,
    `description` TEXT NULL,
    `type` VARCHAR(10) NOT NULL,
    `video_url` VARCHAR(2048) NULL,
    `audience` VARCHAR(20) NOT NULL DEFAULT 'residents',
    `is_published` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `gallery_publication_index` (`audience`, `is_published`, `created_at`),
    KEY `gallery_items_rt_area_id_foreign` (`rt_area_id`),
    KEY `gallery_items_created_by_foreign` (`created_by`),
    CONSTRAINT `gallery_items_rt_area_id_foreign` FOREIGN KEY (`rt_area_id`) REFERENCES `rt_areas` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `gallery_items_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gallery_items'
ORDER BY ORDINAL_POSITION;

SELECT INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gallery_items'
ORDER BY INDEX_NAME, SEQ_IN_INDEX;

SELECT k.CONSTRAINT_NAME, k.COLUMN_NAME, k.REFERENCED_TABLE_NAME,
       k.REFERENCED_COLUMN_NAME, r.DELETE_RULE, r.UPDATE_RULE
FROM information_schema.KEY_COLUMN_USAGE k
JOIN information_schema.REFERENTIAL_CONSTRAINTS r
  ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
 AND r.TABLE_NAME = k.TABLE_NAME
 AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
WHERE k.TABLE_SCHEMA = DATABASE() AND k.TABLE_NAME = 'gallery_items'
ORDER BY k.CONSTRAINT_NAME;
-- Expected: 11 columns, primary ID, publication index, two FK indexes,
-- two enforced foreign keys to existing rt_areas/users with DELETE RESTRICT.
