FROM php:8.2-apache
RUN sed -i s,http://deb.debian.org,https://deb.debian.org,g /etc/apt/sources.list.d/debian.sources && apt-get -o Acquire::Retries=1 -o Acquire::https::Timeout=30 update && apt-get install -y --no-install-recommends libjpeg62-turbo-dev libpng-dev libwebp-dev qpdf openssl \
 && docker-php-ext-configure gd --with-jpeg --with-webp && docker-php-ext-install pdo_mysql gd \
 && a2enmod rewrite ssl && rm -rf /var/lib/apt/lists/* \
 && mkdir -p /srv/carlos/private/storage /app \
 && ln -s /srv/carlos/private/api /app/api \
 && ln -s /srv/carlos/private/database /app/database \
 && ln -s /storage /srv/test-storage \
 && openssl req -x509 -newkey rsa:2048 -nodes -days 2 -subj /CN=carlosburgosh.com -addext subjectAltName=DNS:carlosburgosh.com,DNS:www.carlosburgosh.com -keyout /tmp/test.key -out /tmp/test.crt
COPY deploy/apache.conf /etc/apache2/sites-available/000-default.conf
RUN printf 'upload_max_filesize=8M\npost_max_size=9M\nmemory_limit=256M\ndisplay_errors=Off\nlog_errors=On\n' > /usr/local/etc/php/conf.d/uploads.ini
