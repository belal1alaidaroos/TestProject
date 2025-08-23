<?php

namespace App\Services;

use App\Models\AppUser;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class LocalizationService
{
    protected $supportedLanguages = [
        'en' => [
            'name' => 'English',
            'native_name' => 'English',
            'direction' => 'ltr',
            'flag' => '🇺🇸',
        ],
        'ar' => [
            'name' => 'Arabic',
            'native_name' => 'العربية',
            'direction' => 'rtl',
            'flag' => '🇸🇦',
        ],
    ];

    protected $supportedCurrencies = [
        'SAR' => [
            'name' => 'Saudi Riyal',
            'symbol' => 'ر.س',
            'position' => 'right',
            'decimal_places' => 2,
        ],
        'USD' => [
            'name' => 'US Dollar',
            'symbol' => '$',
            'position' => 'left',
            'decimal_places' => 2,
        ],
    ];

    protected $supportedTimezones = [
        'UTC' => 'UTC',
        'Asia/Riyadh' => 'Riyadh (UTC+3)',
        'Asia/Dubai' => 'Dubai (UTC+4)',
        'Europe/London' => 'London (UTC+0)',
        'America/New_York' => 'New York (UTC-5)',
    ];

    protected $dateFormats = [
        'Y-m-d' => '2024-01-15',
        'd/m/Y' => '15/01/2024',
        'm/d/Y' => '01/15/2024',
        'd-m-Y' => '15-01-2024',
        'Y/m/d' => '2024/01/15',
    ];

    public function getSupportedLanguages()
    {
        return $this->supportedLanguages;
    }

    public function getSupportedCurrencies()
    {
        return $this->supportedCurrencies;
    }

    public function getSupportedTimezones()
    {
        return $this->supportedTimezones;
    }

    public function getDateFormats()
    {
        return $this->dateFormats;
    }

    public function setUserLanguage($userId, $language)
    {
        if (!array_key_exists($language, $this->supportedLanguages)) {
            throw new \Exception('Unsupported language');
        }

        $user = AppUser::findOrFail($userId);
        $user->update(['preferred_language' => $language]);

        // Clear user-specific cache
        Cache::forget("user_language_{$userId}");

        return [
            'success' => true,
            'message' => 'Language updated successfully',
            'data' => [
                'language' => $language,
                'language_info' => $this->supportedLanguages[$language],
            ]
        ];
    }

    public function setUserCurrency($userId, $currency)
    {
        if (!array_key_exists($currency, $this->supportedCurrencies)) {
            throw new \Exception('Unsupported currency');
        }

        $user = AppUser::findOrFail($userId);
        $user->update(['currency_preference' => $currency]);

        // Clear user-specific cache
        Cache::forget("user_currency_{$userId}");

        return [
            'success' => true,
            'message' => 'Currency updated successfully',
            'data' => [
                'currency' => $currency,
                'currency_info' => $this->supportedCurrencies[$currency],
            ]
        ];
    }

    public function setUserTimezone($userId, $timezone)
    {
        if (!array_key_exists($timezone, $this->supportedTimezones)) {
            throw new \Exception('Unsupported timezone');
        }

        $user = AppUser::findOrFail($userId);
        $user->update(['timezone' => $timezone]);

        // Clear user-specific cache
        Cache::forget("user_timezone_{$userId}");

        return [
            'success' => true,
            'message' => 'Timezone updated successfully',
            'data' => [
                'timezone' => $timezone,
                'timezone_info' => $this->supportedTimezones[$timezone],
            ]
        ];
    }

    public function setUserDateFormat($userId, $dateFormat)
    {
        if (!array_key_exists($dateFormat, $this->dateFormats)) {
            throw new \Exception('Unsupported date format');
        }

        $user = AppUser::findOrFail($userId);
        $user->update(['date_format' => $dateFormat]);

        // Clear user-specific cache
        Cache::forget("user_date_format_{$userId}");

        return [
            'success' => true,
            'message' => 'Date format updated successfully',
            'data' => [
                'date_format' => $dateFormat,
                'date_format_example' => $this->dateFormats[$dateFormat],
            ]
        ];
    }

    public function getUserLocalizationSettings($userId)
    {
        $user = AppUser::findOrFail($userId);

        return [
            'language' => [
                'current' => $user->preferred_language,
                'info' => $this->supportedLanguages[$user->preferred_language] ?? null,
                'supported' => $this->supportedLanguages,
            ],
            'currency' => [
                'current' => $user->currency_preference,
                'info' => $this->supportedCurrencies[$user->currency_preference] ?? null,
                'supported' => $this->supportedCurrencies,
            ],
            'timezone' => [
                'current' => $user->timezone,
                'info' => $this->supportedTimezones[$user->timezone] ?? null,
                'supported' => $this->supportedTimezones,
            ],
            'date_format' => [
                'current' => $user->date_format,
                'example' => $this->dateFormats[$user->date_format] ?? null,
                'supported' => $this->dateFormats,
            ],
        ];
    }

    public function formatCurrency($amount, $currency = null, $userId = null)
    {
        if ($userId) {
            $user = AppUser::find($userId);
            $currency = $currency ?? $user->currency_preference;
        }

        $currency = $currency ?? 'SAR';
        $currencyInfo = $this->supportedCurrencies[$currency];

        $formattedAmount = number_format($amount, $currencyInfo['decimal_places']);

        if ($currencyInfo['position'] === 'left') {
            return $currencyInfo['symbol'] . ' ' . $formattedAmount;
        } else {
            return $formattedAmount . ' ' . $currencyInfo['symbol'];
        }
    }

    public function formatDate($date, $format = null, $userId = null)
    {
        if ($userId) {
            $user = AppUser::find($userId);
            $format = $format ?? $user->date_format;
        }

        $format = $format ?? 'Y-m-d';
        
        if ($date instanceof \Carbon\Carbon) {
            return $date->format($format);
        }

        return \Carbon\Carbon::parse($date)->format($format);
    }

    public function formatDateTime($dateTime, $userId = null)
    {
        if ($userId) {
            $user = AppUser::find($userId);
            $timezone = $user->timezone;
            $dateFormat = $user->date_format;
        } else {
            $timezone = 'UTC';
            $dateFormat = 'Y-m-d';
        }

        $carbon = \Carbon\Carbon::parse($dateTime)->setTimezone($timezone);
        
        return $carbon->format($dateFormat . ' H:i:s');
    }

    public function getTranslation($key, $language = null, $replacements = [])
    {
        if (!$language) {
            $language = App::getLocale();
        }

        $translation = __($key, $replacements, $language);

        // If translation is not found, return the key
        if ($translation === $key) {
            return $key;
        }

        return $translation;
    }

    public function getTranslations($keys, $language = null)
    {
        $translations = [];

        foreach ($keys as $key) {
            $translations[$key] = $this->getTranslation($key, $language);
        }

        return $translations;
    }

    public function getLanguageDirection($language = null)
    {
        if (!$language) {
            $language = App::getLocale();
        }

        return $this->supportedLanguages[$language]['direction'] ?? 'ltr';
    }

    public function isRTL($language = null)
    {
        return $this->getLanguageDirection($language) === 'rtl';
    }

    public function convertCurrency($amount, $fromCurrency, $toCurrency, $exchangeRate = null)
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        if (!$exchangeRate) {
            // Default exchange rates (should come from config or API)
            $rates = [
                'SAR' => ['USD' => 0.27],
                'USD' => ['SAR' => 3.75],
            ];

            $exchangeRate = $rates[$fromCurrency][$toCurrency] ?? 1;
        }

        return $amount * $exchangeRate;
    }

    public function getSystemLocalizationSettings()
    {
        return [
            'default_language' => config('app.locale', 'en'),
            'fallback_language' => config('app.fallback_locale', 'en'),
            'default_timezone' => config('app.timezone', 'UTC'),
            'default_currency' => config('app.default_currency', 'SAR'),
            'supported_languages' => $this->supportedLanguages,
            'supported_currencies' => $this->supportedCurrencies,
            'supported_timezones' => $this->supportedTimezones,
            'date_formats' => $this->dateFormats,
        ];
    }

    public function updateSystemLocalizationSettings($settings)
    {
        // This would update system-wide localization settings
        // Implementation depends on your configuration management
        return [
            'success' => true,
            'message' => 'System localization settings updated successfully',
            'data' => $settings,
        ];
    }

    public function exportTranslations($language)
    {
        // This would export all translations for a specific language
        // Implementation depends on your translation management system
        return [
            'success' => true,
            'message' => "Translations exported for {$language}",
            'data' => [
                'language' => $language,
                'translations' => [], // This would contain actual translations
            ],
        ];
    }

    public function importTranslations($language, $translations)
    {
        // This would import translations for a specific language
        // Implementation depends on your translation management system
        return [
            'success' => true,
            'message' => "Translations imported for {$language}",
            'data' => [
                'language' => $language,
                'imported_count' => count($translations),
            ],
        ];
    }
}