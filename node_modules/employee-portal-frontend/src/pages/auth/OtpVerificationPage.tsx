import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate, useLocation } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { useAuthStore } from '../../stores/authStore';
import { authAPI } from '../../services/api';

interface OtpFormData {
  code: string;
}

const OtpVerificationPage: React.FC = () => {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const location = useLocation();
  const { login, isLoading, error, clearError } = useAuthStore();
  
  const [countdown, setCountdown] = useState<number>(300); // 5 minutes
  const [canResend, setCanResend] = useState<boolean>(false);
  const [resendLoading, setResendLoading] = useState<boolean>(false);

  const phone = location.state?.phone;

  const {
    register,
    handleSubmit,
    formState: { errors },
    watch,
  } = useForm<OtpFormData>();

  const code = watch('code');

  // Countdown timer
  useEffect(() => {
    if (countdown > 0) {
      const timer = setInterval(() => {
        setCountdown(prev => prev - 1);
      }, 1000);

      return () => clearInterval(timer);
    } else {
      setCanResend(true);
    }
  }, [countdown]);

  const formatTime = (seconds: number): string => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  };

  const handleVerifyOtp = async (data: OtpFormData) => {
    if (!phone) {
      navigate('/login');
      return;
    }

    try {
      clearError();
      await login(phone, data.code);
      // Navigation will be handled by the App component based on user type
    } catch (error: any) {
      console.error('OTP verification failed:', error);
    }
  };

  const handleResendOtp = async () => {
    if (!phone) return;
    
    try {
      setResendLoading(true);
      clearError();
      await authAPI.requestOtp(phone);
      setCountdown(300);
      setCanResend(false);
    } catch (error: any) {
      console.error('Resend OTP failed:', error);
    } finally {
      setResendLoading(false);
    }
  };

  const handleBackToLogin = () => {
    navigate('/login');
  };

  if (!phone) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div className="max-w-md w-full space-y-8">
          <div className="text-center">
            <h2 className="text-2xl font-bold text-gray-900">
              {t('auth.invalid_otp')}
            </h2>
            <p className="mt-2 text-sm text-gray-600">
              Please go back to login
            </p>
            <button
              onClick={handleBackToLogin}
              className="mt-4 btn-primary"
            >
              {t('common.back')}
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-md w-full space-y-8">
        <div>
          <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
            {t('auth.verify_otp')}
          </h2>
          <p className="mt-2 text-center text-sm text-gray-600">
            {t('auth.otp_sent')}
          </p>
          <p className="mt-1 text-center text-sm text-gray-500">
            {phone}
          </p>
          {/* Development OTP Bypass Notice */}
          {process.env.NODE_ENV === 'development' && (
            <div className="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
              <p className="text-xs text-yellow-800 text-center">
                <strong>Testing Mode:</strong> Use code <code className="bg-yellow-100 px-1 rounded">8523</code> to bypass OTP verification
              </p>
            </div>
          )}
        </div>

        <form className="mt-8 space-y-6" onSubmit={handleSubmit(handleVerifyOtp)}>
          <div>
            <label htmlFor="code" className="form-label">
              {t('auth.otp')}
            </label>
            <input
              id="code"
              type="text"
              autoComplete="one-time-code"
              className={`input-field text-center text-2xl tracking-widest ${errors.code ? 'border-red-500' : ''}`}
              placeholder="0000"
              maxLength={4}
              {...register('code', {
                required: t('validation.required'),
                pattern: {
                  value: /^[0-9]{4}$/,
                  message: t('validation.invalid_otp'),
                },
              })}
            />
            {errors.code && (
              <p className="form-error">{errors.code.message}</p>
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
              disabled={isLoading || !code || code.length !== 4}
              className="btn-primary w-full flex justify-center py-2 px-4"
            >
              {isLoading ? t('common.loading') : t('auth.verify_otp')}
            </button>
          </div>

          {/* Countdown and Resend */}
          <div className="text-center">
            {countdown > 0 ? (
              <p className="text-sm text-gray-600">
                {t('auth.otp_expires_in', { time: formatTime(countdown) })}
              </p>
            ) : (
              <button
                type="button"
                onClick={handleResendOtp}
                disabled={resendLoading}
                className="text-sm text-primary-600 hover:text-primary-500 disabled:text-gray-400"
              >
                {resendLoading ? t('common.loading') : t('auth.resend_otp')}
              </button>
            )}
          </div>

          {/* Back to Login */}
          <div className="text-center">
            <button
              type="button"
              onClick={handleBackToLogin}
              className="text-sm text-gray-500 hover:text-gray-700"
            >
              {t('common.back')} {t('auth.login')}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default OtpVerificationPage;