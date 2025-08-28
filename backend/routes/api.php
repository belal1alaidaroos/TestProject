<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PayPassController;
use App\Http\Controllers\Customer\WorkerController;
use App\Http\Controllers\Customer\ReservationController;
use App\Http\Controllers\Customer\ContractController;
use App\Http\Controllers\Agency\RequestController as AgencyRequestController;
use App\Http\Controllers\Agency\ProposalController as AgencyProposalController;
use App\Http\Controllers\Admin\RequestController as AdminRequestController;
use App\Http\Controllers\Admin\ProposalController as AdminProposalController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\LookupController;
use App\Http\Controllers\Admin\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('request-otp', [AuthController::class, 'requestOtp']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('email-login', [AuthController::class, 'emailLogin']);
    Route::post('customer-signup', [AuthController::class, 'customerSignup']);
    Route::post('social-login', [AuthController::class, 'socialLogin']);
    Route::get('portal-access/{portal_type}', [AuthController::class, 'checkPortalAccess']);
    Route::post('refresh', [AuthController::class, 'refresh']);
});

// Protected routes
Route::middleware('auth:api')->group(function () {
    //Most of the below Missing ?
    // Customer Portal Routes
    Route::prefix('portal')->middleware('role:customer')->group(function () {
        Route::get('workers', [WorkerController::class, 'index']);
        Route::get('workers/{worker}', [WorkerController::class, 'show']);
        Route::post('workers/{worker}/reserve', [WorkerController::class, 'reserve']);
        
        Route::get('reservations', [ReservationController::class, 'index']);
        Route::get('reservations/{reservation}', [ReservationController::class, 'show']);
        Route::post('reservations/{reservation}/contract', [ReservationController::class, 'createContract']);
        Route::post('reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);
        
        Route::get('contracts', [ContractController::class, 'index']);
        Route::get('contracts/{contract}', [ContractController::class, 'show']);
        Route::get('contracts/{contract}/invoice', [ContractController::class, 'getInvoice']);
        Route::post('contracts/{contract}/prepare-payment', [ContractController::class, 'preparePayment']);
        Route::post('contracts/{contract}/confirm-payment', [ContractController::class, 'confirmPayment']);
        Route::post('contracts/{contract}/cancel', [ContractController::class, 'cancel']);
        
        // PayPass Payment Routes
        Route::post('paypass/create-session', [PayPassController::class, 'createSession']);
        Route::post('paypass/verify-otp', [PayPassController::class, 'verifyOtp']);
        Route::get('paypass/session/{sessionId}/status', [PayPassController::class, 'getSessionStatus']);
        Route::post('paypass/session/{sessionId}/cancel', [PayPassController::class, 'cancelSession']);
    });

    // Agency Portal Routes
    Route::prefix('agency')->middleware('role:agency')->group(function () {
        Route::get('requests', [AgencyRequestController::class, 'index']);
        Route::get('requests/{request}', [AgencyRequestController::class, 'show']);
        
        Route::get('proposals', [AgencyProposalController::class, 'index']);
        Route::post('requests/{request}/proposals', [AgencyProposalController::class, 'store']);
        Route::get('proposals/{proposal}', [AgencyProposalController::class, 'show']);
        Route::patch('proposals/{proposal}', [AgencyProposalController::class, 'update']);
        Route::delete('proposals/{proposal}', [AgencyProposalController::class, 'destroy']);
    });

    // Admin Portal Routes
    Route::prefix('admin')->middleware('role:admin,internal')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index']);
        
        Route::get('requests', [AdminRequestController::class, 'index']);
        Route::get('requests/{request}', [AdminRequestController::class, 'show']);
        Route::post('requests', [AdminRequestController::class, 'store']);
        Route::patch('requests/{request}', [AdminRequestController::class, 'update']);
        Route::delete('requests/{request}', [AdminRequestController::class, 'destroy']);
        
        Route::get('requests/{request}/proposals', [AdminProposalController::class, 'index']);
        Route::post('proposals/{proposal}/approve', [AdminProposalController::class, 'approve']);
        Route::post('proposals/{proposal}/reject', [AdminProposalController::class, 'reject']);
        
        Route::get('users', [UserController::class, 'index']);
        Route::get('users/{user}', [UserController::class, 'show']);
        Route::post('users', [UserController::class, 'store']);
        Route::patch('users/{user}', [UserController::class, 'update']);
        Route::delete('users/{user}', [UserController::class, 'destroy']);
        
        // Lookup Management
        Route::prefix('lookups')->group(function () {
            Route::get('countries', [LookupController::class, 'countries']);
            Route::get('cities', [LookupController::class, 'cities']);
            Route::get('districts', [LookupController::class, 'districts']);
            Route::get('nationalities', [LookupController::class, 'nationalities']);
            Route::get('professions', [LookupController::class, 'professions']);
            Route::get('packages', [LookupController::class, 'packages']);
            
            Route::post('countries', [LookupController::class, 'storeCountry']);
            Route::post('cities', [LookupController::class, 'storeCity']);
            Route::post('districts', [LookupController::class, 'storeDistrict']);
            Route::post('nationalities', [LookupController::class, 'storeNationality']);
            Route::post('professions', [LookupController::class, 'storeProfession']);
            Route::post('packages', [LookupController::class, 'storePackage']);
        });
    });

    // User profile and logout
    Route::get('user', [AuthController::class, 'user']);
    Route::post('logout', [AuthController::class, 'logout']);
});

// Health check endpoint
Route::get('health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0'
    ]);
});

// Fallback for undefined routes
Route::fallback(function () {
    return response()->json([
        'message' => 'API endpoint not found.',
        'status' => 404
    ], 404);
});