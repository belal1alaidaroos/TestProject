import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { authAPI } from '../../services/api';
import { useAuthStore } from '../../stores/authStore';
import { useLanguageStore } from '../../stores/languageStore';

interface LoginFormData {
  email: string;
  password: string;
}

interface OtpFormData {
  phone: string;
}

interface SignupFormData {
  name: string;
  email: string;
  phone: string;
  password: string;
  password_confirmation: string;
  company_name_en: string;
  company_name_ar: string;
  tax_number: string;
  commercial_license: string;
  contact_person: string;
}

type AuthMode = 'login' | 'otp' | 'signup';
type PortalType = 'Customer' | 'Agency' | 'Admin' | 'Internal';

const LoginPage: React.FC = () => {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const { language, toggleLanguage } = useLanguageStore();
  const { isLoading, error, clearError, login } = useAuthStore();
  
  const [authMode, setAuthMode] = useState<AuthMode>('login');
  const [portalType, setPortalType] = useState<PortalType>('Customer');
  const [portalConfig, setPortalConfig] = useState<any>(null);
  const [otpRequested, setOtpRequested] = useState(false);
  const [otpExpiry, setOtpExpiry] = useState<number | null>(null);
  const [countdown, setCountdown] = useState<number | null>(null);

  const loginForm = useForm<LoginFormData>();
  const otpForm = useForm<OtpFormData>();
  const signupForm = useForm<SignupFormData>();

  // Get portal type from URL params or default to Customer
  useEffect(() => {
    const portal = searchParams.get('portal') as PortalType;
    if (portal && ['Customer', 'Agency', 'Admin', 'Internal'].includes(portal)) {
      setPortalType(portal);
    }
  }, [searchParams]);

  // Fetch portal configuration
  useEffect(() => {
    const fetchPortalConfig = async () => {
      try {
        const response = await authAPI.checkPortalAccess(portalType);
        if (response.data.success) {
          setPortalConfig(response.data.data);
        }
      } catch (error) {
        console.error('Failed to fetch portal config:', error);
      }
    };

    fetchPortalConfig();
  }, [portalType]);

  // Countdown timer for OTP expiry
  useEffect(() => {
    if (otpExpiry && countdown !== null) {
      const timer = setInterval(() => {
        setCountdown(prev => {
          if (prev === null || prev <= 0) {
            clearInterval(timer);
            return 0;
          }
          return prev - 1;
        });
      }, 1000);

      return () => clearInterval(timer);
    }
  }, [otpExpiry, countdown]);

  const formatTime = (seconds: number): string => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  };

  const handleEmailLogin = async (data: LoginFormData) => {
    try {
      clearError();
      
      // Ensure portalType is set
      if (!portalType) {
        console.error('Portal type is not set');
        return;
      }
      
      console.log('Attempting login with portal type:', portalType);
      
      const response = await authAPI.emailLogin(data.email, data.password, portalType);
      
      if (response.data.success) {
        const { user, token } = response.data.data;
        login(user, token);
        navigate(`/${portalType.toLowerCase()}`);
      }
    } catch (error: any) {
      console.error('Email login failed:', error);
      
      // Handle validation errors from backend
      if (error.response?.data?.errors) {
        console.log('Backend validation errors:', error.response.data.errors);
        Object.keys(error.response.data.errors).forEach(field => {
          loginForm.setError(field as keyof LoginFormData, {
            type: 'server',
            message: error.response.data.errors[field][0]
          });
        });
      } else if (error.response?.data?.message) {
        console.log('Backend error message:', error.response.data.message);
      }
    }
  };

  const handleRequestOtp = async (data: OtpFormData) => {
    try {
      clearError();
      const response = await authAPI.requestOtp(data.phone);
      
      if (response.data.success) {
        setOtpRequested(true);
        setOtpExpiry(response.data.data.expires_in);
        setCountdown(response.data.data.expires_in);
        navigate('/verify-otp', { state: { phone: data.phone } });
      }
    } catch (error: any) {
      console.error('OTP request failed:', error);
    }
  };

  const handleCustomerSignup = async (data: SignupFormData) => {
    try {
      clearError();
      
      // Ensure all required fields are present and properly formatted
      const signupData = {
        name: data.name.trim(),
        email: data.email.trim().toLowerCase(),
        phone: data.phone.trim(),
        password: data.password,
        password_confirmation: data.password_confirmation,
        company_name_en: data.company_name_en.trim(),
        company_name_ar: data.company_name_ar?.trim() || '',
        tax_number: data.tax_number?.trim() || '',
        commercial_license: data.commercial_license?.trim() || '',
        contact_person: data.contact_person.trim()
      };
      
      console.log('Sending signup data:', signupData);
      
      const response = await authAPI.customerSignup(signupData);
      
      if (response.data.success) {
        const { user, token } = response.data.data;
        login(user, token);
        navigate('/customer');
      }
    } catch (error: any) {
      console.error('Customer signup failed:', error);
      
      // Handle validation errors from backend
      if (error.response?.data?.errors) {
        console.log('Backend validation errors:', error.response.data.errors);
        // You can set these errors to display to the user
        Object.keys(error.response.data.errors).forEach(field => {
          signupForm.setError(field as keyof SignupFormData, {
            type: 'server',
            message: error.response.data.errors[field][0]
          });
        });
      } else if (error.response?.data?.message) {
        console.log('Backend error message:', error.response.data.message);
      }
    }
  };

  const handleSocialLogin = async (provider: string) => {
    // This would integrate with actual social login providers
    // For now, we'll show a placeholder
    alert(`${provider} login integration would be implemented here`);
  };

  const handleResendOtp = async () => {
    const phone = otpForm.watch('phone');
    if (!phone) return;
    
    try {
      clearError();
      const response = await authAPI.requestOtp(phone);
      
      if (response.data.success) {
        setOtpExpiry(response.data.data.expires_in);
        setCountdown(response.data.data.expires_in);
      }
    } catch (error: any) {
      console.error('Resend OTP failed:', error);
    }
  };

  const renderPortalTypeSelector = () => (
    <div className="mb-6">
      <label className="block text-sm font-medium text-gray-700 mb-2">
        {t('auth.select_portal')}
      </label>
      <select
        value={portalType}
        onChange={(e) => setPortalType(e.target.value as PortalType)}
        className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
      >
        <option value="Customer">{t('auth.customer_portal')}</option>
        <option value="Agency">{t('auth.agency_portal')}</option>
        <option value="Admin">{t('auth.admin_portal')}</option>
        <option value="Internal">{t('auth.internal_portal')}</option>
      </select>
    </div>
  );

  const renderAuthModeSelector = () => {
    if (!portalConfig) return null;

    if (portalType === 'Customer') {
      return (
        <div className="mb-6">
          <div className="flex space-x-2 mb-4">
            <button
              type="button"
              onClick={() => setAuthMode('login')}
              className={`flex-1 py-2 px-4 rounded-md font-medium ${
                authMode === 'login'
                  ? 'bg-primary-600 text-white'
                  : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
              }`}
            >
              {t('auth.login')}
            </button>
            <button
              type="button"
              onClick={() => setAuthMode('signup')}
              className={`flex-1 py-2 px-4 rounded-md font-medium ${
                authMode === 'signup'
                  ? 'bg-primary-600 text-white'
                  : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
              }`}
            >
              {t('auth.signup')}
            </button>
            <button
              type="button"
              onClick={() => setAuthMode('otp')}
              className={`flex-1 py-2 px-4 rounded-md font-medium ${
                authMode === 'otp'
                  ? 'bg-primary-600 text-white'
                  : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
              }`}
            >
              {t('auth.mobile_otp')}
            </button>
          </div>
        </div>
      );
    }

    return (
      <div className="mb-6">
        <div className="text-center">
          <span className="text-sm text-gray-600">
            {t('auth.email_password_only')}
          </span>
        </div>
      </div>
    );
  };

  const renderEmailLoginForm = () => (
    <form onSubmit={loginForm.handleSubmit(handleEmailLogin)} className="space-y-4">
      <div>
        <label htmlFor="email" className="block text-sm font-medium text-gray-700">
          {t('auth.email')}
        </label>
        <input
          id="email"
          type="email"
          autoComplete="email"
          className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
          placeholder="user@example.com"
          {...loginForm.register('email', {
            required: t('validation.required'),
            pattern: {
              value: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
              message: t('validation.invalid_email'),
            },
          })}
        />
        {loginForm.formState.errors.email && (
          <p className="mt-1 text-sm text-red-600">{loginForm.formState.errors.email.message}</p>
        )}
      </div>

      <div>
        <label htmlFor="password" className="block text-sm font-medium text-gray-700">
          {t('auth.password')}
        </label>
        <input
          id="password"
          type="password"
          autoComplete="current-password"
          className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
          placeholder="••••••••"
          {...loginForm.register('password', {
            required: t('validation.required'),
            minLength: {
              value: 6,
              message: t('validation.password_min'),
            },
          })}
        />
        {loginForm.formState.errors.password && (
          <p className="mt-1 text-sm text-red-600">{loginForm.formState.errors.password.message}</p>
        )}
      </div>

      <button
        type="submit"
        disabled={isLoading}
        className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
      >
        {isLoading ? t('common.loading') : t('auth.login')}
      </button>
    </form>
  );

  const renderCustomerSignupForm = () => (
    <form onSubmit={signupForm.handleSubmit(handleCustomerSignup)} className="space-y-4">
      <div>
        <label htmlFor="signup_name" className="block text-sm font-medium text-gray-700">
          {t('auth.full_name')}
        </label>
        <input
          id="signup_name"
          type="text"
          autoComplete="name"
          className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
          placeholder="John Doe"
          {...signupForm.register('name', {
            required: t('validation.required'),
          })}
        />
        {signupForm.formState.errors.name && (
          <p className="mt-1 text-sm text-red-600">{signupForm.formState.errors.name.message}</p>
        )}
      </div>

      <div>
        <label htmlFor="signup_email" className="block text-sm font-medium text-gray-700">
          {t('auth.email')}
        </label>
        <input
          id="signup_email"
          type="email"
          autoComplete="email"
          className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
          placeholder="user@example.com"
          {...signupForm.register('email', {
            required: t('validation.required'),
            pattern: {
              value: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
              message: t('validation.invalid_email'),
            },
          })}
        />
        {signupForm.formState.errors.email && (
          <p className="mt-1 text-sm text-red-600">{signupForm.formState.errors.email.message}</p>
        )}
      </div>

      <div>
        <label htmlFor="signup_phone" className="block text-sm font-medium text-gray-700">
          {t('auth.mobile_number')} *
        </label>
        <input
          id="signup_phone"
          type="tel"
          autoComplete="tel"
          className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
          placeholder="+966501234567"
          {...signupForm.register('phone', {
            required: t('validation.required'),
            pattern: {
              value: /^\+966[0-9]{9}$/,
              message: t('validation.invalid_phone'),
            },
          })}
        />
        {signupForm.formState.errors.phone && (
          <p className="mt-1 text-sm text-red-600">{signupForm.formState.errors.phone.message}</p>
        )}
      </div>

      <div>
        <label htmlFor="signup_company" className="block text-sm font-medium text-gray-700">
          {t('auth.company_name_en')}
        </label>
        <input
          id="signup_company"
          type="text"
          className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
          placeholder="Company Name"
          {...signupForm.register('company_name_en', {
            required: t('validation.required'),
          })}
        />
        {signupForm.formState.errors.company_name_en && (
          <p className="mt-1 text-sm text-red-600">{signupForm.formState.errors.company_name_en.message}</p>
        )}
      </div>

      <div>
        <label htmlFor="signup_contact" className="block text-sm font-medium text-gray-700">
          {t('auth.contact_person')}
        </label>
        <input
          id="signup_contact"
          type="text"
          className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
          placeholder="Contact Person Name"
          {...signupForm.register('contact_person', {
            required: t('validation.required'),
          })}
        />
        {signupForm.formState.errors.contact_person && (
          <p className="mt-1 text-sm text-red-600">{signupForm.formState.errors.contact_person.message}</p>
        )}
      </div>

      <div>
        <label htmlFor="signup_company_ar" className="block text-sm font-medium text-gray-700">
          {t('auth.company_name_ar')} (Optional)
        </label>
        <input
          id="signup_company_ar"
          type="text"
          className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
          placeholder="اسم الشركة"
          {...signupForm.register('company_name_ar')}
        />
        {signupForm.formState.errors.company_name_ar && (
          <p className="mt-1 text-sm text-red-600">{signupForm.formState.errors.company_name_ar.message}</p>
        )}
      </div>

      <div>
        <label htmlFor="signup_tax_number" className="block text-sm font-medium text-gray-700">
          {t('auth.tax_number')} (Optional)
        </label>
        <input
          id="signup_tax_number"
          type="text"
          className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
          placeholder="Tax Number"
          {...signupForm.register('tax_number')}
        />
        {signupForm.formState.errors.tax_number && (
          <p className="mt-1 text-sm text-red-600">{signupForm.formState.errors.tax_number.message}</p>
        )}
      </div>

      <div>
        <label htmlFor="signup_commercial_license" className="block text-sm font-medium text-gray-700">
          {t('auth.commercial_license')} (Optional)
        </label>
        <input
          id="signup_commercial_license"
          type="text"
          className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
          placeholder="Commercial License"
          {...signupForm.register('commercial_license')}
        />
        {signupForm.formState.errors.commercial_license && (
          <p className="mt-1 text-sm text-red-600">{signupForm.formState.errors.commercial_license.message}</p>
        )}
      </div>

      <div>
        <label htmlFor="signup_password" className="block text-sm font-medium text-gray-700">
          {t('auth.password')}
        </label>
        <input
          id="signup_password"
          type="password"
          autoComplete="new-password"
          className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
          placeholder="••••••••"
          {...signupForm.register('password', {
            required: t('validation.required'),
            minLength: {
              value: 8,
              message: t('validation.password_min'),
            },
          })}
        />
        {signupForm.formState.errors.password && (
          <p className="mt-1 text-sm text-red-600">{signupForm.formState.errors.password.message}</p>
        )}
      </div>

      <div>
        <label htmlFor="signup_password_confirmation" className="block text-sm font-medium text-gray-700">
          {t('auth.confirm_password')}
        </label>
        <input
          id="signup_password_confirmation"
          type="password"
          autoComplete="new-password"
          className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
          placeholder="••••••••"
          {...signupForm.register('password_confirmation', {
            required: t('validation.required'),
            validate: (value) => value === signupForm.watch('password') || t('validation.password_mismatch'),
          })}
        />
        {signupForm.formState.errors.password_confirmation && (
          <p className="mt-1 text-sm text-red-600">{signupForm.formState.errors.password_confirmation.message}</p>
        )}
      </div>

      <button
        type="submit"
        disabled={isLoading}
        className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
      >
        {isLoading ? t('common.loading') : t('auth.signup')}
      </button>
    </form>
  );

  const renderOtpForm = () => (
    <form onSubmit={otpForm.handleSubmit(handleRequestOtp)} className="space-y-4">
      <div>
        <label htmlFor="phone" className="block text-sm font-medium text-gray-700">
          {t('auth.mobile_number')}
        </label>
        <input
          id="phone"
          type="tel"
          autoComplete="tel"
          className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
          placeholder="+966501234567"
          {...otpForm.register('phone', {
            required: t('validation.required'),
            pattern: {
              value: /^\+966[0-9]{9}$/,
              message: t('validation.invalid_phone'),
            },
          })}
        />
        {otpForm.formState.errors.phone && (
          <p className="mt-1 text-sm text-red-600">{otpForm.formState.errors.phone.message}</p>
        )}
      </div>

      <button
        type="submit"
        disabled={isLoading}
        className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
      >
        {isLoading ? t('common.loading') : t('auth.request_otp')}
      </button>
    </form>
  );

  const renderSocialLoginButtons = () => {
    if (!portalConfig?.social_providers) return null;

    return (
      <div className="space-y-3">
        <div className="relative">
          <div className="absolute inset-0 flex items-center">
            <div className="w-full border-t border-gray-300" />
          </div>
          <div className="relative flex justify-center text-sm">
            <span className="px-2 bg-white text-gray-500">{t('auth.or_continue_with')}</span>
          </div>
        </div>

        <div className="grid grid-cols-2 gap-3">
          {portalConfig.social_providers.map((provider: string) => (
            <button
              key={provider}
              type="button"
              onClick={() => handleSocialLogin(provider)}
              className="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
            >
              <span className="capitalize">{provider}</span>
            </button>
          ))}
        </div>
      </div>
    );
  };

  const renderCurrentForm = () => {
    if (portalType === 'Customer') {
      switch (authMode) {
        case 'login':
          return (
            <>
              {renderEmailLoginForm()}
              {renderSocialLoginButtons()}
            </>
          );
        case 'signup':
          return renderCustomerSignupForm();
        case 'otp':
          return renderOtpForm();
        default:
          return renderEmailLoginForm();
      }
    } else {
      return renderEmailLoginForm();
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-md w-full space-y-8">
        <div>
          <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
            {t('auth.welcome_to')} {portalType} {t('auth.portal')}
          </h2>
          <p className="mt-2 text-center text-sm text-gray-600">
            {portalType === 'Customer' 
              ? t('auth.customer_portal_description')
              : t('auth.other_portal_description')
            }
          </p>
        </div>

        {/* Language Toggle */}
        <div className="flex justify-center">
          <button
            onClick={toggleLanguage}
            className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
          >
            {language === 'en' ? 'العربية' : 'English'}
          </button>
        </div>

        {/* Portal Type Selector */}
        {renderPortalTypeSelector()}

        {/* Auth Mode Selector */}
        {renderAuthModeSelector()}

        {/* Current Form */}
        {renderCurrentForm()}

        {error && (
          <div className="rounded-md bg-red-50 p-4">
            <div className="text-sm text-red-700">{error}</div>
          </div>
        )}

        {otpRequested && countdown !== null && countdown > 0 && (
          <div className="text-center">
            <p className="text-sm text-gray-600">
              {t('auth.otp_expires_in', { time: formatTime(countdown) })}
            </p>
            <button
              type="button"
              onClick={handleResendOtp}
              disabled={countdown > 0}
              className="mt-2 text-sm text-primary-600 hover:text-primary-500 disabled:text-gray-400"
            >
              {t('auth.resend_otp')}
            </button>
          </div>
        )}
      </div>
    </div>
  );
};

export default LoginPage;