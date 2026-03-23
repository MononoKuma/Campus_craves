# Production Deployment Guide

## Overview
This guide covers the production deployment setup for Capus Craves after system cleanup and optimization.

## Pre-Deployment Checklist

### ✅ Completed Cleanup Tasks
- Removed all debug and test files from root directory
- Cleaned up temporary SQL migration files
- Optimized .htaccess with production security headers
- Secured Docker configuration with environment variables
- Removed development comments and debug code from PHP files
- Added environment variable support (.env.example provided)

### 🔐 Security Improvements
- Added HSTS (HTTP Strict Transport Security)
- Implemented Content Security Policy (CSP)
- Enhanced X-Frame-Options, X-Content-Type-Options headers
- Secured Docker ports to localhost only
- Environment variable support for sensitive data

## Deployment Steps

### 1. Environment Setup
```bash
# Copy environment template
cp .env.example .env

# Edit .env with production values
nano .env
```

### 2. Docker Deployment
```bash
# Build and start services
docker-compose build --no-cache
docker-compose up -d

# Verify services
docker-compose ps
```

### 3. Production Verification
- [ ] Homepage loads correctly
- [ ] All CSS/JS files load
- [ ] Database connectivity works
- [ ] User registration/login functions
- [ ] Shopping cart works
- [ ] Admin panel accessible

## Security Configuration

### Environment Variables Required
```env
MYSQL_ROOT_PASSWORD=your_secure_root_password
MYSQL_DATABASE=capus_craves
MYSQL_USER=capus_user
MYSQL_PASSWORD=your_secure_user_password
```

### HTTPS Setup
1. Obtain SSL certificate
2. Configure reverse proxy (nginx/Apache)
3. Update .htaccess for HTTPS redirects
4. Update CSP headers for HTTPS

## Performance Optimizations

### Enabled Optimizations
- Gzip compression
- Browser caching (1 year for static assets)
- Security headers
- Docker restart policies

### Recommended Additional Optimizations
- PHP OPcache configuration
- MySQL production tuning
- CDN implementation
- Redis caching

## Monitoring & Maintenance

### Log Locations
- Application logs: `docker logs capus_craves-app-1`
- Database logs: `docker logs capus_craves-db-1`
- Access logs: Apache logs within container

### Backup Procedures
```bash
# Database backup
docker exec capus_craves-db-1 mysqldump -u capus_user -p capus_craves > backup.sql

# Application backup
tar -czf app_backup.tar.gz public/ src/ docker/
```

## Troubleshooting

### Common Issues
1. **Port conflicts**: Ensure ports 8000, 3306, 8081 are available
2. **Permission issues**: Check file permissions in Docker volume
3. **Database connection**: Verify environment variables
4. **HTTPS redirects**: Update .htaccess for SSL termination

### Recovery Commands
```bash
# Restart services
docker-compose restart

# Rebuild if needed
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# Reset database (CAUTION)
docker-compose down -v
docker-compose up -d
```

## Post-Deployment Tasks

### Security
- [ ] Change all default passwords
- [ ] Set up SSL certificates
- [ ] Configure firewall rules
- [ ] Set up monitoring alerts

### Performance
- [ ] Enable OPcache
- [ ] Configure MySQL production settings
- [ ] Set up monitoring tools
- [ ] Implement backup schedule

### Maintenance
- [ ] Set up log rotation
- [ ] Configure automated backups
- [ ] Set up uptime monitoring
- [ ] Plan regular updates

## Support
For deployment issues:
1. Check Docker logs: `docker-compose logs`
2. Verify environment variables
3. Check port availability
4. Review .htaccess configuration

---
**Last Updated**: March 24, 2026
**Version**: 2.0 (Production Ready)
