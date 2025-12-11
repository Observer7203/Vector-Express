<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarrierManagementController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CompanyDocumentController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\ShipmentController;
use App\Http\Controllers\Api\TrackingController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\CompanyController as AdminCompanyController;
use App\Http\Controllers\Admin\CarrierController as AdminCarrierController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->name('verification.verify');
});

// Public tracking
Route::post('/tracking', [TrackingController::class, 'track']);
Route::get('/tracking/{order}', [TrackingController::class, 'orderTracking']);

// Document download (requires token in query string)
Route::get('/documents/{document}/download', [AdminCompanyController::class, 'downloadDocument']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);

    // User profile
    Route::prefix('user')->group(function () {
        Route::get('/profile', [UserController::class, 'profile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::post('/avatar', [UserController::class, 'updateAvatar']);
        Route::put('/password', [UserController::class, 'updatePassword']);
    });

    // Companies
    Route::prefix('companies')->group(function () {
        Route::get('/{company}', [CompanyController::class, 'show']);
        Route::put('/{company}', [CompanyController::class, 'update']);
        Route::post('/{company}/logo', [CompanyController::class, 'updateLogo']);
        Route::post('/{company}/verification', [CompanyController::class, 'requestVerification']);
        Route::post('/{company}/carrier', [CompanyController::class, 'setupCarrier']);
    });

    // Company Documents (для загрузки документов перевозчика)
    Route::prefix('company-documents')->group(function () {
        Route::get('/', [CompanyDocumentController::class, 'index']);
        Route::post('/', [CompanyDocumentController::class, 'store']);
        Route::get('/verification-status', [CompanyDocumentController::class, 'verificationStatus']);
        Route::get('/{document}', [CompanyDocumentController::class, 'show']);
        Route::delete('/{document}', [CompanyDocumentController::class, 'destroy']);
        Route::get('/{document}/download', [CompanyDocumentController::class, 'download']);
    });

    // Shipments
    Route::prefix('shipments')->group(function () {
        Route::get('/', [ShipmentController::class, 'index']);
        Route::post('/', [ShipmentController::class, 'store']);
        Route::get('/{shipment}', [ShipmentController::class, 'show']);
        Route::put('/{shipment}', [ShipmentController::class, 'update']);
        Route::delete('/{shipment}', [ShipmentController::class, 'destroy']);
        Route::post('/{shipment}/calculate', [ShipmentController::class, 'calculate']);
        Route::get('/{shipment}/quotes', [ShipmentController::class, 'quotes']);
    });

    // Quotes
    Route::prefix('quotes')->group(function () {
        Route::get('/{quote}', [QuoteController::class, 'show']);
        Route::post('/{quote}/select', [QuoteController::class, 'select']);
        Route::post('/compare', [QuoteController::class, 'compare']);
    });

    // Orders
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{order}', [OrderController::class, 'show']);
        Route::put('/{order}', [OrderController::class, 'update']);
        Route::post('/{order}/cancel', [OrderController::class, 'cancel']);
        Route::post('/{order}/confirm', [OrderController::class, 'confirm']);
        Route::put('/{order}/status', [OrderController::class, 'updateStatus']);
        Route::get('/{order}/tracking', [OrderController::class, 'tracking']);
    });

    // Chats
    Route::prefix('chats')->group(function () {
        Route::get('/', [ChatController::class, 'index']);
        Route::get('/{chat}', [ChatController::class, 'show']);
        Route::get('/{chat}/messages', [ChatController::class, 'messages']);
        Route::post('/{chat}/messages', [ChatController::class, 'sendMessage']);
        Route::post('/{chat}/attachments', [ChatController::class, 'sendAttachment']);
        Route::post('/{chat}/messages/{message}/read', [ChatController::class, 'markAsRead']);
    });

    // Invoices
    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index']);
        Route::get('/{invoice}', [InvoiceController::class, 'show']);
        Route::get('/{invoice}/pdf', [InvoiceController::class, 'downloadPdf']);
        Route::post('/{invoice}/pay', [InvoiceController::class, 'pay']);
    });

    // Carrier Management (for carriers only)
    Route::prefix('carrier')->group(function () {
        // Stats
        Route::get('/stats', [CarrierManagementController::class, 'getStats']);

        // Zones
        Route::get('/zones', [CarrierManagementController::class, 'getZones']);
        Route::post('/zones', [CarrierManagementController::class, 'createZone']);
        Route::put('/zones/{id}', [CarrierManagementController::class, 'updateZone']);
        Route::delete('/zones/{id}', [CarrierManagementController::class, 'deleteZone']);

        // Terminals
        Route::get('/terminals', [CarrierManagementController::class, 'getTerminals']);
        Route::post('/terminals', [CarrierManagementController::class, 'createTerminal']);
        Route::put('/terminals/{id}', [CarrierManagementController::class, 'updateTerminal']);
        Route::delete('/terminals/{id}', [CarrierManagementController::class, 'deleteTerminal']);

        // Surcharges
        Route::get('/surcharges', [CarrierManagementController::class, 'getSurcharges']);
        Route::post('/surcharges', [CarrierManagementController::class, 'createSurcharge']);
        Route::put('/surcharges/{id}', [CarrierManagementController::class, 'updateSurcharge']);
        Route::delete('/surcharges/{id}', [CarrierManagementController::class, 'deleteSurcharge']);

        // Rate Cards
        Route::get('/rate-cards', [CarrierManagementController::class, 'getRateCards']);
        Route::post('/rate-cards', [CarrierManagementController::class, 'createRateCard']);
        Route::put('/rate-cards/{id}', [CarrierManagementController::class, 'updateRateCard']);
        Route::delete('/rate-cards/{id}', [CarrierManagementController::class, 'deleteRateCard']);

        // Pricing Rule
        Route::get('/pricing-rule', [CarrierManagementController::class, 'getPricingRule']);
        Route::put('/pricing-rule', [CarrierManagementController::class, 'updatePricingRule']);

        // Import from Excel
        Route::post('/import/zones', [CarrierManagementController::class, 'importZones']);
        Route::post('/import/rate-cards', [CarrierManagementController::class, 'importRateCards']);
        Route::post('/import/terminals', [CarrierManagementController::class, 'importTerminals']);
        Route::get('/import/templates', [CarrierManagementController::class, 'getImportTemplates']);
    });

    // Admin routes
    Route::prefix('admin')->middleware('admin')->group(function () {
        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);

        // Users management
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::get('/users/{user}', [AdminUserController::class, 'show']);
        Route::put('/users/{user}', [AdminUserController::class, 'update']);
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);
        Route::post('/users/{user}/toggle-active', [AdminUserController::class, 'toggleActive']);
        Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword']);

        // Companies management
        Route::get('/companies', [AdminCompanyController::class, 'index']);
        Route::get('/companies/pending-verification', [AdminCompanyController::class, 'pendingVerification']);
        Route::post('/companies', [AdminCompanyController::class, 'store']);
        Route::get('/companies/{company}', [AdminCompanyController::class, 'show']);
        Route::put('/companies/{company}', [AdminCompanyController::class, 'update']);
        Route::delete('/companies/{company}', [AdminCompanyController::class, 'destroy']);
        Route::post('/companies/{company}/verify', [AdminCompanyController::class, 'verify']);
        Route::post('/companies/{company}/unverify', [AdminCompanyController::class, 'unverify']);
        Route::get('/companies/{company}/documents', [AdminCompanyController::class, 'documents']);

        // Document verification
        Route::post('/documents/{document}/approve', [AdminCompanyController::class, 'approveDocument']);
        Route::post('/documents/{document}/reject', [AdminCompanyController::class, 'rejectDocument']);
        Route::get('/documents/{document}/download', [AdminCompanyController::class, 'downloadDocument']);

        // Carriers management
        Route::get('/carriers', [AdminCarrierController::class, 'index']);
        Route::post('/carriers', [AdminCarrierController::class, 'store']);
        Route::get('/carriers/available-companies', [AdminCarrierController::class, 'availableCompanies']);
        Route::get('/carriers/countries', [AdminCarrierController::class, 'countries']);
        Route::get('/carriers/{carrier}', [AdminCarrierController::class, 'show']);
        Route::put('/carriers/{carrier}', [AdminCarrierController::class, 'update']);
        Route::delete('/carriers/{carrier}', [AdminCarrierController::class, 'destroy']);
        Route::post('/carriers/{carrier}/toggle-active', [AdminCarrierController::class, 'toggleActive']);

        // Orders management
        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::get('/orders/statistics', [AdminOrderController::class, 'statistics']);
        Route::get('/orders/{order}', [AdminOrderController::class, 'show']);
        Route::put('/orders/{order}/status', [AdminOrderController::class, 'updateStatus']);
    });
});
