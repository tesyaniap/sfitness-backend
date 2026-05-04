<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ClassController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DebtController;
use App\Http\Controllers\Api\DistributorController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\ReceivableController;
use App\Http\Controllers\Api\ReturnController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Midtrans webhook — no auth
Route::post('/payment/notification', [PaymentController::class, 'handleNotification']);

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Profile
    Route::patch('/profile',          [UserController::class, 'updateProfile']);
    Route::patch('/profile/password', [UserController::class, 'updatePassword']);

    // Payment (Midtrans)
    Route::post('/payment/kantin',        [PaymentController::class, 'createKantinPayment']);
    Route::post('/payment/booking/{class}',[PaymentController::class, 'createBookingPayment']);
    Route::post('/payment/confirm',       [PaymentController::class, 'confirmPayment']);

    // Classes
    Route::get('/classes',              [ClassController::class, 'index']);
    Route::get('/classes/{class}',      [ClassController::class, 'show']);
    Route::post('/classes/{class}/book',[ClassController::class, 'book']);
    Route::delete('/classes/{class}/book',[ClassController::class, 'cancelBooking']);
    Route::get('/my-bookings',          [ClassController::class, 'myBookings']);

    // Products
    Route::get('/products',           [ProductController::class, 'index']);
    Route::get('/products/barcode',   [ProductController::class, 'findByBarcode']);
    Route::get('/products/{product}', [ProductController::class, 'show']);

    // Categories (read for all)
    Route::get('/categories', [CategoryController::class, 'index']);

    // Transactions
    Route::get('/transactions',                [TransactionController::class, 'index']);
    Route::post('/transactions',               [TransactionController::class, 'store']);
    Route::get('/transactions/{transaction}',  [TransactionController::class, 'show']);

    // Dashboard
    Route::get('/dashboard/member', [DashboardController::class, 'member']);

    // ── Admin routes ──────────────────────────────────────────────────────────
    Route::middleware('role:admin,super_admin')->group(function () {

        // Dashboard
        Route::get('/dashboard/admin', [DashboardController::class, 'admin']);

        // Classes CRUD
        Route::post('/classes',           [ClassController::class, 'store']);
        Route::put('/classes/{class}',    [ClassController::class, 'update']);
        Route::delete('/classes/{class}', [ClassController::class, 'destroy']);

        // Products CRUD
        Route::post('/products',             [ProductController::class, 'store']);
        Route::put('/products/{product}',    [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);

        // Categories CRUD
        Route::post('/categories',              [CategoryController::class, 'store']);
        Route::put('/categories/{category}',    [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        // Distributors
        Route::get('/distributors',                [DistributorController::class, 'index']);
        Route::post('/distributors',               [DistributorController::class, 'store']);
        Route::put('/distributors/{distributor}',  [DistributorController::class, 'update']);
        Route::delete('/distributors/{distributor}',[DistributorController::class, 'destroy']);

        // Purchases
        Route::get('/purchases',             [PurchaseController::class, 'index']);
        Route::post('/purchases',            [PurchaseController::class, 'store']);
        Route::get('/purchases/{purchase}',  [PurchaseController::class, 'show']);

        // Debts (Utang)
        Route::get('/debts',             [DebtController::class, 'index']);
        Route::get('/debts/{debt}',      [DebtController::class, 'show']);
        Route::post('/debts/{debt}/pay', [DebtController::class, 'pay']);

        // Receivables (Piutang)
        Route::get('/receivables',                    [ReceivableController::class, 'index']);
        Route::get('/receivables/{receivable}',       [ReceivableController::class, 'show']);
        Route::post('/receivables/{receivable}/pay',  [ReceivableController::class, 'pay']);

        // Returns (Retur)
        Route::get('/returns',          [ReturnController::class, 'index']);
        Route::post('/returns',         [ReturnController::class, 'store']);
        Route::get('/returns/{return}', [ReturnController::class, 'show']);

        // Users
        Route::get('/users',           [UserController::class, 'index']);
        Route::get('/users/{user}',    [UserController::class, 'show']);
        Route::patch('/users/{user}',  [UserController::class, 'update']);
    });

    // Super Admin only
    Route::middleware('role:super_admin')->group(function () {
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
});
