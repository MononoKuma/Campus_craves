# Minimal Render Dockerfile for PostgreSQL
FROM php:8.1-apache

# Install PostgreSQL dependencies
RUN apt-get update && apt-get install -y libpq-dev && rm -rf /var/lib/apt/lists/*

# Install PostgreSQL PDO extension
RUN docker-php-ext-install pdo_pgsql

# Copy application
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Enable Apache rewrite
RUN a2enmod rewrite

EXPOSE 80
CMD ["apache2-foreground"]
