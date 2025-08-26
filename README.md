# Employee Recruitment Portal

A production-ready, full-stack web application for managing employee recruitment with customer and agency portals.

## 🚀 Features

- **Customer Portal**: Browse workers, make reservations, create contracts, and manage payments
- **Agency Portal**: Submit proposals for recruitment requests
- **Admin Portal**: Manage proposals, users, roles, and system settings
- **Multi-language Support**: Arabic (RTL) and English with full internationalization
- **Real-time Updates**: Live TTL countdowns and status updates
- **Role-Based Access Control**: Comprehensive RBAC system
- **API Documentation**: OpenAPI/Swagger documentation

## 🏗️ Architecture

- **Frontend**: React 18 + TypeScript + Vite + TailwindCSS
- **Backend**: Laravel 11 + PHP 8.3 + SQL Server
- **Database**: SQL Server 2019/2022 with UUID primary keys
- **Authentication**: JWT with OTP phone verification
- **State Management**: Zustand
- **Form Handling**: React Hook Form
- **HTTP Client**: Axios with interceptors
- **Internationalization**: i18next with RTL support

## 📋 Prerequisites

- Node.js 18+ and npm 9+
- PHP 8.3+
- SQL Server 2019/2022
- Composer 2.0+
- Redis (for queues)

## 🛠️ Quick Start

### 1. Clone and Install Dependencies

```bash
git clone <repository-url>
cd employee-portal-monorepo
npm run install:all
```

### 2. Environment Setup

```bash
# Backend
cd backend
cp .env.example .env
# Edit .env with your database credentials and settings

# Frontend
cd ../frontend
cp .env.example .env
# Edit .env with your API base URL
```

### 3. Database Setup

```bash
cd backend
php artisan migrate:fresh --seed
```

### 4. Start Development Servers

```bash
# From root directory
npm run dev

# Or start separately:
npm run dev:backend    # Laravel server on :8000
npm run dev:frontend   # Vite dev server on :5173
```

## 🗄️ Database Schema

The application uses a comprehensive database schema with:
- UUID primary keys for all tables
- Audit trails (CreatedBy, CreatedAt, ModifiedBy, ModifiedAt)
- Proper foreign key relationships
- Check constraints for enumerations
- Optimized indexes for performance

## 🔐 Authentication

- Phone OTP verification system
- JWT tokens with refresh mechanism
- Rate limiting on OTP requests
- Role-based access control (RBAC)

## 🌐 API Endpoints

### Customer Portal
- `POST /auth/request-otp` - Request OTP
- `POST /auth/verify-otp` - Verify OTP and login
- `GET /portal/workers` - Browse available workers
- `POST /portal/workers/{id}/reserve` - Reserve a worker
- `POST /portal/reservations/{id}/contract` - Create contract
- `POST /portal/contracts/{id}/confirm-payment` - Confirm payment

### Agency Portal
- `GET /agency/requests` - View eligible requests
- `POST /agency/requests/{id}/proposals` - Submit proposal
- `PATCH /agency/proposals/{id}` - Update proposal
- `DELETE /agency/proposals/{id}` - Withdraw proposal

### Admin Portal
- `GET /admin/requests/{id}/proposals` - Review proposals
- `POST /admin/proposals/{id}/approve` - Approve proposal
- `POST /admin/proposals/{id}/reject` - Reject proposal

## 🧪 Testing

```bash
# Run all tests
npm run test

# Frontend tests
npm run test:frontend

# Backend tests
npm run test:backend
```

## 📚 Documentation

- API documentation available at `/docs` (Swagger/OpenAPI)
- Frontend component documentation in `/frontend/docs`
- Database schema documentation in `/backend/docs`

## 🔧 Configuration

### Backend (.env)
```env
DB_CONNECTION=sqlsrv
DB_HOST=your-sql-server
DB_PORT=1433
DB_DATABASE=employee_portal
DB_USERNAME=your-username
DB_PASSWORD=your-password

JWT_SECRET=your-jwt-secret
JWT_TTL=30
JWT_REFRESH_TTL=1440

QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Frontend (.env)
```env
VITE_API_BASE_URL=http://localhost:8000/api
VITE_DEFAULT_LOCALE=en
VITE_FALLBACK_LOCALE=ar
```

## 📁 Project Structure

```
├── frontend/                 # React application
│   ├── src/
│   │   ├── components/      # Reusable components
│   │   ├── pages/          # Page components
│   │   ├── hooks/          # Custom hooks
│   │   ├── stores/         # Zustand stores
│   │   ├── services/       # API services
│   │   ├── utils/          # Utility functions
│   │   └── i18n/           # Internationalization
│   ├── public/             # Static assets
│   └── package.json
├── backend/                 # Laravel application
│   ├── app/
│   │   ├── Http/           # Controllers, Middleware
│   │   ├── Models/         # Eloquent models
│   │   ├── Services/       # Business logic
│   │   ├── Jobs/           # Queue jobs
│   │   └── Policies/       # Authorization policies
│   ├── database/            # Migrations, seeders
│   ├── routes/              # API routes
│   └── composer.json
└── package.json             # Root package.json
```

## 🚀 Deployment

### Production Build

```bash
npm run build
```

### Docker (Optional)

```bash
docker-compose up -d
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## 📄 License

This project is proprietary software. All rights reserved.

## 🆘 Support

For support and questions:
- Create an issue in the repository
- Contact the development team
- Check the documentation at `/docs`

## 🔄 Changelog

### v1.0.0
- Initial release
- Customer portal with worker browsing and reservation
- Agency portal with proposal submission
- Admin portal with proposal management
- Complete RBAC system
- Multi-language support (Arabic/English)
- Real-time updates and TTL management