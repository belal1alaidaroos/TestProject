<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerPortalController;
use App\Http\Controllers\Api\AgencyController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\LookupsController;

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
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth:sanctum');
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Customer Portal Routes
    Route::prefix('portal')->group(function () {
        Route::get('workers', [CustomerPortalController::class, 'getWorkers']);
        Route::get('workers/{worker}', [CustomerPortalController::class, 'getWorker']);
        Route::post('workers/{worker}/reserve', [CustomerPortalController::class, 'reserveWorker']);
        Route::get('reservations', [CustomerPortalController::class, 'getReservations']);
        Route::get('reservations/{reservation}', [CustomerPortalController::class, 'getReservation']);
        Route::post('reservations/{reservation}/cancel', [CustomerPortalController::class, 'cancelReservation']);
        Route::post('reservations/{reservation}/contract', [CustomerPortalController::class, 'createContract']);
        Route::get('contracts', [CustomerPortalController::class, 'getContracts']);
        Route::get('contracts/{contract}', [CustomerPortalController::class, 'getContract']);
        Route::post('contracts/{contract}/cancel', [CustomerPortalController::class, 'cancelContract']);
        Route::get('contracts/{contract}/invoice', [CustomerPortalController::class, 'getInvoice']);
        Route::post('contracts/{contract}/prepare-payment', [CustomerPortalController::class, 'preparePayment']);
        Route::post('contracts/{contract}/confirm-payment', [CustomerPortalController::class, 'confirmPayment']);
    });

    // Agency Portal Routes
    Route::prefix('agency')->group(function () {
        Route::get('requests', [AgencyController::class, 'getRequests']);
        Route::get('requests/{requestId}', [AgencyController::class, 'getRequest']);
        Route::post('requests/{requestId}/proposals', [AgencyController::class, 'submitProposal']);
        Route::get('proposals', [AgencyController::class, 'getProposals']);
        Route::get('proposals/{proposalId}', [AgencyController::class, 'getProposal']);
        Route::patch('proposals/{proposalId}', [AgencyController::class, 'updateProposal']);
        Route::delete('proposals/{proposalId}', [AgencyController::class, 'deleteProposal']);
        Route::post('proposals/{proposalId}/withdraw', [AgencyController::class, 'withdrawProposal']);
    });

    // Admin Portal Routes
    Route::prefix('admin')->group(function () {
        // Dashboard
        Route::get('dashboard/stats', [AdminController::class, 'getDashboardStats']);
        
        // Proposals Review
        Route::get('proposals', [AdminController::class, 'getProposals']);
        Route::post('proposals/{proposalId}/approve', [AdminController::class, 'approveProposal']);
        Route::post('proposals/{proposalId}/reject', [AdminController::class, 'rejectProposal']);
        Route::post('proposals/{proposalId}/partial-approve', [AdminController::class, 'partialApproveProposal']);
        
        // User Management
        Route::get('users', [AdminController::class, 'getUsers']);
        Route::get('users/{userId}', [AdminController::class, 'getUser']);
        Route::patch('users/{userId}', [AdminController::class, 'updateUser']);
        Route::patch('users/{userId}/status', [AdminController::class, 'updateUserStatus']);
        Route::patch('users/{userId}/roles', [AdminController::class, 'updateUserRoles']);
        
        // System Settings
        Route::get('settings', [AdminController::class, 'getSystemSettings']);
        Route::patch('settings', [AdminController::class, 'updateSystemSettings']);
        Route::post('settings/reset', [AdminController::class, 'resetSystemSettings']);
        
        // Lookups for admin
        Route::get('agencies', [AdminController::class, 'getAgencies']);
        Route::get('roles', [AdminController::class, 'getRoles']);
    });

    // Lookups Routes (for all authenticated users)
    Route::prefix('lookups')->group(function () {
        // Countries
        Route::get('countries', [LookupsController::class, 'getCountries']);
        Route::post('countries', [LookupsController::class, 'createCountry'])->middleware('can:countries.create');
        Route::patch('countries/{countryId}', [LookupsController::class, 'updateCountry'])->middleware('can:countries.update');
        Route::delete('countries/{countryId}', [LookupsController::class, 'deleteCountry'])->middleware('can:countries.delete');
        
        // Cities
        Route::get('cities', [LookupsController::class, 'getCities']);
        Route::post('cities', [LookupsController::class, 'createCity'])->middleware('can:cities.create');
        Route::patch('cities/{cityId}', [LookupsController::class, 'updateCity'])->middleware('can:cities.update');
        Route::delete('cities/{cityId}', [LookupsController::class, 'deleteCity'])->middleware('can:cities.delete');
        
        // Districts
        Route::get('districts', [LookupsController::class, 'getDistricts']);
        Route::post('districts', [LookupsController::class, 'createDistrict'])->middleware('can:districts.create');
        Route::patch('districts/{districtId}', [LookupsController::class, 'updateDistrict'])->middleware('can:districts.update');
        Route::delete('districts/{districtId}', [LookupsController::class, 'deleteDistrict'])->middleware('can:districts.delete');
        
        // Professions
        Route::get('professions', [LookupsController::class, 'getProfessions']);
        Route::post('professions', [LookupsController::class, 'createProfession'])->middleware('can:professions.create');
        Route::patch('professions/{professionId}', [LookupsController::class, 'updateProfession'])->middleware('can:professions.update');
        Route::delete('professions/{professionId}', [LookupsController::class, 'deleteProfession'])->middleware('can:professions.delete');
        
        // Nationalities
        Route::get('nationalities', [LookupsController::class, 'getNationalities']);
        Route::post('nationalities', [LookupsController::class, 'createNationality'])->middleware('can:nationalities.create');
        Route::patch('nationalities/{nationalityId}', [LookupsController::class, 'updateNationality'])->middleware('can:nationalities.update');
        Route::delete('nationalities/{nationalityId}', [LookupsController::class, 'deleteNationality'])->middleware('can:nationalities.delete');
    });

    // User profile
    Route::get('profile', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->user()->load('roles')
        ]);
    });

    Route::patch('profile', function (Request $request) {
        $user = $request->user();
        
        $validated = $request->validate([
            'name_en' => 'sometimes|string|max:255',
            'name_ar' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:app_users,email,' . $user->id,
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user->load('roles')
        ]);
    });
});

// Health check endpoint
Route::get('health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0'
    ]);
});

// Catch-all route for undefined endpoints
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Endpoint not found'
    ], 404);
});