FROM php:8.4-cli-alpine

RUN apk add --no-cache sqlite-dev \
    && docker-php-ext-install pdo_sqlite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

COPY . .

RUN mkdir -p storage && chmod 777 storage

EXPOSE 3003

CMD ["php", "-S", "0.0.0.0:3003", "-t", "public"]
