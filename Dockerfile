FROM php:8.2-apache

# 1. ติดตั้ง Driver MySQL (บังคับติดตั้งทั้ง 2 ตัวเพื่อความชัวร์)
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli pdo pdo_mysql

# 2. ก๊อปปี้ไฟล์จากโฟลเดอร์ crudpos มาไว้ที่หน้าแรกของเว็บ
# ถ้าไฟล์ทั้งหมดอยู่ในโฟลเดอร์ crudpos ต้องใช้คำสั่งนี้ครับ
COPY ./crudpos /var/www/html/

# 3. ตั้งค่าสิทธิ์ไฟล์
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

EXPOSE 80
