FROM php:8.1-apache

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    && docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd

RUN a2enmod rewrite

# Create directories and set permissions
RUN mkdir -p /var/www/html/images/products && \
    chown -R www-data:www-data /var/www/html/images && \
    chmod -R 755 /var/www/html/images

COPY public/ /var/www/html/
COPY src/ /var/www/src/
COPY docker/php/apache.conf /etc/apache2/sites-available/000-default.conf

# Ensure proper ownership and permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

WORKDIR /var/www/html

EXPOSE 80