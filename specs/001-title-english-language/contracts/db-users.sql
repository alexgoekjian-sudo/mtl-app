-- DB user setup for Retool (example). Replace placeholders before running.
-- STAGING: read-only user for dashboards
CREATE USER IF NOT EXISTS 'mtl_retool_ro'@'%' IDENTIFIED BY 'STRONG_STAGING_PASSWORD';
GRANT SELECT ON mtl_app_staging.* TO 'mtl_retool_ro'@'%';

-- STAGING: limited write user for operational pages (e.g. enrollments)
CREATE USER IF NOT EXISTS 'mtl_retool_rw'@'%' IDENTIFIED BY 'STRONG_STAGING_WRITE_PASSWORD';
GRANT SELECT ON mtl_app_staging.* TO 'mtl_retool_rw'@'%';
GRANT INSERT, UPDATE, DELETE ON mtl_app_staging.enrollments TO 'mtl_retool_rw'@'%';
GRANT INSERT, UPDATE, DELETE ON mtl_app_staging.attendance_records TO 'mtl_retool_rw'@'%';

-- Production (stronger restrictions, single-host binding)
CREATE USER IF NOT EXISTS 'mtl_retool_ro_prod'@'10.0.0.%' IDENTIFIED BY 'STRONG_PROD_PASSWORD';
GRANT SELECT ON mtl_app_prod.* TO 'mtl_retool_ro_prod'@'10.0.0.%';

CREATE USER IF NOT EXISTS 'mtl_retool_rw_prod'@'10.0.0.%' IDENTIFIED BY 'STRONG_PROD_WRITE_PASSWORD';
GRANT SELECT ON mtl_app_prod.* TO 'mtl_retool_rw_prod'@'10.0.0.%';
GRANT INSERT, UPDATE, DELETE ON mtl_app_prod.enrollments TO 'mtl_retool_rw_prod'@'10.0.0.%';
GRANT INSERT, UPDATE, DELETE ON mtl_app_prod.attendance_records TO 'mtl_retool_rw_prod'@'10.0.0.%';

FLUSH PRIVILEGES;

-- Notes:
-- 1) Replace host '%' with specific IP(s) or network ranges where possible.
-- 2) Use strong passwords and rotate them via Retool secrets manager and your password store.
-- 3) Consider creating read-only views for heavy aggregated queries and grant SELECT on views only.
