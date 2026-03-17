FROM php:8.2-apache

# ติดตั้ง Driver MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli pdo pdo_mysql

# แก้ไขบรรทัดนี้: ให้ก๊อปปี้ไฟล์จากในโฟลเดอร์ crudpos ออกมาวางที่หน้าเว็บ
COPY ./crudpos /var/www/html/

# ตั้งค่าสิทธิ์ไฟล์
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

EXPOSE 80
