<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LocalizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LocalizationController extends Controller
{
    public function __construct(
        private LocalizationService $localizationService
    ) {}

    public function getSupportedLanguages(): JsonResponse
    {
        try {
            $languages = $this->localizationService->getSupportedLanguages();

            return response()->json([
                'success' => true,
                'data' => $languages
            ]);

        } catch (\Exception $e) {
            Log::error('Get supported languages failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getSupportedCurrencies(): JsonResponse
    {
        try {
            $currencies = $this->localizationService->getSupportedCurrencies();

            return response()->json([
                'success' => true,
                'data' => $currencies
            ]);

        } catch (\Exception $e) {
            Log::error('Get supported currencies failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getSupportedTimezones(): JsonResponse
    {
        try {
            $timezones = $this->localizationService->getSupportedTimezones();

            return response()->json([
                'success' => true,
                'data' => $timezones
            ]);

        } catch (\Exception $e) {
            Log::error('Get supported timezones failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getDateFormats(): JsonResponse
    {
        try {
            $dateFormats = $this->localizationService->getDateFormats();

            return response()->json([
                'success' => true,
                'data' => $dateFormats
            ]);

        } catch (\Exception $e) {
            Log::error('Get date formats failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getUserLocalizationSettings(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $settings = $this->localizationService->getUserLocalizationSettings($userId);

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            Log::error('Get user localization settings failed', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function setUserLanguage(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'language' => 'required|string|in:en,ar',
            ]);

            $userId = $request->user()->id;
            $result = $this->localizationService->setUserLanguage($userId, $request->language);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Set user language failed', [
                'user_id' => $request->user()->id ?? null,
                'language' => $request->language ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function setUserCurrency(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'currency' => 'required|string|in:SAR,USD',
            ]);

            $userId = $request->user()->id;
            $result = $this->localizationService->setUserCurrency($userId, $request->currency);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Set user currency failed', [
                'user_id' => $request->user()->id ?? null,
                'currency' => $request->currency ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function setUserTimezone(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'timezone' => 'required|string',
            ]);

            $userId = $request->user()->id;
            $result = $this->localizationService->setUserTimezone($userId, $request->timezone);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Set user timezone failed', [
                'user_id' => $request->user()->id ?? null,
                'timezone' => $request->timezone ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function setUserDateFormat(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'date_format' => 'required|string',
            ]);

            $userId = $request->user()->id;
            $result = $this->localizationService->setUserDateFormat($userId, $request->date_format);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Set user date format failed', [
                'user_id' => $request->user()->id ?? null,
                'date_format' => $request->date_format ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function formatCurrency(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'amount' => 'required|numeric',
                'currency' => 'nullable|string|in:SAR,USD',
            ]);

            $formatted = $this->localizationService->formatCurrency(
                $request->amount,
                $request->currency,
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'amount' => $request->amount,
                    'currency' => $request->currency,
                    'formatted' => $formatted,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Format currency failed', [
                'amount' => $request->amount ?? null,
                'currency' => $request->currency ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function formatDate(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'date' => 'required|date',
                'format' => 'nullable|string',
            ]);

            $formatted = $this->localizationService->formatDate(
                $request->date,
                $request->format,
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $request->date,
                    'format' => $request->format,
                    'formatted' => $formatted,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Format date failed', [
                'date' => $request->date ?? null,
                'format' => $request->format ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getTranslation(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'key' => 'required|string',
                'language' => 'nullable|string|in:en,ar',
                'replacements' => 'nullable|array',
            ]);

            $translation = $this->localizationService->getTranslation(
                $request->key,
                $request->language,
                $request->replacements ?? []
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'key' => $request->key,
                    'language' => $request->language ?? app()->getLocale(),
                    'translation' => $translation,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get translation failed', [
                'key' => $request->key ?? null,
                'language' => $request->language ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getTranslations(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'keys' => 'required|array',
                'keys.*' => 'string',
                'language' => 'nullable|string|in:en,ar',
            ]);

            $translations = $this->localizationService->getTranslations(
                $request->keys,
                $request->language
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'keys' => $request->keys,
                    'language' => $request->language ?? app()->getLocale(),
                    'translations' => $translations,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get translations failed', [
                'keys' => $request->keys ?? [],
                'language' => $request->language ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getSystemLocalizationSettings(): JsonResponse
    {
        try {
            $settings = $this->localizationService->getSystemLocalizationSettings();

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            Log::error('Get system localization settings failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function updateSystemLocalizationSettings(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'settings' => 'required|array',
            ]);

            $result = $this->localizationService->updateSystemLocalizationSettings($request->settings);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Update system localization settings failed', [
                'settings' => $request->settings ?? [],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function exportTranslations(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'language' => 'required|string|in:en,ar',
            ]);

            $result = $this->localizationService->exportTranslations($request->language);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Export translations failed', [
                'language' => $request->language ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function importTranslations(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'language' => 'required|string|in:en,ar',
                'translations' => 'required|array',
            ]);

            $result = $this->localizationService->importTranslations(
                $request->language,
                $request->translations
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Import translations failed', [
                'language' => $request->language ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}