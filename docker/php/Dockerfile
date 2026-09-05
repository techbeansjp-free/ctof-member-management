FROM php:8.3-apache

# ---- 変わりにくいものを上に置く (レイヤキャッシュを効かせるため) ----
# gd: アップロード画像を 256px に縮小するため (docs 02_基本設計.md §11)
RUN apt-get update \
 && apt-get install -y --no-install-recommends libjpeg-dev libpng-dev libwebp-dev \
 && docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" gd pdo_mysql \
 && rm -rf /var/lib/apt/lists/*

# ssl は本番でインフラ側の override が 443 を張るときに必要になる
RUN a2enmod ssl rewrite headers

# 名前付き volume は初回マウント時にイメージ側の所有者を引き継ぐ。
# ここで chown しておかないと root 所有になりアップロードできない (docs 02_基本設計.md §5)
RUN mkdir -p /var/www/skillmap/storage/avatars \
 && chown -R www-data:www-data /var/www/skillmap/storage

# ---- 変わりやすいものを下に ----
# アプリのソースは bind mount するので COPY しない
COPY docker/php/php.ini          /usr/local/etc/php/conf.d/app.ini
COPY docker/php/apache-http.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/skillmap
