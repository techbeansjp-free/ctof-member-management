#!/bin/bash
# デプロイ後フック。
# サーバー上で、コンテナが healthy になった後に実行される。
# 非ゼロで終了するとデプロイ失敗扱いになり、直前のコミットへ自動ロールバックされる。
#
# 第1版: 環境を調べて出力するだけ。マイグレーション本体は結果を見てから実装する。
#        必ず exit 0 で終わる。
set -uo pipefail

say() { printf '  %-16s: %s\n' "$1" "$2"; }

echo "──────── after-deploy.sh (プローブ版) ────────"
say "日時"           "$(date '+%Y-%m-%d %H:%M:%S %Z')"
say "実行ユーザー"     "$(id -un)"
say "カレント"        "$(pwd)"
say "スクリプト"      "${BASH_SOURCE[0]}"
say "bash"           "${BASH_VERSION}"
say "git"            "$(git rev-parse --short HEAD 2>/dev/null || echo '取得できず')"
say ".env"           "$([ -f .env ] && echo 'カレントから見える' || echo '見えない')"

# マイグレーションは DB のパスワードを要る。環境変数で渡ってくるのか、
# 自分で .env を読む必要があるのかで実装が変わるので、ここで切り分ける。
say "DB_ROOT_PASS"   "$([ -n "${DB_ROOT_PASS:-}" ] && echo '環境変数として渡っている' || echo '渡っていない')"
say "docker"         "$(docker compose version --short 2>/dev/null || echo '使えない')"
say "db コンテナ"     "$(docker compose ps --format '{{.Service}} {{.State}}' 2>/dev/null | grep '^db ' || echo '見えない')"

# 実際にクエリを投げられるかまで見る。ここが通らないとマイグレーションは書けない。
if [ -f .env ]; then
  set -a; . ./.env; set +a
  if docker compose exec -T db mysql -uroot -p"${DB_ROOT_PASS:-}" -N -B -e 'SELECT 1' skillmap >/dev/null 2>&1; then
    say "db へのクエリ"  "成功 (.env を読み込めば通る)"
    say "既存テーブル"    "$(docker compose exec -T db mysql -uroot -p"${DB_ROOT_PASS:-}" -N -B \
                              -e 'SHOW TABLES' skillmap 2>/dev/null | tr '\n' ' ')"
  else
    say "db へのクエリ"  "失敗"
  fi
fi

echo "──────── プローブ版のため何も変更しない。正常終了する ────────"
exit 0
