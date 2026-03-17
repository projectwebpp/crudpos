FROM php:8.2-apache

# ติดตั้ง Driver สำหรับ MySQL ทั้งแบบ mysqli และ PDO
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli pdo pdo_mysql

# ก๊อปปี้โค้ดทั้งหมดไปไว้ในโฟลเดอร์รันเว็บ
COPY . /var/www/html/

# เปิดพอร์ต 80
EXPOSE 80
