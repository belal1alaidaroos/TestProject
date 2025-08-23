import React from 'react';
import { useLanguageStore } from '@/stores/languageStore';

interface CurrencyDisplayProps {
  amount: number;
  currency?: string;
  locale?: string;
  showSymbol?: boolean;
  className?: string;
}

const CurrencyDisplay: React.FC<CurrencyDisplayProps> = ({
  amount,
  currency = 'SAR',
  locale,
  showSymbol = true,
  className = ''
}) => {
  const { language } = useLanguageStore();
  
  // Default locale based on language
  const defaultLocale = language === 'ar' ? 'ar-SA' : 'en-US';
  const currentLocale = locale || defaultLocale;

  // Currency formatting options
  const formatCurrency = (value: number, curr: string, loc: string) => {
    try {
      const formatter = new Intl.NumberFormat(loc, {
        style: 'currency',
        currency: curr,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
      
      return formatter.format(value);
    } catch (error) {
      // Fallback formatting
      return `${curr} ${value.toFixed(2)}`;
    }
  };

  // Format without currency symbol
  const formatAmount = (value: number, curr: string, loc: string) => {
    try {
      const formatter = new Intl.NumberFormat(loc, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
      
      return formatter.format(value);
    } catch (error) {
      return value.toFixed(2);
    }
  };

  const formattedValue = showSymbol 
    ? formatCurrency(amount, currency, currentLocale)
    : formatAmount(amount, currency, currentLocale);

  return (
    <span className={className}>
      {formattedValue}
    </span>
  );
};

export default CurrencyDisplay;