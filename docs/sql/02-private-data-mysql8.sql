-- APK-RT-RW: additive schema only; no citizen records or credentials.
-- Select the correct database first in your SQL client. No USE is hard-coded.
-- Verify a restorable backup and test on matching non-production engine/version.
-- Run serially and STOP on the first error. DDL is not one rollbackable transaction.
-- Existing column/index definitions are not silently rewritten; inspect postflight.
-- This SQL does NOT encrypt/backfill data or enable the application privacy flag.

-- MySQL 8 only. Existing private_data TEXT is not widened automatically.

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'private_data'),
    'SELECT ''SKIP: users.private_data already exists; verify its definition'' AS status',
    'ALTER TABLE `users` ADD COLUMN `private_data` LONGTEXT NULL'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'households' AND COLUMN_NAME = 'private_data'),
    'SELECT ''SKIP: households.private_data already exists; verify its definition'' AS status',
    'ALTER TABLE `households` ADD COLUMN `private_data` LONGTEXT NULL'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'residents' AND COLUMN_NAME = 'private_data'),
    'SELECT ''SKIP: residents.private_data already exists; verify its definition'' AS status',
    'ALTER TABLE `residents` ADD COLUMN `private_data` LONGTEXT NULL'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'service_requests' AND COLUMN_NAME = 'private_data'),
    'SELECT ''SKIP: service_requests.private_data already exists; verify its definition'' AS status',
    'ALTER TABLE `service_requests` ADD COLUMN `private_data` LONGTEXT NULL'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_loans' AND COLUMN_NAME = 'private_data'),
    'SELECT ''SKIP: asset_loans.private_data already exists; verify its definition'' AS status',
    'ALTER TABLE `asset_loans` ADD COLUMN `private_data` LONGTEXT NULL'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contributions' AND COLUMN_NAME = 'private_data'),
    'SELECT ''SKIP: contributions.private_data already exists; verify its definition'' AS status',
    'ALTER TABLE `contributions` ADD COLUMN `private_data` LONGTEXT NULL'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'financial_entries' AND COLUMN_NAME = 'private_data'),
    'SELECT ''SKIP: financial_entries.private_data already exists; verify its definition'' AS status',
    'ALTER TABLE `financial_entries` ADD COLUMN `private_data` LONGTEXT NULL'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stored_files' AND COLUMN_NAME = 'private_data'),
    'SELECT ''SKIP: stored_files.private_data already exists; verify its definition'' AS status',
    'ALTER TABLE `stored_files` ADD COLUMN `private_data` LONGTEXT NULL'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;


-- Postflight: compare every returned type/length/nullability with the file above.
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND ((TABLE_NAME = 'users' AND COLUMN_NAME = 'private_data') OR (TABLE_NAME = 'households' AND COLUMN_NAME = 'private_data') OR (TABLE_NAME = 'residents' AND COLUMN_NAME = 'private_data') OR (TABLE_NAME = 'service_requests' AND COLUMN_NAME = 'private_data') OR (TABLE_NAME = 'asset_loans' AND COLUMN_NAME = 'private_data') OR (TABLE_NAME = 'contributions' AND COLUMN_NAME = 'private_data') OR (TABLE_NAME = 'financial_entries' AND COLUMN_NAME = 'private_data') OR (TABLE_NAME = 'stored_files' AND COLUMN_NAME = 'private_data'))
ORDER BY TABLE_NAME, ORDINAL_POSITION;

-- Expected: eight rows; every private_data is nullable LONGTEXT.
