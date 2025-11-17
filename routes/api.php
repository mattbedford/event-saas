<?php

use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\CouponController;
use Illuminate\Support\Facades\Route;

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

// Public API routes for WordPress integration

// Get event details
Route::get('/events/{eventSlug}', [RegistrationController::class, 'getEvent']);

// Registration endpoints
Route::prefix('/events/{eventSlug}')->group(function () {
    Route::post('/registrations', [RegistrationController::class, 'store']);
    Route::get('/registrations/{email}', [RegistrationController::class, 'show']);
    Route::post('/validate-coupon', [RegistrationController::class, 'validateCoupon']);
});

// Coupon endpoints (public, no auth required)
Route::get('/coupons/validate', [CouponController::class, 'validate']);
Route::get('/events/{eventSlug}/coupons', [CouponController::class, 'index']);

// Admin endpoints (require API token authentication)
Route::post('/admin/registrations', [RegistrationController::class, 'storeAdmin']);

// Webhooks (no auth - Stripe/Brevo validate signatures)
Route::post('/webhooks/stripe/{eventSlug}', [WebhookController::class, 'stripe'])
    ->withoutMiddleware(['throttle']);

Route::post('/webhooks/brevo', [WebhookController::class, 'brevo'])
    ->withoutMiddleware(['throttle']);
