-- APK-RT-RW: additive schema only; no citizen records or credentials.
-- Select the correct database first in your SQL client. No USE is hard-coded.
-- Verify a restorable backup and test on matching non-production engine/version.
-- Run serially and STOP on the first error. DDL is not one rollbackable transaction.
-- Existing column/index definitions are not silently rewritten; inspect postflight.
-- This SQL does NOT encrypt/backfill data or enable the application privacy flag.

-- MySQL 8: information_schema guards plus prepared DDL; no stored routines.

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'must_change_password'),
    'SELECT ''SKIP: users.must_change_password already exists; verify its definition'' AS status',
    'ALTER TABLE `users` ADD COLUMN `must_change_password` TINYINT(1) NOT NULL DEFAULT 0'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'households' AND COLUMN_NAME = 'family_card_number'),
    'SELECT ''SKIP: households.family_card_number already exists; verify its definition'' AS status',
    'ALTER TABLE `households` ADD COLUMN `family_card_number` TEXT NULL'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'households' AND COLUMN_NAME = 'family_card_digest'),
    'SELECT ''SKIP: households.family_card_digest already exists; verify its definition'' AS status',
    'ALTER TABLE `households` ADD COLUMN `family_card_digest` CHAR(64) NULL'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'residents' AND COLUMN_NAME = 'national_id'),
    'SELECT ''SKIP: residents.national_id already exists; verify its definition'' AS status',
    'ALTER TABLE `residents` ADD COLUMN `national_id` TEXT NULL'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'residents' AND COLUMN_NAME = 'national_id_digest'),
    'SELECT ''SKIP: residents.national_id_digest already exists; verify its definition'' AS status',
    'ALTER TABLE `residents` ADD COLUMN `national_id_digest` CHAR(64) NULL'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'residents' AND COLUMN_NAME = 'birthplace'),
    'SELECT ''SKIP: residents.birthplace already exists; verify its definition'' AS status',
    'ALTER TABLE `residents` ADD COLUMN `birthplace` VARCHAR(160) NULL'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'residents' AND COLUMN_NAME = 'birth_date'),
    'SELECT ''SKIP: residents.birth_date already exists; verify its definition'' AS status',
    'ALTER TABLE `residents` ADD COLUMN `birth_date` DATE NULL'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'residents' AND COLUMN_NAME = 'gender'),
    'SELECT ''SKIP: residents.gender already exists; verify its definition'' AS status',
    'ALTER TABLE `residents` ADD COLUMN `gender` VARCHAR(16) NULL'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'residents' AND COLUMN_NAME = 'religion'),
    'SELECT ''SKIP: residents.religion already exists; verify its definition'' AS status',
    'ALTER TABLE `residents` ADD COLUMN `religion` TEXT NULL'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'residents' AND COLUMN_NAME = 'marital_status'),
    'SELECT ''SKIP: residents.marital_status already exists; verify its definition'' AS status',
    'ALTER TABLE `residents` ADD COLUMN `marital_status` VARCHAR(32) NULL'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'residents' AND COLUMN_NAME = 'education'),
    'SELECT ''SKIP: residents.education already exists; verify its definition'' AS status',
    'ALTER TABLE `residents` ADD COLUMN `education` VARCHAR(80) NULL'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'residents' AND COLUMN_NAME = 'occupation'),
    'SELECT ''SKIP: residents.occupation already exists; verify its definition'' AS status',
    'ALTER TABLE `residents` ADD COLUMN `occupation` VARCHAR(160) NULL'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'residents' AND COLUMN_NAME = 'residency_status'),
    'SELECT ''SKIP: residents.residency_status already exists; verify its definition'' AS status',
    'ALTER TABLE `residents` ADD COLUMN `residency_status` VARCHAR(32) NULL'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'households' AND INDEX_NAME = 'households_family_card_digest_unique'),
    'SELECT ''SKIP: households_family_card_digest_unique already exists; verify uniqueness/column'' AS status',
    'ALTER TABLE `households` ADD UNIQUE INDEX `households_family_card_digest_unique` (`family_card_digest`)'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'residents' AND INDEX_NAME = 'residents_national_id_digest_unique'),
    'SELECT ''SKIP: residents_national_id_digest_unique already exists; verify uniqueness/column'' AS status',
    'ALTER TABLE `residents` ADD UNIQUE INDEX `residents_national_id_digest_unique` (`national_id_digest`)'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;


-- Postflight: compare every returned type/length/nullability with the file above.
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND ((TABLE_NAME = 'users' AND COLUMN_NAME = 'must_change_password') OR (TABLE_NAME = 'households' AND COLUMN_NAME = 'family_card_number') OR (TABLE_NAME = 'households' AND COLUMN_NAME = 'family_card_digest') OR (TABLE_NAME = 'residents' AND COLUMN_NAME = 'national_id') OR (TABLE_NAME = 'residents' AND COLUMN_NAME = 'national_id_digest') OR (TABLE_NAME = 'residents' AND COLUMN_NAME = 'birthplace') OR (TABLE_NAME = 'residents' AND COLUMN_NAME = 'birth_date') OR (TABLE_NAME = 'residents' AND COLUMN_NAME = 'gender') OR (TABLE_NAME = 'residents' AND COLUMN_NAME = 'religion') OR (TABLE_NAME = 'residents' AND COLUMN_NAME = 'marital_status') OR (TABLE_NAME = 'residents' AND COLUMN_NAME = 'education') OR (TABLE_NAME = 'residents' AND COLUMN_NAME = 'occupation') OR (TABLE_NAME = 'residents' AND COLUMN_NAME = 'residency_status'))
ORDER BY TABLE_NAME, ORDINAL_POSITION;

SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE() AND INDEX_NAME IN ('households_family_card_digest_unique', 'residents_national_id_digest_unique')
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;
