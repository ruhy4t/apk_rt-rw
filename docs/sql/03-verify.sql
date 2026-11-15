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
