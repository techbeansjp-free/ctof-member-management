#!/bin/bash
# デプロイ後フック。サーバー上でコンテナが healthy になった後に実行される。
#
#   非ゼロで終了 → デプロイ失敗扱い → 直前のコミットへ自動ロールバック
#   タイムアウト → 300秒 (インフラ側の設定)
#
# 未適用のマイグレーションを昇順に適用する。適用済みは schema_migrations で管理し、
# 同じコミットを何度デプロイしても二重に流れないようにする。
#
# 注意: ロールバックしてもマイグレーションは巻き戻らない。
#       スキーマ変更は前方互換に保つこと (規約は README を参照)。
set -uo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.." || { echo "❌ リポジトリルートへ移動できない"; exit 1; }
MIGRATIONS_DIR=docker/mysql/migrations

echo "──────── マイグレーション ────────"

if [ ! -f .env ]; then
  echo "❌ .env が見つからない (カレント: $(pwd))"
  exit 1
fi
set -a; . ./.env; set +a

if [ -z "${DB_ROOT_PASS:-}" ]; then
  echo "❌ .env に DB_ROOT_PASS が無い"
  exit 1
fi

# パスワード警告だけを落とす。それ以外のエラーは残す
db() {
  docker compose exec -T db mysql --default-character-set=utf8mb4 \
    -uroot -p"$DB_ROOT_PASS" -N -B skillmap "$@" \
    2> >(grep -v 'Using a password on the command line' >&2)
}

if ! db -e 'SELECT 1' >/dev/null; then
  echo "❌ db に接続できない"
  exit 1
fi

db -e "CREATE TABLE IF NOT EXISTS schema_migrations (
         version    VARCHAR(255) NOT NULL,
         applied_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
         PRIMARY KEY (version)
       ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;" || {
  echo "❌ schema_migrations を用意できない"; exit 1
}

APPLIED=$(db -e 'SELECT version FROM schema_migrations')

shopt -s nullglob
FILES=("$MIGRATIONS_DIR"/*.sql)
shopt -u nullglob

if [ ${#FILES[@]} -eq 0 ]; then
  echo "  マイグレーションファイルなし。適用済み $(echo "$APPLIED" | grep -c . || true) 件"
  echo "──────── 変更なし ────────"
  exit 0
fi

count=0
for file in "${FILES[@]}"; do
  version=$(basename "$file" .sql)

  # ファイル名は 3桁連番 + アンダースコア + 英数字。想定外の名前は流さない
  if ! [[ "$version" =~ ^[0-9]{3}_[A-Za-z0-9_-]+$ ]]; then
    echo "❌ ファイル名が規約に合わない: $version (例: 001_add_department_to_members)"
    exit 1
  fi

  if echo "$APPLIED" | grep -qxF "$version"; then
    echo "  スキップ : $version (適用済み)"
    continue
  fi

  echo "  適用     : $version"
  if ! db < "$file"; then
    echo "❌ $version の適用に失敗した。ここで中断する"
    echo "   MySQL の DDL はロールバックできないため、途中まで適用されている可能性がある。"
    echo "   schema_migrations には記録していないので、修正後に再デプロイすれば再実行される。"
    exit 1
  fi

  # 適用が成功してから記録する
  if ! db -e "INSERT INTO schema_migrations (version) VALUES ('$version')"; then
    echo "❌ $version は適用できたが schema_migrations に記録できなかった"
    echo "   次回のデプロイで二重適用になる。手で INSERT すること"
    exit 1
  fi
  count=$((count + 1))
done

echo "──────── $count 件を適用した ────────"
exit 0
