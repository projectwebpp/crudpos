FROM php:8.2-apache
COPY . /var/www/html/
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
EXPOSE 80
