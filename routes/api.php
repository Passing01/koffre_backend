<?php

use App\Http\Controllers\Api\Auth\OtpAuthController;
use App\Http\Controllers\Api\Cagnottes\CagnotteController;
use App\Http\Controllers\Api\Cagnottes\CagnotteInteractionController;
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

    // Commentaires publics (lecture sans auth)
    Route::get('/{id}/comments', [CagnotteInteractionController::class, 'listComments']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [CagnotteController::class, 'store']); // Fallback / Global
        Route::post('/public/direct', [CagnotteController::class, 'storePublicDirect']);
        Route::post('/public/coffre', [CagnotteController::class, 'storePublicCoffre']);
        Route::post('/private', [CagnotteController::class, 'storePrivate']);
        Route::get('/{id}', [CagnotteController::class, 'show']);
        Route::put('/{id}', [CagnotteController::class, 'update']);              // Modifier la cagnotte
        Route::post('/{id}/participants', [CagnotteController::class, 'addParticipant']);
        Route::post('/{id}/unlock', [CagnotteController::class, 'requestUnlock']); // Demande de déblocage
        Route::get('/{id}/transactions', [CagnotteTransactionController::class, 'index']);
        Route::post('/{id}/archive', [CagnotteController::class, 'archive']);
        Route::post('/{id}/unarchive', [CagnotteController::class, 'unarchive']);

        // Commentaires (écriture avec auth)
        Route::post('/{id}/comments', [CagnotteInteractionController::class, 'storeComment']);
        Route::delete('/{id}/comments/{commentId}', [CagnotteInteractionController::class, 'deleteComment']);

        // Likes
        Route::post('/{id}/like', [CagnotteInteractionController::class, 'toggleLike']);
        Route::get('/{id}/like', [CagnotteInteractionController::class, 'checkLike']);
    });
});

Route::middleware('auth:sanctum')->prefix('tontines')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\Tontines\TontineController::class, 'index']);
    Route::get('/mine', [\App\Http\Controllers\Api\Tontines\TontineController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\Tontines\TontineController::class, 'store']);
    Route::get('/{id}', [\App\Http\Controllers\Api\Tontines\TontineController::class, 'show'])->where('id', '[0-9]+');
    Route::put('/{id}', [\App\Http\Controllers\Api\Tontines\TontineController::class, 'update'])->where('id', '[0-9]+');
    Route::post('/{id}/members', [\App\Http\Controllers\Api\Tontines\TontineController::class, 'addMember'])->where('id', '[0-9]+');
    Route::post('/{id}/members/complete-registration', [\App\Http\Controllers\Api\Tontines\TontineController::class, 'completeRegistration'])->where('id', '[0-9]+');
    Route::put('/{id}/ranks', [\App\Http\Controllers\Api\Tontines\TontineController::class, 'setRanks'])->where('id', '[0-9]+');
    Route::put('/{id}/members/{phone}/permissions', [\App\Http\Controllers\Api\Tontines\TontineController::class, 'updateMemberPermissions'])->where('id', '[0-9]+');
    Route::post('/{id}/pay', [\App\Http\Controllers\Api\Tontines\TontinePaymentController::class, 'pay'])->where('id', '[0-9]+');
});

Route::middleware('auth:sanctum')->get('/my-cagnottes', [CagnotteController::class, 'mine']);
Route::middleware('auth:sanctum')->get('/my-tontines', [\App\Http\Controllers\Api\Tontines\TontineController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/contribute/simulate', [ContributionController::class, 'simulate']);
    Route::post('/contribute/initiate', [ContributionController::class, 'initiate']);
    Route::get('/my-contributions', [ContributionController::class, 'listMine']);

    // Profile routes
    Route::get('/me', [\App\Http\Controllers\Api\Users\ProfileController::class, 'show']);
    Route::put('/me', [\App\Http\Controllers\Api\Users\ProfileController::class, 'update']);
});

Route::post('/payments/webhook', [\App\Http\Controllers\Api\Payments\PaymentWebhookController::class, 'handleCinetPay']);
Route::post('/payments/fedapay/webhook', [\App\Http\Controllers\Api\Payments\PaymentWebhookController::class, 'handleFedaPay']);
Route::post('/payments/paydunya/webhook', [\App\Http\Controllers\Api\Payments\PaymentWebhookController::class, 'handlePayDunya']);
Route::post('/payments/geniuspay/webhook', [\App\Http\Controllers\Api\Payments\PaymentWebhookController::class, 'handleGeniusPay'])->name('webhooks.geniuspay');

// ─── Callback unifié (redirection depuis la passerelle de paiement) ─────────────
// Accepte GET (navigateur redirigé) et POST (IPN/webhook générique)
Route::match(['get', 'post'], '/payments/callback', [\App\Http\Controllers\Api\Payments\PaymentCallbackController::class, 'handle'])
    ->name('payments.callback');
