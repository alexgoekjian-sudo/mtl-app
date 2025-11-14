-- Migration: create example users for Retool access (run as DBA)
-- This is a SQL migration file for documentation; run via your DB migration tooling or via mysql CLI.

-- Note: Do NOT commit real passwords in production. Replace placeholders before executing.

CREATE USER IF NOT EXISTS 'mtl_retool_ro'@'%' IDENTIFIED BY 'CHANGE_ME_RO_STAGING';
GRANT SELECT ON mtl_app_staging.* TO 'mtl_retool_ro'@'%';

CREATE USER IF NOT EXISTS 'mtl_retool_rw'@'%' IDENTIFIED BY 'CHANGE_ME_RW_STAGING';
GRANT SELECT ON mtl_app_staging.* TO 'mtl_retool_rw'@'%';
GRANT INSERT, UPDATE, DELETE ON mtl_app_staging.enrollments TO 'mtl_retool_rw'@'%';
GRANT INSERT, UPDATE, DELETE ON mtl_app_staging.attendance_records TO 'mtl_retool_rw'@'%';

FLUSH PRIVILEGES;
