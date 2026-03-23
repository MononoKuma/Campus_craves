# Render Build Configuration
# This file tells Render to install PostgreSQL extensions

# Install PostgreSQL PDO driver and dependencies
apt-get update && apt-get install -y libpq-dev

# Install PostgreSQL PDO extension
docker-php-ext-install pdo_pgsql

# Enable the extension
docker-php-ext-enable pdo_pgsql
