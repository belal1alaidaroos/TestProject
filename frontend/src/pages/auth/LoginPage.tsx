import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { authAPI } from '../../services/api';
import { useAuthStore } from '../../stores/authStore';
import { useLanguageStore } from '../../stores/languageStore';

interface LoginFormData {
  phone: string;
}

const LoginPage: React.FC = () => {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const { language, toggleLanguage } = useLanguageStore();
  const { isLoading, error, clearError } = useAuthStore();
  
  const [otpRequested, setOtpRequested] = useState(false);
  const [otpExpiry, setOtpExpiry] = useState<number | null>(null);
  const [countdown, setCountdown] = useState<number | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors },
    watch,
  } = useForm<LoginFormData>();

  const phone = watch('phone');

  // Countdown timer for OTP expiry
  React.useEffect(() => {
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

  const handleRequestOtp = async (data: LoginFormData) => {
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

  const handleResendOtp = async () => {
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

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-md w-full space-y-8">
        <div>
          <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
            {t('auth.login')}
          </h2>
          <p className="mt-2 text-center text-sm text-gray-600">
            {t('auth.request_otp')}
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

        <form className="mt-8 space-y-6" onSubmit={handleSubmit(handleRequestOtp)}>
          <div>
            <label htmlFor="phone" className="form-label">
              {t('auth.phone')}
            </label>
            <input
              id="phone"
              type="tel"
              autoComplete="tel"
              className={`input-field ${errors.phone ? 'border-red-500' : ''}`}
              placeholder="+966501234567"
              {...register('phone', {
                required: t('validation.required'),
                pattern: {
                  value: /^\+966[0-9]{9}$/,
                  message: t('validation.invalid_phone'),
                },
              })}
            />
            {errors.phone && (
              <p className="form-error">{errors.phone.message}</p>
            )}
          </div>

          {error && (
            <div className="rounded-md bg-red-50 p-4">
              <div className="text-sm text-red-700">{error}</div>
            </div>
          )}

          <div>
            <button
              type="submit"
              disabled={isLoading}
              className="btn-primary w-full flex justify-center py-2 px-4"
            >
              {isLoading ? t('common.loading') : t('auth.request_otp')}
            </button>
          </div>

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
        </form>
      </div>
    </div>
  );
};

export default LoginPage;