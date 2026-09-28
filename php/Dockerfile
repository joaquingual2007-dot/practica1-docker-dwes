FROM php:8.3-fpm

# Instalar extensiones requeridas para conectar con MySQL mediante PDO
RUN docker-php-ext-install pdo pdo_mysql

# Establecer el directorio de trabajo donde se montará el código
WORKDIR /var/www/html
