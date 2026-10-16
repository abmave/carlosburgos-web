ALTER TABLE cms_users ADD COLUMN session_version INT UNSIGNED NOT NULL DEFAULT 1;
CREATE TABLE editorial_content (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 kind VARCHAR(24) NOT NULL,
 slug VARCHAR(160) NOT NULL,
 title VARCHAR(200) NOT NULL,
 content LONGTEXT NOT NULL CHECK(JSON_VALID(content)),
 status ENUM('draft','review','approved','published','withdrawn') NOT NULL DEFAULT 'draft',
 sort_order INT NOT NULL DEFAULT 0,
 version INT UNSIGNED NOT NULL DEFAULT 1,
 approved_by BIGINT UNSIGNED NULL,
 approved_at DATETIME NULL,
 approval LONGTEXT NULL,
 updated_by BIGINT UNSIGNED NOT NULL,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY kind_slug(kind,slug),
 INDEX publication(kind,status,sort_order),
 FOREIGN KEY (approved_by) REFERENCES cms_users(id),
 FOREIGN KEY (updated_by) REFERENCES cms_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE media_files (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 filename VARCHAR(80) NOT NULL UNIQUE,
 mime VARCHAR(40) NOT NULL,
 size INT UNSIGNED NOT NULL,
 title VARCHAR(200) NOT NULL,
 alt VARCHAR(300) NOT NULL DEFAULT '',
 private_case_id BIGINT UNSIGNED NULL,
 version INT UNSIGNED NOT NULL DEFAULT 1,
 created_by BIGINT UNSIGNED NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(private_case_id) REFERENCES editorial_content(id),
 FOREIGN KEY(created_by) REFERENCES cms_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE password_resets (
 token_hash CHAR(64) PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 expires_at DATETIME NOT NULL,
 FOREIGN KEY(user_id) REFERENCES cms_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO schema_migrations(version) VALUES ('003_editorial');
