<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Health check endpoint
Route::get('/up', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
        'environment' => config('app.env'),
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'redis' => Redis::connection()->ping() ? 'connected' : 'disconnected',
    ]);
});

// API documentation
Route::get('/docs', function () {
    return view('api.docs');
})->name('api.docs');

// Swagger JSON endpoint
Route::get('/docs/swagger.json', function () {
    return response()->json([
        'openapi' => '3.0.0',
        'info' => [
            'title' => 'Employee Portal API',
            'description' => 'API documentation for the Employee Recruitment Portal',
            'version' => '1.0.0',
            'contact' => [
                'name' => 'API Support',
                'email' => 'support@example.com'
            ]
        ],
        'servers' => [
            [
                'url' => config('app.url') . '/api',
                'description' => 'Production server'
            ],
            [
                'url' => 'http://localhost:8000/api',
                'description' => 'Development server'
            ]
        ],
        'paths' => [
            '/auth/request-otp' => [
                'post' => [
                    'tags' => ['Authentication'],
                    'summary' => 'Request OTP',
                    'description' => 'Request a one-time password for phone verification',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'phone' => [
                                            'type' => 'string',
                                            'description' => 'Phone number',
                                            'example' => '0501234567'
                                        ]
                                    ],
                                    'required' => ['phone']
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'OTP sent successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'message' => [
                                                'type' => 'string',
                                                'example' => 'OTP sent successfully'
                                            ],
                                            'status' => [
                                                'type' => 'integer',
                                                'example' => 200
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        '422' => [
                            'description' => 'Validation error'
                        ],
                        '429' => [
                            'description' => 'Too many requests'
                        ]
                    ]
                ]
            ],
            '/auth/verify-otp' => [
                'post' => [
                    'tags' => ['Authentication'],
                    'summary' => 'Verify OTP',
                    'description' => 'Verify the one-time password and authenticate user',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'phone' => [
                                            'type' => 'string',
                                            'description' => 'Phone number',
                                            'example' => '0501234567'
                                        ],
                                        'code' => [
                                            'type' => 'string',
                                            'description' => 'OTP code',
                                            'example' => '123456'
                                        ]
                                    ],
                                    'required' => ['phone', 'code']
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Authentication successful',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'access_token' => [
                                                'type' => 'string',
                                                'example' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'
                                            ],
                                            'token_type' => [
                                                'type' => 'string',
                                                'example' => 'bearer'
                                            ],
                                            'expires_in' => [
                                                'type' => 'integer',
                                                'example' => 1800
                                            ],
                                            'user' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'id' => [
                                                        'type' => 'string',
                                                        'example' => '550e8400-e29b-41d4-a716-446655440000'
                                                    ],
                                                    'name' => [
                                                        'type' => 'string',
                                                        'example' => 'John Doe'
                                                    ],
                                                    'user_type' => [
                                                        'type' => 'string',
                                                        'example' => 'Customer'
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        '401' => [
                            'description' => 'Invalid OTP'
                        ],
                        '422' => [
                            'description' => 'Validation error'
                        ]
                    ]
                ]
            ]
        ],
        'components' => [
            'securitySchemes' => [
                'bearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'JWT'
                ]
            ]
        ]
    ]);
});

// Welcome page
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Catch-all route for SPA
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
