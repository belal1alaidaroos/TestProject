# Employee Portal - Complete Runbook

## 🚀 Quick Start Guide

### Prerequisites
- **Node.js** 18+ and npm 9+
- **PHP** 8.3+
- **SQL Server** 2019/2022 with sqlsrv drivers
- **Composer** 2.0+
- **Redis** (for queues and caching)
- **Git**

### 1. Initial Setup

```bash
# Clone the repository
git clone <repository-url>
cd employee-portal-monorepo

# Install all dependencies
npm run install:all

# Setup backend environment
cd backend
cp .env.example .env
# Edit .env with your database credentials

# Setup frontend environment
cd ../frontend
cp .env.example .env
# Edit .env with your API base URL
```

### 2. Database Configuration

#### SQL Server Setup
```sql
-- Create database
CREATE DATABASE employee_portal;
GO

-- Create login and user
CREATE LOGIN portal_user WITH PASSWORD = 'YourStrongPassword123!';
GO

USE employee_portal;
GO

CREATE USER portal_user FOR LOGIN portal_user;
GO

-- Grant permissions
ALTER ROLE db_owner ADD MEMBER portal_user;
GO
```

#### Environment Configuration
```env
# Backend .env
DB_CONNECTION=sqlsrv
DB_HOST=your-sql-server
DB_PORT=1433
DB_DATABASE=employee_portal
DB_USERNAME=portal_user
DB_PASSWORD=YourStrongPassword123!

# JWT Configuration
JWT_SECRET=your-jwt-secret-key-here
JWT_TTL=30
JWT_REFRESH_TTL=1440

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
QUEUE_CONNECTION=redis
```

### 3. Backend Setup

```bash
cd backend

# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret

# Run migrations
php artisan migrate:fresh

# Seed database with initial data
php artisan db:seed

# Create storage link
php artisan storage:link

# Set proper permissions
chmod -R 775 storage bootstrap/cache
```

### 4. Frontend Setup

```bash
cd frontend

# Install dependencies (if not already done)
npm install

# Build for production
npm run build
```

### 5. Start Development Servers

```bash
# From root directory - start both servers
npm run dev

# Or start separately:
npm run dev:backend    # Laravel server on :8000
npm run dev:frontend   # Vite dev server on :5173
```

## 🗄️ Database Management

### Migration Commands
```bash
cd backend

# Create new migration
php artisan make:migration create_table_name

# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Reset all migrations
php artisan migrate:reset

# Fresh install (drop all tables and recreate)
php artisan migrate:fresh --seed
```

### Seeding Commands
```bash
cd backend

# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=UserSeeder

# Run demo data seeder
php artisan portal:seed-demo
```

### Database Backup
```bash
cd backend

# Create backup
php artisan portal:backup-db

# Clean old backups
php artisan backup:clean
```

## 🔄 Queue Management

### Start Queue Workers
```bash
cd backend

# Start queue worker
php artisan queue:work

# Start with specific queue
php artisan queue:work --queue=high,default,low

# Start with specific connection
php artisan queue:work --connection=redis

# Start in daemon mode
php artisan queue:work --daemon
```

### Queue Monitoring
```bash
cd backend

# Check failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry {id}

# Clear all failed jobs
php artisan queue:flush

# Monitor queue status
php artisan queue:monitor
```

### Supervisor Configuration (Production)
```ini
[program:employee-portal-queues]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/employee-portal/backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/path/to/employee-portal/backend/storage/logs/worker.log
stopwaitsecs=3600
```

## ⏰ Scheduled Tasks

### Cron Setup
```bash
# Add to crontab
crontab -e

# Add this line:
* * * * * cd /path/to/employee-portal/backend && php artisan schedule:run >> /dev/null 2>&1
```

### Available Scheduled Jobs
- **Release Expired Reservations**: Every minute
- **Cancel Expired Awaiting Payment**: Every 2 minutes
- **Send Contract Reminders**: Daily at 9 AM
- **Sync with ERP**: Every hour
- **Generate Reports**: Daily at 6 AM
- **Cleanup Tasks**: Daily at various times

### Manual Job Execution
```bash
cd backend

# Run specific scheduled job
php artisan schedule:run --verbose

# Test specific job
php artisan queue:work --once --queue=default
```

## 🧪 Testing

### Backend Testing
```bash
cd backend

# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/AuthTest.php

# Run with coverage
php artisan test --coverage

# Run specific test method
php artisan test --filter testUserCanLogin
```

### Frontend Testing
```bash
cd frontend

# Run all tests
npm run test

# Run with UI
npm run test:ui

# Run with coverage
npm run test:coverage

# Run specific test
npm run test -- --grep "Login"
```

### E2E Testing
```bash
cd frontend

# Install Playwright
npx playwright install

# Run E2E tests
npx playwright test

# Run with UI
npx playwright test --ui
```

## 🔧 Maintenance Commands

### System Health Check
```bash
cd backend

# Run health check
php artisan portal:health-check

# Check system status
php artisan portal:stats

# Test OTP generation
php artisan portal:test-otp 0501234567
```

### Cleanup Commands
```bash
cd backend

# Clean up expired data
php artisan portal:cleanup

# Clear application cache
php artisan cache:clear

# Clear config cache
php artisan config:clear

# Clear route cache
php artisan route:clear

# Clear view cache
php artisan view:clear

# Optimize for production
php artisan optimize
```

### Log Management
```bash
cd backend

# View logs
tail -f storage/logs/laravel.log

# Clear old logs
php artisan log:clear

# Monitor slow queries
tail -f storage/logs/slow-queries.log
```

## 🚀 Production Deployment

### Environment Setup
```bash
# Set production environment
export APP_ENV=production
export APP_DEBUG=false

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Performance Optimization
```bash
cd backend

# Enable OPcache
# Add to php.ini:
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1

# Enable Redis for sessions and cache
# Update .env:
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Security Hardening
```bash
cd backend

# Set proper file permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Enable HTTPS
# Configure SSL certificates in web server

# Set security headers
# Add to web server configuration:
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;
add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
```

## 📊 Monitoring & Logging

### Application Monitoring
```bash
cd backend

# Enable Telescope (development only)
php artisan telescope:install
php artisan migrate

# Monitor queue performance
php artisan queue:monitor

# Check failed jobs
php artisan queue:failed
```

### Database Monitoring
```sql
-- Check slow queries
SELECT 
    qs.sql_handle,
    qs.execution_count,
    qs.total_elapsed_time / 1000000.0 AS total_elapsed_time_seconds,
    qs.last_execution_time,
    SUBSTRING(qt.text, (qs.statement_start_offset/2)+1, 
        ((CASE qs.statement_end_offset
            WHEN -1 THEN DATALENGTH(qt.text)
            ELSE qs.statement_end_offset
        END - qs.statement_start_offset)/2) + 1) AS statement_text
FROM sys.dm_exec_query_stats qs
CROSS APPLY sys.dm_exec_sql_text(qs.sql_handle) qt
WHERE qs.total_elapsed_time > 1000000  -- 1 second
ORDER BY qs.total_elapsed_time DESC;
```

### Performance Monitoring
```bash
cd backend

# Monitor memory usage
php artisan tinker
memory_get_usage(true)

# Check database connections
php artisan tinker
DB::connection()->getPdo()->getAttribute(PDO::ATTR_CONNECTION_STATUS)
```

## 🔄 Backup & Recovery

### Database Backup
```bash
cd backend

# Create backup
php artisan portal:backup-db

# Manual backup using SQL Server tools
sqlcmd -S your-server -d employee_portal -Q "BACKUP DATABASE employee_portal TO DISK = 'C:\backups\employee_portal_$(Get-Date -Format 'yyyyMMdd_HHmmss').bak'"
```

### File Backup
```bash
# Backup storage directory
tar -czf storage_backup_$(date +%Y%m%d_%H%M%S).tar.gz storage/

# Backup configuration files
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
```

### Recovery Procedures
```bash
cd backend

# Restore from backup
php artisan backup:restore --path=/path/to/backup

# Restore database
sqlcmd -S your-server -d master -Q "RESTORE DATABASE employee_portal FROM DISK = 'C:\backups\employee_portal_backup.bak'"
```

## 🆘 Troubleshooting

### Common Issues

#### Database Connection Issues
```bash
# Check SQL Server status
systemctl status mssql-server

# Test connection
php artisan tinker
DB::connection()->getPdo()

# Check drivers
php -m | grep sqlsrv
```

#### Queue Issues
```bash
# Check Redis connection
redis-cli ping

# Restart queue workers
php artisan queue:restart

# Clear failed jobs
php artisan queue:flush
```

#### Performance Issues
```bash
# Check slow queries
tail -f storage/logs/slow-queries.log

# Monitor memory usage
php artisan tinker
memory_get_peak_usage(true)

# Check cache hit rate
php artisan cache:stats
```

### Emergency Procedures

#### System Down
```bash
# Put application in maintenance mode
php artisan down --message="System maintenance in progress" --retry=60

# Bring system back up
php artisan up
```

#### Database Recovery
```bash
# Stop application
php artisan down

# Restore database
# (Use SQL Server recovery procedures)

# Run migrations
php artisan migrate

# Start application
php artisan up
```

## 📚 Additional Resources

### Documentation
- API Documentation: `/docs`
- Swagger JSON: `/docs/swagger.json`
- Health Check: `/up`

### Support Commands
```bash
# List all available commands
php artisan list

# Get help for specific command
php artisan help migrate

# Check system requirements
php artisan about
```

### Development Tools
```bash
# Install development dependencies
composer require --dev laravel/telescope
composer require --dev barryvdh/laravel-debugbar

# Enable development features
php artisan telescope:install
php artisan debugbar:publish
```

This runbook provides comprehensive guidance for setting up, operating, and maintaining the Employee Portal application. Follow the procedures step-by-step and refer to the troubleshooting section for common issues.