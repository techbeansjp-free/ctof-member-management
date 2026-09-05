-- マイグレーションの適用記録。
-- このファイルは db_data volume が空のときにだけ実行される。
--
-- 01_schema.sql に既に反映済みのマイグレーションは、まっさらな環境で
-- 二重に流れないよう、ここに 1 行ずつ記録しておくこと (README 参照)。
SET NAMES utf8mb4;

CREATE TABLE schema_migrations (
  version    VARCHAR(255) NOT NULL,
  applied_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 01_schema.sql に取り込み済みのマイグレーション:
INSERT INTO schema_migrations (version) VALUES ('001_add_member_credentials');
