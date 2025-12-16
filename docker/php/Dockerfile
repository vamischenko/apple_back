FROM php:8.2-fpm

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libicu-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

# Установка PHP расширений
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    intl \
    zip \
    opcache

# Установка Redis расширения
RUN pecl install redis-5.3.7 \
    && docker-php-ext-enable redis

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Установка рабочей директории
WORKDIR /app

# Копирование приложения
COPY . /app

# Установка прав доступа
RUN chown -R www-data:www-data /app \
    && chmod -R 755 /app

# Установка зависимостей Composer
RUN composer install --no-interaction --optimize-autoloader

# Создание директорий для runtime и assets
RUN mkdir -p backend/runtime backend/web/assets frontend/runtime frontend/web/assets \
    && chmod -R 777 backend/runtime backend/web/assets frontend/runtime frontend/web/assets

EXPOSE 9000

CMD ["php-fpm"]
