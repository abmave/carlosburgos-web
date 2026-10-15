ALTER TABLE cms_audit
 ADD COLUMN entity_id BIGINT UNSIGNED NULL,
 ADD COLUMN metadata LONGTEXT NULL CHECK (metadata IS NULL OR JSON_VALID(metadata));
INSERT INTO schema_migrations(version) VALUES ('004_audit_context');
