FROM php:8.2-cli

WORKDIR /app

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# gd cho mã QR/mã vạch, pdo_mysql để kết nối MySQL, zip cho composer
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd pdo_mysql zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Cài thư viện trước để tận dụng cache khi chỉ sửa mã nguồn
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 10000

# Khóa bí mật (APP_KEY, DB, VNPay, Cloudinary, mail) truyền qua biến môi trường, không đóng gói vào image
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=10000
