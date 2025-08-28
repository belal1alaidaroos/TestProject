# Enhanced Authentication System Implementation

## Overview

This document describes the enhanced authentication system that has been implemented to address the requirements for different portal types and authentication methods.

## Key Features Implemented

### 1. Portal-Based Authentication
- **Customer Portal**: Full authentication options (email/password, social media, mobile OTP)
- **Agency/Admin/Internal Portals**: Email/password only
- **Dynamic portal selection** in the login interface

### 2. Authentication Methods by Portal Type

#### Customer Portal
- **Email & Password Login**: For existing customers
- **Customer Signup**: New customer registration with mandatory mobile number
- **Social Media Login**: Google, Facebook, Apple, LinkedIn
- **Mobile OTP**: Optional for existing customers with verified mobile numbers

#### Other Portals (Agency, Admin, Internal)
- **Email & Password Login Only**: Standard authentication

### 3. Mobile Number Requirements
- **Customer Portal**: Mobile number is **MANDATORY** during signup
- **Validation**: Saudi mobile number format (+966XXXXXXXXX)
- **Database Storage**: Mobile number stored in both `app_users` and `customers` tables

## Backend Implementation

### New API Endpoints

```php
// Authentication Routes
POST /api/auth/email-login          // Email/password login
POST /api/auth/customer-signup      // Customer registration
POST /api/auth/social-login         // Social media login
GET  /api/auth/portal-access/{type} // Check portal access configuration
POST /api/auth/request-otp          // Request OTP (existing)
POST /api/auth/verify-otp           // Verify OTP (existing)
```

### Enhanced AuthController

The `AuthController` now includes:

1. **emailLogin()**: Handles email/password authentication
2. **customerSignup()**: Processes new customer registrations
3. **socialLogin()**: Manages social media authentication
4. **checkPortalAccess()**: Returns portal-specific configuration

### Enhanced AuthService

New methods in `AuthService`:

1. **emailLogin()**: Validates credentials and portal access
2. **customerSignup()**: Creates user and customer records
3. **socialLogin()**: Handles social authentication flows
4. **checkPortalAccess()**: Returns available authentication methods
5. **canAccessPortal()**: Validates user portal permissions

### New Request Validation Classes

1. **EmailLoginRequest**: Validates email/password login
2. **CustomerSignupRequest**: Validates customer registration (with mobile requirement)
3. **SocialLoginRequest**: Validates social login parameters

### Enhanced AppUserRepository

Added `findByEmail()` method to support email-based authentication.

## Frontend Implementation

### Enhanced LoginPage Component

The login page now features:

1. **Portal Type Selector**: Dropdown to choose portal type
2. **Dynamic Authentication Methods**: Shows appropriate options based on portal
3. **Multiple Forms**: Login, signup, and OTP forms
4. **Social Login Buttons**: For customer portal
5. **Responsive Design**: Mobile-friendly interface

### Authentication Modes

#### Customer Portal
- **Login Tab**: Email/password + social media options
- **Signup Tab**: Complete registration form with mobile requirement
- **OTP Tab**: Mobile OTP verification

#### Other Portals
- **Single Form**: Email/password login only

### Enhanced API Service

New methods in `authAPI`:

```typescript
emailLogin: (email: string, password: string, portalType: string)
customerSignup: (data: any)
socialLogin: (provider: string, providerUserId: string, email: string, name: string, portalType: string, phone?: string)
checkPortalAccess: (portalType: string)
```

### Enhanced Auth Store

Updated `authStore` with:

1. **login()**: Direct user/token setting
2. **loginWithOtp()**: OTP-based authentication
3. **loginWithEmail()**: Email/password authentication

## Database Schema

### AppUser Table
- `email`: Required for all users
- `phone`: Required for customers, optional for others
- `password`: Required for email login users
- `user_type`: Determines portal access

### Customer Table
- `phone`: **MANDATORY** field
- `company_name_en`: Required company name
- `contact_person`: Required contact person

## Security Features

1. **Portal Access Control**: Users can only access their designated portal
2. **Password Validation**: Minimum 8 characters for customer signup
3. **Mobile Verification**: OTP verification for mobile numbers
4. **Rate Limiting**: OTP request cooldown periods
5. **Input Validation**: Comprehensive form validation with error messages

## Usage Examples

### Customer Portal Access
```
URL: /login?portal=Customer
Options: Login, Signup, Mobile OTP, Social Media
```

### Agency Portal Access
```
URL: /login?portal=Agency
Options: Email/Password only
```

### Admin Portal Access
```
URL: /login?portal=Admin
Options: Email/Password only
```

## Configuration

### Portal Configuration Response
```json
{
  "portal_type": "Customer",
  "available_methods": ["email_password", "social_media", "mobile_otp"],
  "mobile_required": true,
  "social_providers": ["google", "facebook", "apple", "linkedin"]
}
```

### Environment Variables
- `VITE_API_BASE_URL`: API base URL (default: http://localhost:8000/api)
- `system.development.otp_bypass_enabled`: Development OTP bypass
- `system.otp.cooldown_seconds`: OTP request cooldown

## Testing

### Backend Testing
- All new endpoints are properly validated
- Request validation classes ensure data integrity
- Error handling for all authentication scenarios

### Frontend Testing
- Portal type switching works correctly
- Form validation displays appropriate errors
- Authentication flow redirects properly

## Future Enhancements

1. **Social Media Integration**: Actual OAuth implementation
2. **Two-Factor Authentication**: Additional security layer
3. **Password Reset**: Email-based password recovery
4. **Account Lockout**: Brute force protection
5. **Audit Logging**: Authentication attempt tracking

## Troubleshooting

### Common Issues

1. **404 Errors**: Ensure all routes are properly registered
2. **Validation Errors**: Check request validation rules
3. **Portal Access Denied**: Verify user type matches portal
4. **Mobile Number Issues**: Ensure Saudi format (+966XXXXXXXXX)

### Debug Mode

Enable development mode for easier testing:
- OTP bypass codes available
- Detailed error logging
- Development-friendly responses

## Conclusion

The enhanced authentication system provides:

- **Flexible authentication** based on portal type
- **Mandatory mobile numbers** for customer portal
- **Multiple login methods** for customer convenience
- **Secure access control** for different user types
- **Comprehensive validation** and error handling

This implementation addresses all the requirements specified while maintaining security and user experience standards.