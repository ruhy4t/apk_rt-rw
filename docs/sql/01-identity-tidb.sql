-- APK-RT-RW: additive schema only; no citizen records or credentials.
-- Select the correct database first in your SQL client. No USE is hard-coded.
-- Verify a restorable backup and test on matching non-production engine/version.
-- Run serially and STOP on the first error. DDL is not one rollbackable transaction.
-- Existing column/index definitions are not silently rewritten; inspect postflight.
-- This SQL does NOT encrypt/backfill data or enable the application privacy flag.

-- TiDB syntax (IF NOT EXISTS extensions), not a MySQL 8 script.

ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `must_change_password` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `households` ADD COLUMN IF NOT EXISTS `family_card_number` TEXT NULL;
ALTER TABLE `households` ADD COLUMN IF NOT EXISTS `family_card_digest` CHAR(64) NULL;
ALTER TABLE `residents` ADD COLUMN IF NOT EXISTS `national_id` TEXT NULL;
ALTER TABLE `residents` ADD COLUMN IF NOT EXISTS `national_id_digest` CHAR(64) NULL;
ALTER TABLE `residents` ADD COLUMN IF NOT EXISTS `birthplace` VARCHAR(160) NULL;
ALTER TABLE `residents` ADD COLUMN IF NOT EXISTS `birth_date` DATE NULL;
ALTER TABLE `residents` ADD COLUMN IF NOT EXISTS `gender` VARCHAR(16) NULL;
ALTER TABLE `residents` ADD COLUMN IF NOT EXISTS `religion` TEXT NULL;
ALTER TABLE `residents` ADD COLUMN IF NOT EXISTS `marital_status` VARCHAR(32) NULL;
ALTER TABLE `residents` ADD COLUMN IF NOT EXISTS `education` VARCHAR(80) NULL;
ALTER TABLE `residents` ADD COLUMN IF NOT EXISTS `occupation` VARCHAR(160) NULL;
ALTER TABLE `residents` ADD COLUMN IF NOT EXISTS `residency_status` VARCHAR(32) NULL;

-- If a digest is duplicated, unique-index creation stops; no records are removed.
CREATE UNIQUE INDEX IF NOT EXISTS `households_family_card_digest_unique` ON `households` (`family_card_digest`);
CREATE UNIQUE INDEX IF NOT EXISTS `residents_national_id_digest_unique` ON `residents` (`national_id_digest`);

-- Postflight: compare every returned type/length/nullability with the file above.
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND ((TABLE_NAME = 'users' AND COLUMN_NAME = 'must_change_password') OR (TABLE_NAME = 'households' AND COLUMN_NAME = 'family_card_number') OR (TABLE_NAME = 'households' AND COLUMN_NAME = 'family_card_digest') OR (TABLE_NAME = 'residents' AND COLUMN_NAME = 'national_id') OR (TABLE_NAME = 'residents' AND COLUMN_NAME = 'national_id_digest') OR (TABLE_NAME = 'residents' AND COLUMN_NAME = 'birthplace') OR (TABLE_NAME = 'residents' AND COLUMN_NAME = 'birth_date') OR (TABLE_NAME = 'residents' AND COLUMN_NAME = 'gender') OR (TABLE_NAME = 'residents' AND COLUMN_NAME = 'religion') OR (TABLE_NAME = 'residents' AND COLUMN_NAME = 'marital_status') OR (TABLE_NAME = 'residents' AND COLUMN_NAME = 'education') OR (TABLE_NAME = 'residents' AND COLUMN_NAME = 'occupation') OR (TABLE_NAME = 'residents' AND COLUMN_NAME = 'residency_status'))
ORDER BY TABLE_NAME, ORDINAL_POSITION;

SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE() AND INDEX_NAME IN ('households_family_card_digest_unique', 'residents_national_id_digest_unique')
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;
