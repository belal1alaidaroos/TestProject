# Worker Management System

A production-ready, on-premises web application for managing worker recruitment, reservations, and contracts with customer and agency portals.

## 🏗️ Architecture

- **Frontend**: React 18 + Vite + TypeScript, TailwindCSS, React Router, React Hook Form, Axios, i18next (Arabic/English with RTL support), Zustand for state management
- **Backend**: Laravel 11 (PHP 8.3) with SQL Server 2019/2022, Sanctum for API authentication, Laravel Queues (Redis), Laravel Excel for import/export, OpenAPI (Swagger) documentation
- **Database**: SQL Server with UUID primary keys, audit columns, and CHECK constraints
- **Hosting**: On-premises only with internal SMS and ERP adapters

## 🚀 Features

### Customer Portal
- Phone OTP authentication
- Browse available workers with filters
- Reserve workers with TTL countdown
- Create contracts from reservations
- Payment processing with ERP integration
- Auto-release expired reservations

### Agency Portal
- View eligible recruitment requests
- Submit proposals with pricing and lead times
- Track proposal status
- Withdraw proposals if needed

### Admin Portal
- Review and approve/reject proposals
- Manage users, roles, and permissions
- System settings and lookups management
- Import/export functionality

## 📋 Prerequisites

- **Node.js** 18+ and npm 9+
- **PHP** 8.3+
- **Composer** 2.0+
- **SQL Server** 2019/2022
- **Redis** (for queues)
- **Git**

## 🛠️ Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd worker-management-system
```

### 2. Install Dependencies

```bash
# Install all dependencies (frontend + backend)
npm run install:all
```

### 3. Database Setup

#### SQL Server Configuration

1. **Install SQL Server Drivers**
   ```bash
   # Ubuntu/Debian
   sudo apt-get install php-sqlsrv php-pdo-sqlsrv
   
   # CentOS/RHEL
   sudo yum install php-sqlsrv php-pdo-sqlsrv
   ```

2. **Create Database**
   ```sql
   CREATE DATABASE worker_management;
   CREATE LOGIN worker_user WITH PASSWORD = 'YourStrongPassword123!';
   USE worker_management;
   CREATE USER worker_user FOR LOGIN worker_user;
   ALTER ROLE db_owner ADD MEMBER worker_user;
   ```

#### Configure Environment

```bash
# Copy environment files
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env

# Edit backend/.env with your database credentials
DB_CONNECTION=sqlsrv
DB_HOST=127.0.0.1
DB_PORT=1433
DB_DATABASE=worker_management
DB_USERNAME=worker_user
DB_PASSWORD=YourStrongPassword123!
```

### 4. Backend Setup

```bash
cd backend

# Generate application key
php artisan key:generate

# Run migrations and seeders
php artisan migrate --seed

# Generate JWT secret (if using JWT)
php artisan jwt:secret

# Create storage link
php artisan storage:link

# Set proper permissions
chmod -R 775 storage bootstrap/cache
```

### 5. Frontend Setup

```bash
cd frontend

# Build for production
npm run build
```

### 6. Queue Setup

```bash
# Start queue worker
php artisan queue:work --daemon

# Or use supervisor for production
sudo apt-get install supervisor
```

Create `/etc/supervisor/conf.d/worker-queues.conf`:
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

### 7. Scheduler Setup

Add to crontab:
```bash
* * * * * cd /path/to/worker-management-system/backend && php artisan schedule:run >> /dev/null 2>&1
```

## 🚀 Development

### Start Development Servers

```bash
# Start both frontend and backend
npm run dev

# Or start individually
npm run dev:frontend  # Frontend on http://localhost:3000
npm run dev:backend   # Backend on http://localhost:8000
```

### Available Scripts

```bash
# Development
npm run dev                    # Start both servers
npm run dev:frontend          # Start frontend only
npm run dev:backend           # Start backend only

# Building
npm run build                 # Build both frontend and backend
npm run build:frontend        # Build frontend only
npm run build:backend         # Build backend only

# Testing
npm run test                  # Run all tests
npm run test:frontend         # Run frontend tests
npm run test:backend          # Run backend tests

# Linting
npm run lint                  # Lint both frontend and backend
npm run lint:frontend         # Lint frontend only
npm run lint:backend          # Lint backend only
```

## 📁 Project Structure

```
worker-management-system/
├── frontend/                 # React application
│   ├── src/
│   │   ├── components/      # Reusable components
│   │   ├── pages/          # Page components
│   │   ├── stores/         # Zustand stores
│   │   ├── services/       # API services
│   │   ├── i18n/          # Internationalization
│   │   └── types/         # TypeScript types
│   ├── public/            # Static assets
│   └── package.json
├── backend/                # Laravel application
│   ├── app/
│   │   ├── Http/          # Controllers, Middleware, Requests
│   │   ├── Models/        # Eloquent models
│   │   ├── Services/      # Business logic services
│   │   ├── Repositories/  # Data access layer
│   │   ├── Jobs/          # Queue jobs
│   │   └── Events/        # Event classes
│   ├── database/
│   │   ├── migrations/    # Database migrations
│   │   └── seeders/       # Database seeders
│   ├── routes/            # API routes
│   └── composer.json
└── package.json           # Root package.json
```

## 🔧 Configuration

### Environment Variables

#### Backend (.env)
```env
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
JWT_SECRET=your-jwt-secret
JWT_TTL=1800

# System Settings
RESERVATION_TIMEOUT_CREATE=300
RESERVATION_TIMEOUT_PAY=600
OTP_EXPIRY=300
OTP_MAX_ATTEMPTS=3

# SMS/ERP (Internal Stubs)
SMS_PROVIDER=internal
SMS_BASE_URL=http://localhost:8080/sms
ERP_PROVIDER=internal
ERP_BASE_URL=http://localhost:8080/erp
```

#### Frontend (.env)
```env
VITE_API_BASE_URL=http://localhost:8000/api
VITE_DEFAULT_LOCALE=en
VITE_FALLBACK_LOCALE=en
```

## 🔐 Security Features

- **OWASP ASVS Compliance**: Input validation, output encoding, CSRF protection
- **JWT Authentication**: Short-lived tokens with refresh capability
- **OTP Security**: Hashed storage, rate limiting, attempt tracking
- **RBAC**: Role-based access control with granular permissions
- **Audit Logging**: Complete audit trail for sensitive operations
- **IDOR Prevention**: Proper scoping and authorization checks

## 📊 Database Schema

### Key Tables
- `app_users` - User accounts with types (Internal, Customer, Agency)
- `workers` - Worker profiles with status management
- `worker_reservations` - Reservation system with TTL
- `contracts` - Contract management
- `recruitment_requests` - Agency recruitment requests
- `supplier_proposals` - Agency proposals
- `audit_logs` - Security audit trail

### Audit Columns
All major tables include:
- `created_by` (UUID)
- `created_at` (timestamp)
- `modified_by` (UUID)
- `updated_at` (timestamp)

## 🧪 Testing

### Backend Testing
```bash
cd backend
php artisan test                    # Run all tests
php artisan test --filter=Unit      # Run unit tests only
php artisan test --filter=Feature   # Run feature tests only
```

### Frontend Testing
```bash
cd frontend
npm run test                        # Run all tests
npm run test:unit                   # Run unit tests
npm run test:e2e                    # Run E2E tests
```

## 📈 Performance

### Backend Optimization
- Database indexing on frequently queried columns
- Redis caching for lookups and sessions
- Queue processing for background jobs
- API response caching where appropriate

### Frontend Optimization
- Code splitting and lazy loading
- Image optimization
- Bundle analysis and optimization
- Service worker for caching

## 🚀 Deployment

### Production Checklist

1. **Environment Setup**
   - Set `APP_ENV=production`
   - Configure production database
   - Set up Redis for queues
   - Configure SSL certificates

2. **Security**
   - Update all passwords and secrets
   - Configure firewall rules
   - Set up monitoring and logging
   - Enable audit logging

3. **Performance**
   - Configure OPcache for PHP
   - Set up CDN for static assets
   - Configure database connection pooling
   - Set up load balancing if needed

4. **Monitoring**
   - Set up application monitoring
   - Configure error tracking
   - Set up health checks
   - Monitor queue performance

### Docker Deployment (Optional)

```dockerfile
# Backend Dockerfile
FROM php:8.3-fpm
# ... (see docker/backend/Dockerfile)

# Frontend Dockerfile
FROM node:18-alpine
# ... (see docker/frontend/Dockerfile)
```

## 🔄 API Documentation

Access Swagger documentation at: `http://localhost:8000/docs`

### Key Endpoints

#### Authentication
- `POST /api/auth/request-otp` - Request OTP
- `POST /api/auth/verify-otp` - Verify OTP and login
- `POST /api/auth/logout` - Logout

#### Customer Portal
- `GET /api/portal/workers` - Get available workers
- `POST /api/portal/workers/{id}/reserve` - Reserve worker
- `POST /api/portal/reservations/{id}/contract` - Create contract
- `POST /api/portal/contracts/{id}/confirm-payment` - Confirm payment

#### Agency Portal
- `GET /api/agency/requests` - Get eligible requests
- `POST /api/agency/requests/{id}/proposals` - Submit proposal
- `GET /api/agency/proposals` - Get agency proposals

#### Admin
- `GET /api/admin/requests/{id}/proposals` - Get proposals for review
- `POST /api/admin/proposals/{id}/approve` - Approve proposal
- `POST /api/admin/proposals/{id}/reject` - Reject proposal

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## 📄 License

This project is proprietary software. All rights reserved.

## 🆘 Support

For support and questions:
- Create an issue in the repository
- Contact the development team
- Check the documentation at `/docs`

## 🔄 Changelog

### v1.0.0 (2024-01-01)
- Initial release
- Customer portal with OTP authentication
- Agency portal with proposal management
- Admin portal with RBAC
- Complete worker reservation and contract system
- Arabic/English bilingual support with RTL
- SQL Server integration with UUID primary keys
- Comprehensive audit logging
- Queue-based background processing