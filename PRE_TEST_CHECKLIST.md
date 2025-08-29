# Pre-Test Checklist

## Backend Setup
- [ ] Run `composer install` in `/workspace/backend`
- [ ] Run `php artisan migrate:fresh --seed` to ensure latest DB schema
- [ ] Run `php artisan storage:link` for file uploads
- [ ] Check `.env` file has correct database credentials
- [ ] Verify JWT secret is set: `php artisan jwt:secret`
- [ ] Clear all caches: `php artisan cache:clear && php artisan config:clear`

## Frontend Setup
- [ ] Run `npm install` in `/workspace/frontend`
- [ ] Check `.env` has correct API URL (usually `http://localhost:8000/api`)
- [ ] Build for testing: `npm run build` or use `npm run dev` for hot-reload

## Server Requirements
- [ ] PHP 8.1+ with required extensions (bcmath, json, mbstring, openssl)
- [ ] MySQL/PostgreSQL database running
- [ ] Storage permissions: `chmod -R 775 storage bootstrap/cache`

## Test Scenarios

### 1. Authentication Flow
- [ ] Test login with different user types
- [ ] Verify logout clears tokens properly
- [ ] Check token expiration handling
- [ ] Test OTP verification flow

### 2. Customer Portal
- [ ] Browse workers with filters
- [ ] Make reservations
- [ ] View contracts
- [ ] Test payment flow (if payment gateway configured)

### 3. Agency Portal
- [ ] View worker requests
- [ ] Submit new proposals
- [ ] Edit existing proposals
- [ ] Upload attachments

### 4. Admin Portal
- [ ] Create users (Customer, Agency, Internal)
- [ ] Edit user information
- [ ] Delete users
- [ ] Manage user roles
- [ ] Review proposals

### 5. Employee Portal
- [ ] Add/Edit/Delete workers
- [ ] Process reservations (approve/reject/extend)
- [ ] Update contract statuses
- [ ] Report and resolve worker problems
- [ ] Manage notifications

## Known Limitations
1. Payment gateway integration depends on external service configuration
2. Email notifications require mail server setup
3. SMS OTP requires SMS gateway configuration

## Quick Fixes for Common Issues

### CORS Issues
Add to `backend/config/cors.php`:
```php
'allowed_origins' => ['http://localhost:3000', 'http://localhost:5173'],
```

### File Upload Issues
In `backend/php.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 10M
```

### Permission Issues
```bash
cd /workspace/backend
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```