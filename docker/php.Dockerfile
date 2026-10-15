FROM php:8.2-cli-alpine
RUN docker-php-ext-install pdo_mysql \
    && addgroup -g 1000 app \
    && adduser -D -u 1000 -G app app \
    && mkdir -p /app/api/storage \
    && chown -R app:app /app
WORKDIR /app
USER app
CMD ["php", "-S", "0.0.0.0:8080", "api/public/index.php"]
