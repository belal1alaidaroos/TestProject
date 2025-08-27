# PayPass Payment System Setup Guide

## Overview

The PayPass system is a secure payment method that uses OTP (One-Time Password) verification via SMS to complete payments. This system is designed to work without external SMS providers during development and testing.

## Features

- **Secure OTP Verification**: 6-digit OTP sent to user's phone
- **Development Bypass**: Built-in bypass code (8523) for testing
- **Session Management**: Secure payment sessions with expiration
- **Multiple Payment Methods**: Support for card, bank transfer, and PayPass
- **Rate Limiting**: Protection against OTP abuse

## Backend Setup

### 1. Environment Configuration

Create a `.env` file in the backend directory with the following settings:

```env
# OTP Configuration
OTP_EXPIRY_SECONDS=300
OTP_MAX_ATTEMPTS=3
OTP_COOLDOWN_SECONDS=60
OTP_LENGTH=6

# Development Configuration
OTP_BYPASS_ENABLED=true
OTP_BYPASS_CODE=8523
PAYMENT_BYPASS_ENABLED=true
PAYPASS_BYPASS_CODE=8523

# PayPass Configuration
PAYPASS_ENABLED=true
PAYPASS_TIMEOUT=300
PAYPASS_MAX_ATTEMPTS=3

# SMS Configuration
SMS_PROVIDER=internal
```

### 2. Database Migration

Run the migration to create the payment_sessions table:

```bash
cd backend
php artisan migrate
```

### 3. System Configuration

The system configuration is automatically loaded from `config/system.php`. This file contains all the necessary settings for OTP and PayPass functionality.

## Frontend Integration

### 1. Payment Method Selection

The PaymentPage now includes PayPass as a payment option alongside credit card and bank transfer.

### 2. PayPass Form Fields

When PayPass is selected, users will see:
- Phone number input field
- Development mode OTP bypass notice (shows code 8523)
- Clear instructions about the payment process

### 3. API Integration

The frontend automatically handles:
- Creating PayPass sessions
- OTP verification
- Payment completion
- Error handling

## How to Use PayPass

### For End Users

1. **Select PayPass** as payment method on the payment page
2. **Enter phone number** where you want to receive the OTP
3. **Receive OTP** via SMS (or use bypass code 8523 in development)
4. **Enter OTP** to complete payment
5. **Payment confirmed** and contract activated

### For Developers

1. **Enable OTP bypass** in environment configuration
2. **Use bypass code 8523** for testing without SMS
3. **Monitor logs** for debugging payment flows
4. **Test session expiration** and retry mechanisms

## API Endpoints

### Create PayPass Session
```
POST /api/portal/paypass/create-session
{
  "contract_id": "contract-uuid",
  "phone": "0501234567"
}
```

### Verify PayPass OTP
```
POST /api/portal/paypass/verify-otp
{
  "session_id": "session-uuid",
  "otp": "8523"
}
```

### Get Session Status
```
GET /api/portal/paypass/session/{sessionId}/status
```

### Cancel Session
```
POST /api/portal/paypass/session/{sessionId}/cancel
```

## Development Mode Features

### OTP Bypass
- **Code**: 8523
- **Purpose**: Skip SMS sending during development
- **Security**: Only works when `OTP_BYPASS_ENABLED=true`

### PayPass Bypass
- **Code**: 8523
- **Purpose**: Skip OTP verification during development
- **Security**: Only works when `PAYMENT_BYPASS_ENABLED=true`

## Security Features

### Rate Limiting
- OTP requests: 1 per minute per phone
- Maximum attempts: 3 per session
- Session timeout: 5 minutes

### Session Security
- Unique session tokens
- IP address tracking
- User agent logging
- Automatic expiration

### OTP Security
- 6-digit random codes
- Hashed storage
- Single-use validation
- Attempt tracking

## Troubleshooting

### Common Issues

1. **OTP Not Working**
   - Check if OTP bypass is enabled
   - Verify environment configuration
   - Check database connection

2. **Payment Session Expired**
   - Sessions expire after 5 minutes
   - Create new session if expired
   - Check server time settings

3. **Invalid OTP**
   - Ensure 6-digit format
   - Check for leading zeros
   - Verify bypass code in development

### Debug Steps

1. **Check Logs**
   ```bash
   tail -f backend/storage/logs/laravel.log
   ```

2. **Verify Configuration**
   ```bash
   php artisan config:cache
   php artisan config:clear
   ```

3. **Test OTP Bypass**
   - Use code 8523 in development
   - Check if bypass is enabled
   - Verify environment variables

## Production Deployment

### SMS Provider Integration
1. **Replace internal SMS service** with real provider
2. **Update SMS configuration** in environment
3. **Disable OTP bypass** for security
4. **Test SMS delivery** thoroughly

### Security Hardening
1. **Disable development bypasses**
2. **Enable rate limiting**
3. **Configure proper timeouts**
4. **Set up monitoring and alerts**

## Support

For technical support or questions about the PayPass system:
- Check the logs for error details
- Verify configuration settings
- Test with bypass codes in development
- Review API documentation

## Version History

- **v1.0.0**: Initial PayPass implementation
- **v1.1.0**: Added development bypass functionality
- **v1.2.0**: Enhanced security and session management