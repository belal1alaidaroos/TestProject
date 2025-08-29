import React, { createContext, useContext, useState, ReactNode } from 'react';
import { useLanguageStore } from '../stores/languageStore';

interface I18nContextType {
  locale: string;
  dir: 'ltr' | 'rtl';
  t: (key: string) => string;
  setLocale: (locale: string) => void;
  language: 'en' | 'ar';
  toggleLanguage: () => void;
}

export const I18nContext = createContext<I18nContextType | undefined>(undefined);

interface I18nProviderProps {
  children: ReactNode;
}

export const I18nProvider: React.FC<I18nProviderProps> = ({ children }) => {
  const [locale, setLocaleState] = useState('en');
  const { language, toggleLanguage } = useLanguageStore();

  const setLocale = (newLocale: string) => {
    setLocaleState(newLocale);
  };

  const dir: 'ltr' | 'rtl' = locale === 'ar' ? 'rtl' : 'ltr';

  // Simple translation function - you can expand this with actual translations
  const t = (key: string): string => {
    // For now, return the key as placeholder
    // You can implement actual translation logic here
    return key;
  };

  const value: I18nContextType = {
    locale,
    dir,
    t,
    setLocale,
    language,
    toggleLanguage,
  };

  return (
    <I18nContext.Provider value={value}>
      {children}
    </I18nContext.Provider>
  );
};

export const useI18n = (): I18nContextType => {
  const context = useContext(I18nContext);
  if (context === undefined) {
    throw new Error('useI18n must be used within an I18nProvider');
  }
  return context;
};