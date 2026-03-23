# Render-specific Dockerfile for PostgreSQL support
FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    locales \
    zip \
    unzip \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql pdo_pgsql gd zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set up Apache
RUN a2enmod rewrite
COPY docker/php/apache.conf /etc/apache2/sites-available/000-default.conf
RUN a2dissite 000-default.conf && a2ensite 000-default.conf \
    && a2enmod headers \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY public/ /var/www/html/
COPY src/ /var/www/html/src/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 644 /var/www/html/*.php \
    && chmod -R 644 /var/www/html/src/**/*.php \
    && find /var/www/html -type d -exec chmod 755 {} \;

# Expose port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
