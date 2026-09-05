-- 04_改修_メンバー個人ログイン.md
-- メンバー本人がログインして自分の情報を編集できるようにするための認証情報。
-- admin_users とは別テーブルにする (責務分離。設計書参照)。
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
