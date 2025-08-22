# Worker Management System - Run Book

## 🚀 Quick Start Guide

### Prerequisites Check
```bash
# Check Node.js version (requires 18+)
node --version

# Check npm version (requires 9+)
npm --version

# Check PHP version (requires 8.3+)
php --version

# Check Composer version (requires 2.0+)
composer --version

# Check if SQL Server is accessible
sqlcmd -S localhost -U sa -P YourStrongPassword123! -Q "SELECT @@VERSION"
```

## 📋 Step-by-Step Setup

### 1. Database Setup

#### SQL Server Installation & Configuration

**Ubuntu/Debian:**
```bash
# Install SQL Server
curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add -
curl https://packages.microsoft.com/config/ubuntu/20.04/prod.list > /etc/apt/sources.list.d/mssql-release.list
apt-get update
ACCEPT_EULA=Y apt-get install -y msodbcsql18 mssql-tools18 unixodbc-dev

# Install PHP SQL Server drivers
sudo apt-get install php-sqlsrv php-pdo-sqlsrv

# Restart PHP-FPM
sudo systemctl restart php8.3-fpm
```

**CentOS/RHEL:**
```bash
# Install SQL Server
sudo yum install -y mssql-tools unixODBC-devel

# Install PHP SQL Server drivers
sudo yum install php-sqlsrv php-pdo-sqlsrv

# Restart PHP-FPM
sudo systemctl restart php-fpm
```

#### Create Database and User
```sql
-- Connect to SQL Server as SA
sqlcmd -S localhost -U sa -P YourStrongPassword123!

-- Create database
CREATE DATABASE worker_management;
GO

-- Create login and user
CREATE LOGIN worker_user WITH PASSWORD = 'YourStrongPassword123!';
GO

USE worker_management;
GO

CREATE USER worker_user FOR LOGIN worker_user;
GO

-- Grant permissions
ALTER ROLE db_owner ADD MEMBER worker_user;
GO

-- Verify
SELECT name FROM sys.databases WHERE name = 'worker_management';
GO
```

### 2. Redis Setup

**Ubuntu/Debian:**
```bash
sudo apt-get install redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

**CentOS/RHEL:**
```bash
sudo yum install redis
sudo systemctl enable redis
sudo systemctl start redis
```

**Test Redis:**
```bash
redis-cli ping
# Should return: PONG
```

### 3. Application Setup

#### Clone and Install
```bash
# Clone repository
git clone <repository-url>
cd worker-management-system

# Install all dependencies
npm run install:all
```

#### Environment Configuration
```bash
# Copy environment files
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env

# Edit backend environment
nano backend/.env
```

**Required backend/.env changes:**
```env
APP_NAME="Worker Management System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=sqlsrv
DB_HOST=127.0.0.1
DB_PORT=1433
DB_DATABASE=worker_management
DB_USERNAME=worker_user
DB_PASSWORD=YourStrongPassword123!

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis

# JWT
JWT_SECRET=your-super-secret-jwt-key-here
JWT_TTL=1800

# System Settings
RESERVATION_TIMEOUT_CREATE=300
RESERVATION_TIMEOUT_PAY=600
OTP_EXPIRY=300
OTP_MAX_ATTEMPTS=3
```

#### Backend Initialization
```bash
cd backend

# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Create storage link
php artisan storage:link

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### Frontend Build
```bash
cd frontend

# Build for production
npm run build

# Or for development
npm run dev
```

### 4. Queue Worker Setup

#### Development
```bash
cd backend
php artisan queue:work --daemon
```

#### Production with Supervisor
```bash
# Install supervisor
sudo apt-get install supervisor

# Create configuration
sudo nano /etc/supervisor/conf.d/worker-queues.conf
```

**Supervisor configuration:**
```ini
[program:worker-queues]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/worker-management-system/backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/path/to/worker-management-system/backend/storage/logs/worker.log
stopwaitsecs=3600
```

**Start supervisor:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start worker-queues:*
```

### 5. Scheduler Setup

#### Crontab Configuration
```bash
# Edit crontab
crontab -e

# Add this line
* * * * * cd /path/to/worker-management-system/backend && php artisan schedule:run >> /dev/null 2>&1
```

#### Verify Scheduler
```bash
cd backend
php artisan schedule:list
```

## 🚀 Running the Application

### Development Mode
```bash
# Start both frontend and backend
npm run dev

# Or start individually
npm run dev:frontend  # http://localhost:3000
npm run dev:backend   # http://localhost:8000
```

### Production Mode
```bash
# Build frontend
cd frontend
npm run build

# Serve backend
cd backend
php artisan serve --host=0.0.0.0 --port=8000
```

### Using Nginx (Production)
```bash
# Install Nginx
sudo apt-get install nginx

# Create Nginx configuration
sudo nano /etc/nginx/sites-available/worker-management
```

**Nginx configuration:**
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/worker-management-system/backend/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location /api {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Frontend static files
    location / {
        root /path/to/worker-management-system/frontend/dist;
        try_files $uri $uri/ /index.html;
    }
}
```

**Enable site:**
```bash
sudo ln -s /etc/nginx/sites-available/worker-management /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

## 🔧 Maintenance Tasks

### Database Backup
```bash
# Create backup script
nano backup.sh
```

**Backup script:**
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/worker-management"
mkdir -p $BACKUP_DIR

# Database backup
sqlcmd -S localhost -U worker_user -P YourStrongPassword123! -Q "BACKUP DATABASE worker_management TO DISK = '$BACKUP_DIR/worker_management_$DATE.bak'"

# Application backup
tar -czf $BACKUP_DIR/app_$DATE.tar.gz /path/to/worker-management-system

# Clean old backups (keep 7 days)
find $BACKUP_DIR -name "*.bak" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
```

**Make executable and schedule:**
```bash
chmod +x backup.sh
crontab -e
# Add: 0 2 * * * /path/to/backup.sh
```

### Log Rotation
```bash
# Configure logrotate
sudo nano /etc/logrotate.d/worker-management
```

**Logrotate configuration:**
```
/path/to/worker-management-system/backend/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload php8.3-fpm
    endscript
}
```

### Health Checks
```bash
# Create health check script
nano health-check.sh
```

**Health check script:**
```bash
#!/bin/bash

# Check if backend is responding
if curl -f http://localhost:8000/api/health > /dev/null 2>&1; then
    echo "Backend: OK"
else
    echo "Backend: FAILED"
    exit 1
fi

# Check if Redis is responding
if redis-cli ping > /dev/null 2>&1; then
    echo "Redis: OK"
else
    echo "Redis: FAILED"
    exit 1
fi

# Check if database is accessible
if sqlcmd -S localhost -U worker_user -P YourStrongPassword123! -Q "SELECT 1" > /dev/null 2>&1; then
    echo "Database: OK"
else
    echo "Database: FAILED"
    exit 1
fi

echo "All systems operational"
```

## 🐛 Troubleshooting

### Common Issues

#### 1. Database Connection Issues
```bash
# Test database connection
sqlcmd -S localhost -U worker_user -P YourStrongPassword123! -Q "SELECT @@VERSION"

# Check PHP SQL Server extension
php -m | grep sqlsrv

# Check Laravel database connection
cd backend
php artisan tinker
DB::connection()->getPdo();
```

#### 2. Queue Worker Issues
```bash
# Check queue status
cd backend
php artisan queue:work --once

# Clear failed jobs
php artisan queue:flush

# Restart queue worker
sudo supervisorctl restart worker-queues:*
```

#### 3. Permission Issues
```bash
# Fix storage permissions
sudo chown -R www-data:www-data /path/to/worker-management-system/backend/storage
sudo chmod -R 775 /path/to/worker-management-system/backend/storage

# Fix cache permissions
sudo chown -R www-data:www-data /path/to/worker-management-system/backend/bootstrap/cache
sudo chmod -R 775 /path/to/worker-management-system/backend/bootstrap/cache
```

#### 4. Frontend Build Issues
```bash
# Clear node modules and reinstall
cd frontend
rm -rf node_modules package-lock.json
npm install

# Clear Vite cache
npm run build -- --force
```

### Log Analysis
```bash
# Check Laravel logs
tail -f /path/to/worker-management-system/backend/storage/logs/laravel.log

# Check queue logs
tail -f /path/to/worker-management-system/backend/storage/logs/worker.log

# Check Nginx logs
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/nginx/access.log

# Check PHP-FPM logs
sudo tail -f /var/log/php8.3-fpm.log
```

## 📊 Monitoring

### Application Metrics
```bash
# Check queue status
cd backend
php artisan queue:monitor

# Check scheduled tasks
php artisan schedule:list

# Check cache status
php artisan cache:table
php artisan cache:clear

# Check storage usage
du -sh storage/
du -sh storage/logs/
```

### System Monitoring
```bash
# Monitor system resources
htop
iotop
nethogs

# Monitor database connections
sqlcmd -S localhost -U worker_user -P YourStrongPassword123! -Q "SELECT * FROM sys.dm_exec_sessions"

# Monitor Redis memory
redis-cli info memory
```

## 🔄 Updates and Maintenance

### Application Updates
```bash
# Pull latest changes
git pull origin main

# Update dependencies
npm run install:all

# Run migrations
cd backend
php artisan migrate

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Restart services
sudo supervisorctl restart worker-queues:*
sudo systemctl restart nginx
sudo systemctl restart php8.3-fpm
```

### Database Maintenance
```bash
# Optimize database
sqlcmd -S localhost -U worker_user -P YourStrongPassword123! -Q "DBCC SHRINKDATABASE (worker_management, 10)"

# Update statistics
sqlcmd -S localhost -U worker_user -P YourStrongPassword123! -Q "EXEC sp_updatestats"

# Check for corruption
sqlcmd -S localhost -U worker_user -P YourStrongPassword123! -Q "DBCC CHECKDB (worker_management)"
```

## 🚨 Emergency Procedures

### System Recovery
```bash
# Stop all services
sudo systemctl stop nginx
sudo systemctl stop php8.3-fpm
sudo supervisorctl stop worker-queues:*

# Restore from backup
# (Follow backup restoration procedures)

# Restart services
sudo systemctl start php8.3-fpm
sudo systemctl start nginx
sudo supervisorctl start worker-queues:*
```

### Database Recovery
```bash
# Stop application
sudo systemctl stop nginx

# Restore database
sqlcmd -S localhost -U sa -P YourStrongPassword123! -Q "RESTORE DATABASE worker_management FROM DISK = '/backups/worker-management/worker_management_20240101_120000.bak'"

# Restart application
sudo systemctl start nginx
```

## 📞 Support Contacts

- **System Administrator**: admin@company.com
- **Database Administrator**: dba@company.com
- **Development Team**: dev@company.com
- **Emergency Hotline**: +966-XX-XXX-XXXX

## 📚 Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [React Documentation](https://react.dev)
- [SQL Server Documentation](https://docs.microsoft.com/en-us/sql/sql-server)
- [Redis Documentation](https://redis.io/documentation)
- [Nginx Documentation](https://nginx.org/en/docs/)