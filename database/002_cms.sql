CREATE TABLE IF NOT EXISTS cms_users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('admin','editor') NOT NULL DEFAULT 'editor',
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS site_settings (
 id TINYINT UNSIGNED PRIMARY KEY,
 content LONGTEXT NOT NULL CHECK (JSON_VALID(content)),
 version INT UNSIGNED NOT NULL DEFAULT 1,
 updated_by BIGINT UNSIGNED NULL,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (updated_by) REFERENCES cms_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS login_limits (
 ip_key CHAR(64) PRIMARY KEY,
 attempts INT UNSIGNED NOT NULL DEFAULT 0,
 window_start BIGINT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS cms_audit (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 action VARCHAR(80) NOT NULL,
 settings_version INT UNSIGNED NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES cms_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO site_settings (id, content) VALUES (1, '{"brandName":"Carlos Burgos Huapaya","heroTitle":"Su tranquilidad.","heroAccent":"Nuestra prioridad.","heroDescription":"Asesoría legal y administrativa con atención personalizada, claridad y compromiso.","studyTitle":"El primer paso es entender su situación.","studyLead":"Cada consulta merece tiempo, atención y una explicación comprensible.","studyDescription":"Un espacio para conocer sus necesidades, evaluar las alternativas y definir los siguientes pasos de su atención legal.","contactEmail":"","contactPhone":"","whatsapp":"","address":"","schedule":""}');
INSERT IGNORE INTO schema_migrations (version) VALUES ('002_cms');
