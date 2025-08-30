# Test Credentials for Login

## Admin Portal (Internal Users)
- **Email:** admin@paypass.com
- **Password:** paypass8523
- **Portal Type:** Internal

## Employee Portal
- **Email:** employee@paypass.com
- **Password:** paypass8523
- **Portal Type:** Internal

## Agency Portal
- **Email:** agency@alyuserrecruitment.com
- **Password:** paypass8523
- **Portal Type:** Agency

## Customer Portal
- **Email:** customer@gmail.com
- **Password:** paypass8523
- **Portal Type:** Customer

## Important Notes:
1. All test users have the same password: `paypass8523`
2. Make sure to select the correct portal type when logging in
3. If login fails, try running: `php artisan migrate:fresh --seed`

## Alternative Login Methods:
### Customer OTP Login (if configured):
- Phone: +966512345678
- OTP: 0000 (in development mode)

### Quick Test:
For admin access, use:
- Email: admin@paypass.com
- Password: paypass8523
- Portal: Internal