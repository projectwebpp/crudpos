FROM php:8.2-apache

# ติดตั้ง Driver สำหรับ MySQL (ต้องมี pdo_mysql ด้วย)
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli pdo pdo_mysql

# ก๊อปปี้ไฟล์ทั้งหมด (ซึ่งตอนนี้อยู่ถูกที่แล้ว)
COPY . /var/www/html/

# เปิดพอร์ต 80
EXPOSE 80
