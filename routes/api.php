<?php

use App\Http\Controllers\Api\Auth\OtpAuthController;
use App\Http\Controllers\Api\Cagnottes\CagnotteController;
use App\Http\Controllers\Api\Cagnottes\CagnotteTransactionController;
use App\Http\Controllers\Api\Contributions\ContributionController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/send-otp', [OtpAuthController::class, 'sendOtp'])->middleware('throttle:otp-send');
    Route::post('/verify-otp', [OtpAuthController::class, 'verifyOtp'])->middleware('throttle:otp-verify');
    Route::post('/update-fcm-token', [OtpAuthController::class, 'updateFcmToken'])->middleware('auth:sanctum');
});

Route::prefix('cagnottes')->group(function () {
    Route::get('/publiques', [CagnotteController::class, 'publics']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [CagnotteController::class, 'store']);
        Route::get('/{id}', [CagnotteController::class, 'show']);
        Route::post('/{id}/participants', [CagnotteController::class, 'addParticipant']);
        Route::get('/{id}/transactions', [CagnotteTransactionController::class, 'index']);
    });
});

Route::middleware('auth:sanctum')->get('/my-cagnottes', [CagnotteController::class, 'mine']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/contribute/simulate', [ContributionController::class, 'simulate']);
    Route::post('/contribute/initiate', [ContributionController::class, 'initiate']);
    Route::get('/my-contributions', [ContributionController::class, 'listMine']);
});

Route::post('/payments/webhook', [\App\Http\Controllers\Api\Payments\PaymentWebhookController::class, 'handleCinetPay']);
Route::post('/payments/fedapay/webhook', [\App\Http\Controllers\Api\Payments\PaymentWebhookController::class, 'handleFedaPay']);
Route::post('/payments/paydunya/webhook', [\App\Http\Controllers\Api\Payments\PaymentWebhookController::class, 'handlePayDunya']);
