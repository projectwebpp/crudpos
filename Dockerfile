FROM php:8.2-apache

# ติดตั้ง Driver สำหรับ MySQL ให้ครบ (mysqli และ pdo)
RUN apt-get update && apt-get install -y \
    libmariadb-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && docker-php-ext-enable mysqli pdo pdo_mysql

# ก๊อปปี้ไฟล์งานทั้งหมดไปที่ Server
COPY . /var/www/html/

# ตั้งค่า Permission ให้ Apache อ่านไฟล์ได้
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

EXPOSE 80
