FROM php:8.2-cli

RUN apt-get update && apt-get install -y git unzip libxml2-dev \
    && docker-php-ext-install xml \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-interaction --prefer-dist --ignore-platform-req=php

CMD ["vendor/bin/phpunit"]

