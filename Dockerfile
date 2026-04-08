FROM php:8.3-cli-alpine

LABEL maintainer="hectorfranco@nowo.tech"

# Install system dependencies
RUN apk add --no-cache \
    bash \
    git \
    unzip \
    curl \
    linux-headers \
    $PHPIZE_DEPS

# Install Xdebug for coverage
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy source
COPY . .

# Install dependencies
RUN composer install --no-interaction --prefer-dist

CMD ["bash"]
