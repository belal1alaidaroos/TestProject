import React from 'react';
import { useLanguageStore } from '@/stores/languageStore';

interface DateDisplayProps {
  date: string | Date;
  format?: 'short' | 'long' | 'relative' | 'custom';
  customFormat?: string;
  locale?: string;
  className?: string;
}

const DateDisplay: React.FC<DateDisplayProps> = ({
  date,
  format = 'short',
  customFormat,
  locale,
  className = ''
}) => {
  const { language } = useLanguageStore();
  
  // Default locale based on language
  const defaultLocale = language === 'ar' ? 'ar-SA' : 'en-US';
  const currentLocale = locale || defaultLocale;

  const formatDate = (dateValue: string | Date) => {
    const dateObj = typeof dateValue === 'string' ? new Date(dateValue) : dateValue;
    
    if (isNaN(dateObj.getTime())) {
      return 'Invalid Date';
    }

    try {
      switch (format) {
        case 'short':
          return new Intl.DateTimeFormat(currentLocale, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
          }).format(dateObj);

        case 'long':
          return new Intl.DateTimeFormat(currentLocale, {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            weekday: 'long',
          }).format(dateObj);

        case 'relative':
          return getRelativeTimeString(dateObj, currentLocale);

        case 'custom':
          if (customFormat) {
            return new Intl.DateTimeFormat(currentLocale, {
              ...parseCustomFormat(customFormat)
            }).format(dateObj);
          }
          return dateObj.toLocaleDateString(currentLocale);

        default:
          return dateObj.toLocaleDateString(currentLocale);
      }
    } catch (error) {
      return dateObj.toLocaleDateString();
    }
  };

  const getRelativeTimeString = (date: Date, loc: string) => {
    const now = new Date();
    const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);
    
    if (diffInSeconds < 60) {
      return new Intl.RelativeTimeFormat(loc, { numeric: 'auto' }).format(-diffInSeconds, 'second');
    }
    
    const diffInMinutes = Math.floor(diffInSeconds / 60);
    if (diffInMinutes < 60) {
      return new Intl.RelativeTimeFormat(loc, { numeric: 'auto' }).format(-diffInMinutes, 'minute');
    }
    
    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) {
      return new Intl.RelativeTimeFormat(loc, { numeric: 'auto' }).format(-diffInHours, 'hour');
    }
    
    const diffInDays = Math.floor(diffInHours / 24);
    if (diffInDays < 30) {
      return new Intl.RelativeTimeFormat(loc, { numeric: 'auto' }).format(-diffInDays, 'day');
    }
    
    const diffInMonths = Math.floor(diffInDays / 30);
    if (diffInMonths < 12) {
      return new Intl.RelativeTimeFormat(loc, { numeric: 'auto' }).format(-diffInMonths, 'month');
    }
    
    const diffInYears = Math.floor(diffInMonths / 12);
    return new Intl.RelativeTimeFormat(loc, { numeric: 'auto' }).format(-diffInYears, 'year');
  };

  const parseCustomFormat = (format: string) => {
    const options: any = {};
    
    if (format.includes('YYYY')) options.year = 'numeric';
    if (format.includes('MM')) options.month = '2-digit';
    if (format.includes('DD')) options.day = '2-digit';
    if (format.includes('HH')) options.hour = '2-digit';
    if (format.includes('mm')) options.minute = '2-digit';
    if (format.includes('ss')) options.second = '2-digit';
    if (format.includes('WW')) options.weekday = 'long';
    if (format.includes('W')) options.weekday = 'short';
    
    return options;
  };

  return (
    <span className={className}>
      {formatDate(date)}
    </span>
  );
};

export default DateDisplay;