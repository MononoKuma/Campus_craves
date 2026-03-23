# Render Deployment Guide

## Database Configuration

The error you're experiencing occurs because the application is configured for Docker (`DB_HOST=db`) but needs Render-specific database settings.

### Steps to Fix:

1. **Create a Render Database Service**
   - Go to Render Dashboard
   - Create a new "PostgreSQL" or "MySQL" service
   - Choose a name (e.g., `capus-craves-db`)

2. **Get Database Connection Details**
   - Once created, go to your database service
   - Copy the connection details from the "Connect" tab
   - You'll need: Host, Port, Database name, Username, Password

3. **Set Environment Variables in Render**
   - Go to your web service settings
   - Add these environment variables:
   ```
   DB_HOST=your-database-host.railway.app
   DB_PORT=3306
   DB_NAME=capus_craves
   DB_USER=your_username
   DB_PASSWORD=your_password
   ```

4. **Alternative: Use DATABASE_URL**
   - Some platforms support a single DATABASE_URL variable:
   ```
   DATABASE_URL=mysql://username:password@host:port/database
   ```

### Updated Database Configuration

The `src/config/database.php` file has been updated to:
- Properly handle connection failures
- Log errors instead of exposing them to users
- Throw meaningful exceptions

### Common Render Database Hosts:
- Railway: `railway.app`
- Render: `astra.datastax.com` or custom domains
- PlanetScale: `aws.connect.psdb.cloud`

### Testing the Connection

After deployment, check your logs to ensure the database connection is working:
```
Database connection failed: SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo for db failed
```

Should become:
```
Database connection successful
```

### Troubleshooting

1. **Check Environment Variables**: Ensure all DB_* variables are set correctly
2. **Network Access**: Make sure your web service can access the database
3. **SSL Requirements**: Some databases require SSL connections
4. **Firewall Rules**: Check if there are any IP restrictions

### Docker vs Render

- **Docker**: Uses service names (`db`) for inter-container communication
- **Render**: Uses actual hostnames provided by the database service
- **Environment Variables**: Render automatically injects them, Docker uses .env file
