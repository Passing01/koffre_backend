<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Web\WebContributionController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/c/{id}', [WebContributionController::class, 'show'])->name('cagnotte.web_show');
Route::post('/c/{id}/like', [WebContributionController::class, 'toggleLike'])->name('cagnotte.web_like');
Route::post('/c/{id}/contribute', [WebContributionController::class, 'contribute'])->name('cagnotte.web_contribute');
Route::post('/c/{id}/comment', [WebContributionController::class, 'storeComment'])->name('cagnotte.web_comment');


// ─── Callback de paiement Web (navigateur redirigé par la passerelle) ────────
// URL à configurer dans le tableau de bord de votre prestataire de paiement :
//   https://votre-domaine.com/payments/callback?event={event}&reference={reference}&provider={provider}
//
// Paramètres optionnels pour rétrocompatibilité : token, status
Route::get('/payments/callback', [\App\Http\Controllers\Web\WebPaymentCallbackController::class, 'handle'])
    ->name('payment.callback');

// Alias de rétrocompatibilité → redirigent vers le callback unifié
Route::get('/payments/success', function (\Illuminate\Http\Request $req) {
    return redirect()->route('payment.callback', array_merge($req->all(), ['event' => 'payment.success']));
})->name('payment.success');

Route::get('/payments/cancel', function (\Illuminate\Http\Request $req) {
    return redirect()->route('payment.callback', array_merge($req->all(), ['event' => 'payment.cancelled']));
})->name('payment.cancel');


// Admin Authentication Routes
Route::prefix('admin')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Web\Admin\AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [\App\Http\Controllers\Web\Admin\AdminAuthController::class, 'sendOtp'])->name('admin.send-otp');
    Route::get('/verify-otp', [\App\Http\Controllers\Web\Admin\AdminAuthController::class, 'showVerifyForm'])->name('admin.verify-otp');
    Route::post('/verify-otp', [\App\Http\Controllers\Web\Admin\AdminAuthController::class, 'verifyOtp'])->name('admin.verify-otp.submit');
    Route::post('/logout', [\App\Http\Controllers\Web\Admin\AdminAuthController::class, 'logout'])->name('admin.logout');
});

Route::prefix('admin')->middleware(['auth', 'admin', 'check.blocked'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Web\Admin\AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/cagnottes', [\App\Http\Controllers\Web\Admin\AdminCagnotteController::class, 'index'])->name('admin.cagnottes.index');
    Route::get('/cagnottes/{id}', [\App\Http\Controllers\Web\Admin\AdminCagnotteController::class, 'show'])->name('admin.cagnottes.show');
    Route::post('/cagnottes/{id}/approve-unlock', [\App\Http\Controllers\Web\Admin\AdminCagnotteController::class, 'approveUnlock'])->name('admin.cagnottes.approve-unlock');
    Route::post('/cagnottes/{id}/reject-unlock', [\App\Http\Controllers\Web\Admin\AdminCagnotteController::class, 'rejectUnlock'])->name('admin.cagnottes.reject-unlock');
    Route::post('/cagnottes/{id}/process-payout', [\App\Http\Controllers\Web\Admin\AdminCagnotteController::class, 'processPayout'])->name('admin.cagnottes.process-payout');

    // Modération
    Route::post('/cagnottes/{id}/activate', [\App\Http\Controllers\Web\Admin\AdminCagnotteController::class, 'activate'])->name('admin.cagnottes.activate');
    Route::post('/cagnottes/{id}/block', [\App\Http\Controllers\Web\Admin\AdminCagnotteController::class, 'block'])->name('admin.cagnottes.block');
    Route::post('/cagnottes/{id}/comments/{commentId}/block', [\App\Http\Controllers\Web\Admin\AdminCagnotteController::class, 'blockComment'])->name('admin.cagnottes.block-comment');

    // Tontines
    Route::get('/tontines', [\App\Http\Controllers\Web\Admin\AdminTontineController::class, 'index'])->name('admin.tontines.index');
    Route::get('/tontines/{id}', [\App\Http\Controllers\Web\Admin\AdminTontineController::class, 'show'])->name('admin.tontines.show');
    Route::post('/tontines/{id}/disable', [\App\Http\Controllers\Web\Admin\AdminTontineController::class, 'disable'])->name('admin.tontines.disable');
    Route::post('/tontines/{id}/enable', [\App\Http\Controllers\Web\Admin\AdminTontineController::class, 'enable'])->name('admin.tontines.enable');
    Route::post('/tontines/{id}/cycle/{cycle}/process-payout', [\App\Http\Controllers\Web\Admin\AdminTontineController::class, 'processPayout'])->name('admin.tontines.process-payout');

    Route::get('/transactions', [\App\Http\Controllers\Web\Admin\AdminTransactionController::class, 'index'])->name('admin.transactions.index');
    Route::get('/platform-earnings', [\App\Http\Controllers\Web\Admin\AdminPlatformEarningController::class, 'index'])->name('admin.platform-earnings.index');

    // Audit Logs
    Route::get('/audit', [\App\Http\Controllers\Web\Admin\AdminAuditLogController::class, 'index'])->name('admin.audit.index');
    Route::get('/audit/{id}', [\App\Http\Controllers\Web\Admin\AdminAuditLogController::class, 'show'])->name('admin.audit.show');

    // Users
    Route::get('/users', [\App\Http\Controllers\Web\Admin\AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/users/{id}', [\App\Http\Controllers\Web\Admin\AdminUserController::class, 'show'])->name('admin.users.show');
    Route::post('/users/{id}/block', [\App\Http\Controllers\Web\Admin\AdminUserController::class, 'block'])->name('admin.users.block');
    Route::post('/users/{id}/unblock', [\App\Http\Controllers\Web\Admin\AdminUserController::class, 'unblock'])->name('admin.users.unblock');
});


