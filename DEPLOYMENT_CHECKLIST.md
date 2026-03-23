# Deployment Checklist for Capus Craves

## ✅ Pre-Deployment Requirements

### Environment Setup
- [ ] Docker and Docker Compose installed
- [ ] Port 8000 available for web server
- [ ] Port 3306 available for MySQL
- [ ] Port 8081 available for Adminer (optional)
- [ ] Sufficient disk space for Docker volumes

### Configuration Files
- [ ] `docker-compose.yml` configured and valid
- [ ] Database credentials set in environment variables
- [ ] Apache configuration properly set up
- [ ] File permissions correctly configured

## 🚀 Deployment Steps

### 1. Build and Start Services
```bash
docker-compose build --no-cache
docker-compose up -d
```

### 2. Verify Services
```bash
docker-compose ps
```
Expected: All 3 services (app, db, adminer) should be running

### 3. Test Application
```bash
# Test web server responds
curl http://localhost:8000

# Check application logs
docker logs capus_craves-app-1

# Check database logs
docker logs capus_craves-db-1
```

### 4. Database Verification
- [ ] Database initialized successfully
- [ ] Tables created from init.sql
- [ ] Sample data inserted
- [ ] Application can connect to database

## 🔍 Post-Deployment Verification

### Application Functionality
- [ ] Homepage loads correctly (HTTP 200)
- [ ] CSS and JavaScript files load
- [ ] Images display properly
- [ ] Database connectivity works
- [ ] User registration/login functions
- [ ] Product catalog displays
- [ ] Shopping cart works
- [ ] Admin panel accessible

### Performance & Security
- [ ] Response times acceptable
- [ ] No PHP errors in logs
- [ ] Security headers present
- [ ] File permissions correct
- [ ] Database credentials not exposed

## 🛠️ Troubleshooting

### Common Issues

#### Service Won't Start
```bash
# Check logs
docker-compose logs [service-name]

# Rebuild if needed
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

#### Database Connection Issues
```bash
# Verify database is running
docker exec capus_craves-db-1 mysql -u steam_user -p gear_pass -e "SHOW DATABASES;"

# Test from application container
docker exec capus_craves-app-1 php -r "try { new PDO('mysql:host=db;dbname=steampunk_construction', 'steam_user', 'gear_pass'); echo 'DB OK'; } catch(Exception \$e) { echo 'DB Error: ' . \$e->getMessage(); }"
```

#### File Permission Issues
```bash
# Fix permissions if needed
docker exec capus_craves-app-1 chown -R www-data:www-data /var/www/html
docker exec capus_craves-app-1 chmod -R 755 /var/www/html
```

## 📋 Production Considerations

### Security
- [x] Change default database passwords
- [x] Use environment files for sensitive data
- [x] Enable HTTPS/SSL certificates
- [x] Add security headers (HSTS, CSP)
- [x] Remove debug code and test files
- [ ] Regular security updates
- [ ] Backup strategy implemented

### Performance
- [x] Enable Gzip compression
- [x] Set up browser caching
- [ ] Enable PHP OPcache
- [ ] Configure MySQL for production
- [ ] Set up monitoring and logging
- [ ] Implement caching strategies
- [ ] Regular database optimization

### Code Cleanup
- [x] Remove debug and test files
- [x] Clean up temporary SQL files
- [x] Remove development comments
- [x] Optimize .htaccess for production
- [x] Add environment variable support
- [x] Secure Docker configuration

### Maintenance
- [ ] Set up automated backups
- [ ] Monitor disk space usage
- [ ] Regular security scans
- [ ] Update dependencies
- [ ] Performance monitoring

## 🔄 Backup and Recovery

### Database Backup
```bash
# Create backup
docker exec capus_craves-db-1 mysqldump -u steam_user -p gear_pass steampunk_construction > backup.sql

# Restore backup
docker exec -i capus_craves-db-1 mysql -u steam_user -p gear_pass steampunk_construction < backup.sql
```

### Application Backup
```bash
# Backup application files
tar -czf app_backup.tar.gz public/ src/ docker/
```

## 📞 Support Contacts

- Docker Documentation: https://docs.docker.com/
- MySQL Documentation: https://dev.mysql.com/doc/
- Apache Documentation: https://httpd.apache.org/docs/
- PHP Documentation: https://www.php.net/docs.php

---

**Last Updated**: March 23, 2026
**Version**: 1.0
