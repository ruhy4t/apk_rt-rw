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
