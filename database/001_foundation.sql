-- Aplicar en una base de datos exclusiva del proyecto. No contiene usuarios ni contraseñas iniciales.
CREATE TABLE IF NOT EXISTS schema_migrations (
 version VARCHAR(64) PRIMARY KEY,
 applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO schema_migrations (version) VALUES ('001_foundation');
