import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { GlobeAltIcon, CurrencyDollarIcon, ClockIcon, CalendarIcon } from '@heroicons/react/24/outline';
import { api } from '../services/api';

interface LocalizationSettingsProps {
  className?: string;
}

interface Language {
  name: string;
  native_name: string;
  direction: string;
  flag: string;
}

interface Currency {
  name: string;
  symbol: string;
  position: string;
  decimal_places: number;
}

interface Timezone {
  [key: string]: string;
}

interface DateFormat {
  [key: string]: string;
}

interface UserSettings {
  language: {
    current: string;
    info: Language;
    supported: { [key: string]: Language };
  };
  currency: {
    current: string;
    info: Currency;
    supported: { [key: string]: Currency };
  };
  timezone: {
    current: string;
    info: string;
    supported: Timezone;
  };
  date_format: {
    current: string;
    example: string;
    supported: DateFormat;
  };
}

const LocalizationSettings: React.FC<LocalizationSettingsProps> = ({ className = '' }) => {
  const { t, i18n } = useTranslation();
  const [settings, setSettings] = useState<UserSettings | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  useEffect(() => {
    loadUserSettings();
  }, []);

  const loadUserSettings = async () => {
    try {
      setIsLoading(true);
      const response = await api.get('/localization/user-settings');
      
      if (response.data.success) {
        setSettings(response.data.data);
      }
    } catch (error: any) {
      setError(error.response?.data?.message || t('localization.loadError'));
    } finally {
      setIsLoading(false);
    }
  };

  const updateLanguage = async (language: string) => {
    try {
      setIsLoading(true);
      setError(null);
      setSuccess(null);

      const response = await api.post('/localization/set-language', { language });
      
      if (response.data.success) {
        setSuccess(t('localization.languageUpdated'));
        // Update i18n language
        i18n.changeLanguage(language);
        // Reload settings
        await loadUserSettings();
      }
    } catch (error: any) {
      setError(error.response?.data?.message || t('localization.updateError'));
    } finally {
      setIsLoading(false);
    }
  };

  const updateCurrency = async (currency: string) => {
    try {
      setIsLoading(true);
      setError(null);
      setSuccess(null);

      const response = await api.post('/localization/set-currency', { currency });
      
      if (response.data.success) {
        setSuccess(t('localization.currencyUpdated'));
        await loadUserSettings();
      }
    } catch (error: any) {
      setError(error.response?.data?.message || t('localization.updateError'));
    } finally {
      setIsLoading(false);
    }
  };

  const updateTimezone = async (timezone: string) => {
    try {
      setIsLoading(true);
      setError(null);
      setSuccess(null);

      const response = await api.post('/localization/set-timezone', { timezone });
      
      if (response.data.success) {
        setSuccess(t('localization.timezoneUpdated'));
        await loadUserSettings();
      }
    } catch (error: any) {
      setError(error.response?.data?.message || t('localization.updateError'));
    } finally {
      setIsLoading(false);
    }
  };

  const updateDateFormat = async (dateFormat: string) => {
    try {
      setIsLoading(true);
      setError(null);
      setSuccess(null);

      const response = await api.post('/localization/set-date-format', { date_format: dateFormat });
      
      if (response.data.success) {
        setSuccess(t('localization.dateFormatUpdated'));
        await loadUserSettings();
      }
    } catch (error: any) {
      setError(error.response?.data?.message || t('localization.updateError'));
    } finally {
      setIsLoading(false);
    }
  };

  if (isLoading && !settings) {
    return (
      <div className={`animate-pulse ${className}`}>
        <div className="bg-white rounded-lg shadow p-6">
          <div className="space-y-4">
            {[1, 2, 3, 4].map((i) => (
              <div key={i} className="flex items-center space-x-4">
                <div className="w-8 h-8 bg-gray-200 rounded"></div>
                <div className="flex-1">
                  <div className="h-4 bg-gray-200 rounded w-1/4"></div>
                  <div className="h-3 bg-gray-200 rounded w-1/2 mt-2"></div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    );
  }

  if (!settings) {
    return (
      <div className={`bg-red-50 border border-red-200 rounded-lg p-4 ${className}`}>
        <p className="text-red-600">{t('localization.loadError')}</p>
      </div>
    );
  }

  return (
    <div className={`space-y-6 ${className}`}>
      {/* Success/Error Messages */}
      {success && (
        <div className="bg-green-50 border border-green-200 rounded-lg p-4">
          <p className="text-green-600">{success}</p>
        </div>
      )}

      {error && (
        <div className="bg-red-50 border border-red-200 rounded-lg p-4">
          <p className="text-red-600">{error}</p>
        </div>
      )}

      {/* Language Settings */}
      <div className="bg-white rounded-lg shadow p-6">
        <div className="flex items-center space-x-3 mb-4">
          <GlobeAltIcon className="w-6 h-6 text-blue-500" />
          <h3 className="text-lg font-medium text-gray-900">{t('localization.language')}</h3>
        </div>
        
        <div className="space-y-3">
          {Object.entries(settings.language.supported).map(([code, language]) => (
            <div
              key={code}
              className={`flex items-center justify-between p-3 border rounded-lg cursor-pointer transition-colors ${
                settings.language.current === code
                  ? 'border-blue-500 bg-blue-50'
                  : 'border-gray-200 hover:border-gray-300'
              }`}
              onClick={() => updateLanguage(code)}
            >
              <div className="flex items-center space-x-3">
                <span className="text-2xl">{language.flag}</span>
                <div>
                  <p className="font-medium text-gray-900">{language.name}</p>
                  <p className="text-sm text-gray-600">{language.native_name}</p>
                </div>
              </div>
              
              {settings.language.current === code && (
                <div className="w-5 h-5 bg-blue-500 rounded-full flex items-center justify-center">
                  <div className="w-2 h-2 bg-white rounded-full"></div>
                </div>
              )}
            </div>
          ))}
        </div>
      </div>

      {/* Currency Settings */}
      <div className="bg-white rounded-lg shadow p-6">
        <div className="flex items-center space-x-3 mb-4">
          <CurrencyDollarIcon className="w-6 h-6 text-green-500" />
          <h3 className="text-lg font-medium text-gray-900">{t('localization.currency')}</h3>
        </div>
        
        <div className="space-y-3">
          {Object.entries(settings.currency.supported).map(([code, currency]) => (
            <div
              key={code}
              className={`flex items-center justify-between p-3 border rounded-lg cursor-pointer transition-colors ${
                settings.currency.current === code
                  ? 'border-green-500 bg-green-50'
                  : 'border-gray-200 hover:border-gray-300'
              }`}
              onClick={() => updateCurrency(code)}
            >
              <div className="flex items-center space-x-3">
                <div className="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                  <span className="text-sm font-medium">{currency.symbol}</span>
                </div>
                <div>
                  <p className="font-medium text-gray-900">{currency.name}</p>
                  <p className="text-sm text-gray-600">
                    {t('localization.symbol')}: {currency.symbol}
                  </p>
                </div>
              </div>
              
              {settings.currency.current === code && (
                <div className="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center">
                  <div className="w-2 h-2 bg-white rounded-full"></div>
                </div>
              )}
            </div>
          ))}
        </div>
      </div>

      {/* Timezone Settings */}
      <div className="bg-white rounded-lg shadow p-6">
        <div className="flex items-center space-x-3 mb-4">
          <ClockIcon className="w-6 h-6 text-purple-500" />
          <h3 className="text-lg font-medium text-gray-900">{t('localization.timezone')}</h3>
        </div>
        
        <div className="space-y-3">
          {Object.entries(settings.timezone.supported).map(([code, timezone]) => (
            <div
              key={code}
              className={`flex items-center justify-between p-3 border rounded-lg cursor-pointer transition-colors ${
                settings.timezone.current === code
                  ? 'border-purple-500 bg-purple-50'
                  : 'border-gray-200 hover:border-gray-300'
              }`}
              onClick={() => updateTimezone(code)}
            >
              <div className="flex items-center space-x-3">
                <div className="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                  <ClockIcon className="w-4 h-4 text-gray-600" />
                </div>
                <div>
                  <p className="font-medium text-gray-900">{timezone}</p>
                  <p className="text-sm text-gray-600">{code}</p>
                </div>
              </div>
              
              {settings.timezone.current === code && (
                <div className="w-5 h-5 bg-purple-500 rounded-full flex items-center justify-center">
                  <div className="w-2 h-2 bg-white rounded-full"></div>
                </div>
              )}
            </div>
          ))}
        </div>
      </div>

      {/* Date Format Settings */}
      <div className="bg-white rounded-lg shadow p-6">
        <div className="flex items-center space-x-3 mb-4">
          <CalendarIcon className="w-6 h-6 text-orange-500" />
          <h3 className="text-lg font-medium text-gray-900">{t('localization.dateFormat')}</h3>
        </div>
        
        <div className="space-y-3">
          {Object.entries(settings.date_format.supported).map(([format, example]) => (
            <div
              key={format}
              className={`flex items-center justify-between p-3 border rounded-lg cursor-pointer transition-colors ${
                settings.date_format.current === format
                  ? 'border-orange-500 bg-orange-50'
                  : 'border-gray-200 hover:border-gray-300'
              }`}
              onClick={() => updateDateFormat(format)}
            >
              <div className="flex items-center space-x-3">
                <div className="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                  <CalendarIcon className="w-4 h-4 text-gray-600" />
                </div>
                <div>
                  <p className="font-medium text-gray-900">{format}</p>
                  <p className="text-sm text-gray-600">{t('localization.example')}: {example}</p>
                </div>
              </div>
              
              {settings.date_format.current === format && (
                <div className="w-5 h-5 bg-orange-500 rounded-full flex items-center justify-center">
                  <div className="w-2 h-2 bg-white rounded-full"></div>
                </div>
              )}
            </div>
          ))}
        </div>
      </div>

      {/* Current Settings Summary */}
      <div className="bg-gray-50 rounded-lg p-6">
        <h3 className="text-lg font-medium text-gray-900 mb-4">{t('localization.currentSettings')}</h3>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div className="flex items-center space-x-3">
            <GlobeAltIcon className="w-5 h-5 text-blue-500" />
            <div>
              <p className="text-sm text-gray-600">{t('localization.language')}</p>
              <p className="font-medium">{settings.language.info.name}</p>
            </div>
          </div>
          
          <div className="flex items-center space-x-3">
            <CurrencyDollarIcon className="w-5 h-5 text-green-500" />
            <div>
              <p className="text-sm text-gray-600">{t('localization.currency')}</p>
              <p className="font-medium">{settings.currency.info.name}</p>
            </div>
          </div>
          
          <div className="flex items-center space-x-3">
            <ClockIcon className="w-5 h-5 text-purple-500" />
            <div>
              <p className="text-sm text-gray-600">{t('localization.timezone')}</p>
              <p className="font-medium">{settings.timezone.info}</p>
            </div>
          </div>
          
          <div className="flex items-center space-x-3">
            <CalendarIcon className="w-5 h-5 text-orange-500" />
            <div>
              <p className="text-sm text-gray-600">{t('localization.dateFormat')}</p>
              <p className="font-medium">{settings.date_format.current}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default LocalizationSettings;