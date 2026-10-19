FROM php:8.2-cli-alpine
RUN apk add --no-cache libjpeg-turbo-dev libpng-dev libwebp-dev qpdf \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql gd \
    && addgroup -g 1000 app \
    && adduser -D -u 1000 -G app app \
    && mkdir -p /app/api/storage /storage \
    && chown -R app:app /app /storage
WORKDIR /app
RUN printf 'upload_max_filesize=8M\npost_max_size=9M\nmemory_limit=256M\ndisplay_errors=Off\ndisplay_startup_errors=Off\n' > /usr/local/etc/php/conf.d/uploads.ini
USER app
CMD ["php", "-S", "0.0.0.0:8080", "api/public/index.php"]
