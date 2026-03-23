# Capus Craves - E-Commerce Platform

A modern e-commerce platform built with PHP, MySQL, and Docker, featuring a clean blue and white design theme.

## 🚀 Features

### Core Functionality
- **User Management**: Registration, login, profile management
- **Product Catalog**: Browse and search products with filtering
- **Shopping Cart**: Add to cart, quantity management
- **Order System**: Complete order processing and tracking
- **Review System**: Customer reviews and ratings
- **Admin Panel**: Comprehensive admin dashboard
- **Seller Portal**: Multi-vendor marketplace functionality

### Advanced Features
- **Allergen Filtering**: Dietary preference filtering
- **Complaint System**: Dispute resolution
- **Student Verification**: Special student accounts
- **Store Profiles**: Custom seller stores
- **Lazy Loading**: Optimized image loading
- **Responsive Design**: Mobile-friendly interface

## 🛠️ Technology Stack

- **Backend**: PHP 8.1 with Apache
- **Database**: MySQL 8.0
- **Frontend**: HTML5, CSS3, JavaScript
- **Containerization**: Docker & Docker Compose
- **Design**: Modern blue and white theme

## 📋 Requirements

- Docker Desktop installed
- Ports 8000, 3306, and 8081 available
- 4GB+ RAM recommended

## 🚀 Quick Start

### 1. Clone and Setup
```bash
git clone <repository-url>
cd capus_craves
```

### 2. Build and Run
```bash
docker-compose build --no-cache
docker-compose up -d
```

### 3. Access the Application
- **Main Application**: http://localhost:8000
- **Admin Panel**: http://localhost:8000/admin
- **Database Admin**: http://localhost:8081

### 4. Default Login
- **Admin**: username `admin`, password `admin`
- **Test User**: Register through the application

## 📁 Project Structure

```
capus_craves/
├── docker/                 # Docker configuration
│   ├── mysql/             # Database setup
│   └── php/               # PHP/Apache config
├── public/                # Web root
│   ├── admin/            # Admin panel
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   └── images/           # Product images
├── src/                  # Application source
│   ├── config/           # Configuration files
│   ├── controllers/      # MVC controllers
│   ├── models/           # Data models
│   ├── helpers/          # Utility functions
│   └── views/            # View templates
└── docker-compose.yml    # Container orchestration
```

## 🔧 Configuration

### Environment Variables
- `DB_HOST`: Database host (default: `db`)
- `DB_NAME`: Database name (default: `steampunk_construction`)
- `DB_USER`: Database username (default: `steam_user`)
- `DB_PASSWORD`: Database password (default: `gear_pass`)

### Database Setup
The database is automatically initialized with:
- Required tables and indexes
- Sample products data
- Default admin user

## 🎨 Design System

### Color Scheme
- **Primary Blue**: `#2563eb`
- **Light Blue**: `#3b82f6`
- **Dark Blue**: `#1e40af`
- **White**: `#ffffff`
- **Light Gray**: `#f8fafc`

### Typography
- **Font Family**: Inter, Segoe UI, Tahoma, Geneva, Verdana, sans-serif
- **Weights**: 400 (regular), 600 (semibold), 700 (bold)

## 🔒 Security Features

- Password hashing with PHP's built-in functions
- SQL injection prevention with prepared statements
- XSS protection with output escaping
- CSRF protection for forms
- Security headers configuration

## 📊 Performance Optimizations

- Lazy loading for images
- Gzip compression
- Browser caching headers
- Database query optimization
- CSS and JavaScript minification

## 🧪 Testing

### Manual Testing Checklist
- [ ] User registration and login
- [ ] Product browsing and search
- [ ] Cart functionality
- [ ] Order processing
- [ ] Admin panel operations
- [ ] Review system
- [ ] File uploads

### Health Checks
```bash
# Check container status
docker-compose ps

# View application logs
docker-compose logs app

# Test database connection
docker-compose exec db mysql -u steam_user -p gear_pass steampunk_construction
```

## 🔄 Maintenance

### Database Backup
```bash
# Create backup
docker-compose exec db mysqldump -u steam_user -p gear_pass steampunk_construction > backup.sql

# Restore backup
docker-compose exec -i db mysql -u steam_user -p gear_pass steampunk_construction < backup.sql
```

### Application Updates
```bash
# Pull latest changes
git pull

# Rebuild containers
docker-compose build --no-cache
docker-compose up -d
```

## 🐛 Troubleshooting

### Common Issues

#### Container Won't Start
```bash
# Check logs
docker-compose logs [service-name]

# Rebuild containers
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

#### Database Connection Issues
```bash
# Restart database
docker-compose restart db

# Check database status
docker-compose exec db mysql -u steam_user -p gear_pass -e "SHOW DATABASES;"
```

#### File Permission Issues
```bash
# Fix permissions
docker-compose exec app chown -R www-data:www-data /var/www/html
docker-compose exec app chmod -R 755 /var/www/html
```

## 📞 Support

For issues and questions:
1. Check the troubleshooting section
2. Review container logs
3. Verify Docker and system requirements
4. Consult the deployment checklist

## 📄 License

This project is proprietary software. All rights reserved.

---

**Version**: 1.0  
**Last Updated**: March 23, 2026  
**Theme**: Modern Blue & White
