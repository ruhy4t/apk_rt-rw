-- APK-RT-RW: additive schema only; no citizen records or credentials.
-- Select the correct database first in your SQL client. No USE is hard-coded.
-- Verify a restorable backup and test on matching non-production engine/version.
-- Run serially and STOP on the first error. DDL is not one rollbackable transaction.
-- Existing column/index definitions are not silently rewritten; inspect postflight.
-- This SQL does NOT encrypt/backfill data or enable the application privacy flag.

-- TiDB only. Existing private_data TEXT is not widened automatically.

ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `private_data` LONGTEXT NULL;
ALTER TABLE `households` ADD COLUMN IF NOT EXISTS `private_data` LONGTEXT NULL;
ALTER TABLE `residents` ADD COLUMN IF NOT EXISTS `private_data` LONGTEXT NULL;
ALTER TABLE `service_requests` ADD COLUMN IF NOT EXISTS `private_data` LONGTEXT NULL;
ALTER TABLE `asset_loans` ADD COLUMN IF NOT EXISTS `private_data` LONGTEXT NULL;
ALTER TABLE `contributions` ADD COLUMN IF NOT EXISTS `private_data` LONGTEXT NULL;
ALTER TABLE `financial_entries` ADD COLUMN IF NOT EXISTS `private_data` LONGTEXT NULL;
ALTER TABLE `stored_files` ADD COLUMN IF NOT EXISTS `private_data` LONGTEXT NULL;

-- Postflight: compare every returned type/length/nullability with the file above.
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND ((TABLE_NAME = 'users' AND COLUMN_NAME = 'private_data') OR (TABLE_NAME = 'households' AND COLUMN_NAME = 'private_data') OR (TABLE_NAME = 'residents' AND COLUMN_NAME = 'private_data') OR (TABLE_NAME = 'service_requests' AND COLUMN_NAME = 'private_data') OR (TABLE_NAME = 'asset_loans' AND COLUMN_NAME = 'private_data') OR (TABLE_NAME = 'contributions' AND COLUMN_NAME = 'private_data') OR (TABLE_NAME = 'financial_entries' AND COLUMN_NAME = 'private_data') OR (TABLE_NAME = 'stored_files' AND COLUMN_NAME = 'private_data'))
ORDER BY TABLE_NAME, ORDINAL_POSITION;

-- Expected: eight rows; every private_data is nullable LONGTEXT.
