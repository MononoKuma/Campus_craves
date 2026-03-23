# Render MySQL Database Configuration
# Create a MySQL database service in Render and use these credentials

# Step 1: Create MySQL Service
# Go to Render Dashboard → New → MySQL
# Choose Free tier, same region as your web service

# Step 2: Get Connection Details
# Copy from your MySQL service's "Connect" tab

# Step 3: Set Environment Variables in Web Service
DB_HOST=your-mysql-host.railway.app
DB_PORT=3306
DB_NAME=capus_craves
DB_USER=your_mysql_user
DB_PASSWORD=your_mysql_password
DB_TYPE=mysql

# Alternative: Use Render's built-in MySQL
# DB_HOST=your-render-mysql-host
# DB_PORT=3306
