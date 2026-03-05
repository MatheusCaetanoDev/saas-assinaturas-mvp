FROM php:8.3-fpm-alpine

RUN set -eux; \
    apk add --no-cache \
        bash \
        curl \
        git \
        libpq \
        unzip \
        zip; \
    apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        postgresql-dev; \
    docker-php-ext-install pdo_pgsql bcmath pcntl; \
    pecl install redis; \
    docker-php-ext-enable redis; \
    apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

CMD ["php-fpm"]
