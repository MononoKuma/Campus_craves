# Install PostgreSQL PDO Driver on Render

# Create this file to install required extensions
# Render will run this during build process

# Install PostgreSQL PDO driver
RUN docker-php-ext-install pdo_pgsql

# Enable the extension
RUN docker-php-ext-enable pdo_pgsql
