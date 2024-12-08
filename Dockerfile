FROM php:8.0-cli

WORKDIR /var/www/html

COPY . .

RUN docker-php-ext-install pdo pdo_mysql

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]