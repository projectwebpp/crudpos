FROM php:8.2-apache

# ติดตั้ง Driver
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli pdo pdo_mysql

# ก๊อปปี้จากหน้าแรกไปเลย (ใช้จุดตัวเดียว)
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

EXPOSE 80
