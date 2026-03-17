FROM php:8.2-apache

# บรรทัดนี้คือหัวใจสำคัญ ถ้าไม่มีจะขึ้น "could not find driver" เสมอ
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli pdo pdo_mysql

# ก๊อปปี้ไฟล์งานทั้งหมด
COPY . /var/www/html/

# ตั้งค่าสิทธิ์ให้ Apache
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

EXPOSE 80
