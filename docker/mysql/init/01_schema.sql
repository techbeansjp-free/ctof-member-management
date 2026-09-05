-- docs/TASKS/001-010/001-member-skill-management-system/02_基本設計.md §8.1
-- このファイルは db_data volume が空のときにだけ実行される。
SET NAMES utf8mb4;

CREATE TABLE admin_users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  login_id      VARCHAR(64)  NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admin_users_login_id (login_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE members (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(100) NOT NULL,
  avatar_path VARCHAR(255) NULL,
  bio         TEXT         NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_members_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE skill_categories (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(50)  NOT NULL,
  sort_order INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_skill_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE skills (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id INT UNSIGNED NOT NULL,
  name        VARCHAR(100) NOT NULL,
  sort_order  INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_skills_name (name),
  KEY idx_skills_category (category_id, sort_order),
  CONSTRAINT fk_skills_category FOREIGN KEY (category_id)
    REFERENCES skill_categories (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE member_skills (
  member_id INT UNSIGNED     NOT NULL,
  skill_id  INT UNSIGNED     NOT NULL,
  level     TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (member_id, skill_id),
  KEY idx_member_skills_lookup (skill_id, level),
  CONSTRAINT fk_ms_member FOREIGN KEY (member_id)
    REFERENCES members (id) ON DELETE CASCADE,
  CONSTRAINT fk_ms_skill FOREIGN KEY (skill_id)
    REFERENCES skills (id) ON DELETE CASCADE,
  CONSTRAINT chk_ms_level CHECK (level BETWEEN 1 AND 3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- docs/.../04_改修_メンバー個人ログイン.md
-- メンバー本人のログイン情報。admin_users とは別テーブル (責務分離)。
-- migrations/001_add_member_credentials.sql と同一内容。00_migrations.sql も参照。
CREATE TABLE member_credentials (
  member_id     INT UNSIGNED NOT NULL,
  login_id      VARCHAR(64)  NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (member_id),
  UNIQUE KEY uq_member_credentials_login_id (login_id),
  CONSTRAINT fk_mc_member FOREIGN KEY (member_id)
    REFERENCES members (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
