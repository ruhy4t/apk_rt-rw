-- UPDATE DATABASE APLIKASI RT/RW - SATU FILE UNTUK MYSQL DAN TiDB
-- Target: MySQL 8+ (Laragon) dan TiDB dengan foreign key aktif (6.6+).
-- Ini paket UPDATE struktur pada database aplikasi yang sudah terpasang,
-- BUKAN installer, dump lengkap, atau proses enkripsi data lama.
-- Pilih database aplikasi yang benar di HeidiSQL/phpMyAdmin terlebih dahulu.
-- File yang SAMA dijalankan terpisah pada koneksi lokal dan produksi;
-- menjalankan di Laragon TIDAK memperbarui TiDB, dan sebaliknya.
-- Siapkan backup yang teruji dipulihkan, lalu uji pada salinan non-production.
-- Jalankan seluruh file melalui SATU koneksi, serial, STOP pada error pertama.
-- Jangan jalankan bersamaan dengan migrasi atau import SQL lain.
-- DDL tidak menjadi satu transaksi yang dapat di-rollback seluruhnya.
-- Isi: pemeriksaan awal, akun/identitas warga, kolom enkripsi, tabel galeri,
-- nama Ketua RT/RW, identitas penandatangan saat penerbitan, dan pemeriksaan
-- hasil. Nama kolom/indeks/tabel yang ada akan dilewati.
-- Definisi yang sudah ada tidak diam-diam diperbaiki; periksa hasil query.
-- Tidak ada penghapusan/perubahan isi record warga atau perubahan secret.
-- TIDAK mengaktifkan enkripsi aplikasi atau mengenkripsi data lama.
-- TIDAK mencatat tabel migrations; rekonsiliasikan sebelum artisan migrate.
-- PREPARE/EXECUTE dan information_schema dipakai agar satu sintaks berlaku
-- di MySQL serta TiDB, tanpa ADD COLUMN IF NOT EXISTS khusus TiDB.
-- Rujukan: https://docs.pingcap.com/tidb/stable/sql-statement-prepare/
-- https://dev.mysql.com/doc/refman/8.0/en/sql-prepared-statements.html

-- ============================================================
-- 00-preflight.sql (sintaks bersama MySQL/TiDB)
-- ============================================================

-- Read-only; works on MySQL/TiDB. Select the intended database in your client.
SELECT VERSION() AS server_version, DATABASE() AS selected_database;

-- Expected base tables must already exist. This is not a fresh-install schema.
SELECT TABLE_NAME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('users','rw_areas','rt_areas','households','residents','service_requests','asset_loans','contributions','financial_entries','stored_files','migrations')
ORDER BY TABLE_NAME;

-- Metadata only; no names, identity values, credentials, or citizen rows.
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND (COLUMN_NAME IN ('private_data','family_card_number','family_card_digest','national_id','national_id_digest','must_change_password'))
ORDER BY TABLE_NAME, ORDINAL_POSITION;

SELECT COUNT(*) AS rw_count FROM rw_areas;
-- If rw_count > 1: stop and ask which RW stays active. Do not merge/delete RWs.

-- ============================================================
-- 01-identity.sql (sintaks bersama MySQL/TiDB)
-- ============================================================

-- APK-RT-RW: additive schema only; no citizen records or credentials.
-- Select the correct database first in your SQL client. No USE is hard-coded.
-- Verify a restorable backup and test on matching non-production engine/version.
-- Run serially and STOP on the first error. DDL is not one rollbackable transaction.
-- Existing column/index definitions are not silently rewritten; inspect postflight.
-- This SQL does NOT encrypt/backfill data or enable the application privacy flag.


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

-- ============================================================
-- 02-private-data.sql (sintaks bersama MySQL/TiDB)
-- ============================================================

-- APK-RT-RW: additive schema only; no citizen records or credentials.
-- Select the correct database first in your SQL client. No USE is hard-coded.
-- Verify a restorable backup and test on matching non-production engine/version.
-- Run serially and STOP on the first error. DDL is not one rollbackable transaction.
-- Existing column/index definitions are not silently rewritten; inspect postflight.
-- This SQL does NOT encrypt/backfill data or enable the application privacy flag.

-- Existing private_data TEXT is not widened automatically.

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

-- ============================================================
-- 04-gallery.sql (sintaks bersama MySQL/TiDB)
-- ============================================================

-- Referenced tables require compatible unsigned IDs; MySQL tables must use InnoDB.
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

-- ============================================================
-- 03-verify.sql (sintaks bersama MySQL/TiDB)
-- ============================================================

-- Read-only verification after schema application; no personal values returned.
SELECT TABLE_NAME, COLUMN_TYPE, IS_NULLABLE,
       CASE WHEN DATA_TYPE = 'longtext' AND IS_NULLABLE = 'YES' THEN 'OK' ELSE 'REVIEW_REQUIRED' END AS private_data_status
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'private_data'
  AND TABLE_NAME IN ('users','households','residents','service_requests','asset_loans','contributions','financial_entries','stored_files')
ORDER BY TABLE_NAME;
-- Expected: exactly 8 rows, all OK. Missing rows are an incomplete schema.

SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND INDEX_NAME IN ('households_family_card_digest_unique','residents_national_id_digest_unique')
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;
-- Expected: each index has one row on the matching digest and NON_UNIQUE = 0.

SELECT COUNT(*) AS duplicate_family_digest_groups
FROM (SELECT 1 FROM households WHERE family_card_digest IS NOT NULL GROUP BY family_card_digest HAVING COUNT(*) > 1) AS conflicts;
SELECT COUNT(*) AS duplicate_nik_digest_groups
FROM (SELECT 1 FROM residents WHERE national_id_digest IS NOT NULL GROUP BY national_id_digest HAVING COUNT(*) > 1) AS conflicts;
-- Both counts must be zero. Do not print conflicting digest values or auto-delete.

-- ============================================================
-- PENANDATANGAN RT/RW - nama ketua dan identitas saat penerbitan
-- ============================================================
-- Seluruh kolom berikut menampung ciphertext aplikasi, bukan nama plaintext.
-- Tidak ada pengisian otomatis ketua atau perubahan record lama.

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rt_areas' AND COLUMN_NAME = 'chairman_name'),
    'SELECT ''SKIP: rt_areas.chairman_name already exists; verify its definition'' AS status',
    'ALTER TABLE `rt_areas` ADD COLUMN `chairman_name` LONGTEXT NULL'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rw_areas' AND COLUMN_NAME = 'chairman_name'),
    'SELECT ''SKIP: rw_areas.chairman_name already exists; verify its definition'' AS status',
    'ALTER TABLE `rw_areas` ADD COLUMN `chairman_name` LONGTEXT NULL'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'service_requests' AND COLUMN_NAME = 'signatory_data'),
    'SELECT ''SKIP: service_requests.signatory_data already exists; verify its definition'' AS status',
    'ALTER TABLE `service_requests` ADD COLUMN `signatory_data` LONGTEXT NULL'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'announcements' AND COLUMN_NAME = 'signatory_data'),
    'SELECT ''SKIP: announcements.signatory_data already exists; verify its definition'' AS status',
    'ALTER TABLE `announcements` ADD COLUMN `signatory_data` LONGTEXT NULL'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SET @apk_rt_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'community_events' AND COLUMN_NAME = 'signatory_data'),
    'SELECT ''SKIP: community_events.signatory_data already exists; verify its definition'' AS status',
    'ALTER TABLE `community_events` ADD COLUMN `signatory_data` LONGTEXT NULL'
);
PREPARE apk_rt_stmt FROM @apk_rt_ddl;
EXECUTE apk_rt_stmt;
DEALLOCATE PREPARE apk_rt_stmt;

SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE,
       CASE WHEN DATA_TYPE = 'longtext' AND IS_NULLABLE = 'YES' THEN 'OK' ELSE 'REVIEW_REQUIRED' END AS signatory_schema_status
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND ((TABLE_NAME IN ('rt_areas','rw_areas') AND COLUMN_NAME = 'chairman_name')
       OR (TABLE_NAME IN ('service_requests','announcements','community_events') AND COLUMN_NAME = 'signatory_data'))
ORDER BY TABLE_NAME, COLUMN_NAME;
-- Expected: exactly five rows, every row OK. Missing rows require completion.
