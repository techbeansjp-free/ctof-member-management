# skillmap — メンバースキル管理

シートエフのメンバーが持つスキルを一覧し、**スキルから人を逆引きする**ための社内ツール。

- 公開先: https://skillmap.techbeans.info
- 要求・要件・基本設計: リポジトリ外の `docs/TASKS/001-010/001-member-skill-management-system/` (git 管理外)

## 何を解くツールか

案件アサインを決めるとき「React できる人は誰か」を Slack で聞いて回らずに済ませること。
**スキルを登録できることではなく、絞り込めることが目的。** 一覧の絞り込み条件は URL に載るので、
そのまま Slack に貼って共有できる。

例: `/?skill[]=14&skill[]=3&level=2` = React と TypeScript を両方、実務経験あり以上で持つ人

## 構成

| 項目 | 内容 |
|---|---|
| 言語 | PHP 8.3 (Composer 依存なし) |
| Web | Apache (php:8.3-apache) |
| DB | MySQL 8.0 |
| 実行 | Docker Compose |

**フレームワークを使っていない。** デプロイスクリプトが `.env` のキーをランダム値で埋める仕様のため、
`APP_KEY` のように形式が決まった値を要求するフレームワークが噛み合わないことによる判断。
そのぶんルーティング・認証・エスケープ・CSRF は自前。`src/` の各クラスがその担当。

```
docker/php/          Dockerfile / Apache 設定 / php.ini
docker/mysql/init/   初回起動時に流れる DDL とシード
public/              DocumentRoot。index.php が唯一の入口
src/                 アプリ本体
storage/avatars/     アップロード画像 (DocumentRoot の外。volume で永続化)
views/               表示のみ。SQL を書かない
```

## ローカルで動かす

```bash
cat > .env <<EOT
DB_PASS=$(openssl rand -hex 24)
DB_ROOT_PASS=$(openssl rand -hex 24)
ADMIN_PASSWORD=localpassword
EOT
chmod 600 .env

docker compose up -d --build
open http://localhost/          # ログインID: admin
```

スキーマを変えたときは、マイグレーションを流すか volume を作り直す。

```bash
bash deploy/after-deploy.sh          # 未適用のマイグレーションを流す
docker compose down -v && docker compose up -d   # まっさらから作り直す
```

## デプロイ

インフラ側の `20-deploy.sh` が `git clone` → `docker compose up -d --build` する。
**アプリは 80 番のみ公開する。HTTPS (443 / 証明書) はインフラ側が `docker-compose.override.yml` で被せる。**

- `.env` はサーバー上で自動生成される。**リポジトリに置かない**
- `.env.example` に書いたキーは**ランダムな16進48文字で埋められる**。値を選べないキー (秘密情報) だけを置くこと。
  `DB_HOST` / `DB_NAME` / `DB_USER` のような固定値は `docker-compose.yml` の `environment` に持たせている
- `docker/php/vhost.conf` はインフラ側が生成する。アプリの Apache 設定は `docker/php/apache-http.conf` (名前が衝突すると `git pull --ff-only` が落ちる)

## スキーマを変更する

`docker/mysql/init/*.sql` は **db_data volume が空のときにしか走らない。**
稼働中の環境にスキーマ変更を届けるのはマイグレーションの役目で、
`deploy/after-deploy.sh` がデプロイのたびに未適用ぶんを流す。適用済みは `schema_migrations` で管理している。

### 手順 (3つとも必要)

1. **`docker/mysql/migrations/NNN_内容.sql` を追加する**
   連番は3桁。ファイル名は `[0-9]{3}_[A-Za-z0-9_-]+`。規約外の名前はデプロイ時に弾かれる
2. **同じ変更を `docker/mysql/init/01_schema.sql` にも入れる**
   まっさらな環境はマイグレーションではなくこちらから作られるため
3. **`docker/mysql/init/00_migrations.sql` に 1 行足す**
   ```sql
   INSERT INTO schema_migrations (version) VALUES ('003_add_department_to_members');
   ```
   これが無いと、まっさらな環境で 2 の変更が入った状態のまま同じマイグレーションが流れて失敗する

3 を忘れるとローカルの `docker compose down -v` で気づく。**スキーマを変えたら一度まっさらから起動して確認すること。**

### 守ること

- **前方互換に保つ。列の追加のみ。削除・リネーム・NOT NULL 化はしない。**
  デプロイが失敗すると直前のコミットへ自動ロールバックするが、**マイグレーションは巻き戻らない。**
  古いコードと新しいスキーマが同居する瞬間があるので、古いコードが動かなくなる変更を入れない
- **1 ファイル 1 ステートメント。**
  MySQL の DDL はトランザクションで戻せない。複数文を書くと、途中で失敗したとき手で直すことになる
- データの投入 (INSERT) もマイグレーションに書いてよい。スキルマスタの追加はここが定位置

### 失敗したとき

適用に失敗したマイグレーションは `schema_migrations` に記録されない。
SQL を直して再デプロイすれば、そこから再実行される。
ただし DDL が途中まで適用されている場合があるので、**再実行の前に DB の状態を確認すること。**

## 運用

### 管理画面に入る

ログインIDは `admin` 固定。パスワードはサーバー上の `.env` にある。

```bash
grep ADMIN_PASSWORD ~/skillmap/.env
```

デプロイ時に自動生成される 48 文字のランダム文字列で、**画面から変更する機能はない**。
変えるときは `.env` を書き換えて再デプロイする。

### スキルを追加する

**画面から追加できない。** 表記ゆれ (React / ReactJS / react.js) で絞り込みが壊れるのを防ぐため、
マスタは DB でのみ管理している。**マイグレーションとして追加する** (上の「スキーマを変更する」の手順に従う)。

```sql
-- docker/mysql/migrations/00N_add_sveltekit.sql
INSERT INTO skills (category_id, name, sort_order) VALUES (2, 'SvelteKit', 190);
```

`category_id` は `skill_categories` を参照 (1=プログラミング言語 / 2=フレームワーク・ライブラリ /
3=インフラ・ツール / 4=語学 / 5=その他)。

### 障害時

`display_errors` は Off。エラーはコンテナのログに出る。

```bash
docker compose logs web --tail=100
```

## 今回作っていないもの

- スキルマスタの管理画面 (追加は SQL)
- パスワード変更機能
- 操作ログ・更新履歴 (1 アカウント共用のため元々記録できない)
- 自動テスト
- ログイン試行回数の制限 (パスワードが 48 文字ランダムであることで代替している)
