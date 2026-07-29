FROM php:8.2-cli-alpine

# System packages
RUN apk add --no-cache \
    curl \
    git \
    unzip \
    libzip-dev \
    mariadb-connector-c-dev

# PHP extensions needed by tests
RUN docker-php-ext-install pdo pdo_mysql zip

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files and install dependencies first (layer cache)
COPY composer.json ./
RUN composer install --no-scripts --no-autoloader --prefer-dist

# Copy everything else and generate autoloader
COPY . .
RUN composer dump-autoload --optimize

CMD ["./vendor/bin/phpunit", "--configuration", "phpunit.integration.xml", "--testdox"]
