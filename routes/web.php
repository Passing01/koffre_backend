<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Web\WebContributionController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/c/{id}', [WebContributionController::class, 'show'])->name('cagnotte.web_show');
Route::post('/c/{id}/contribute', [WebContributionController::class, 'contribute'])->name('cagnotte.web_contribute');

Route::get('/payments/success', function () {
    return view('contributions.success');
})->name('payment.success');

Route::get('/payments/cancel', function () {
    return view('contributions.cancel');
})->name('payment.cancel');

// Admin Authentication Routes
Route::prefix('admin')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Web\Admin\AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [\App\Http\Controllers\Web\Admin\AdminAuthController::class, 'sendOtp'])->name('admin.send-otp');
    Route::get('/verify-otp', [\App\Http\Controllers\Web\Admin\AdminAuthController::class, 'showVerifyForm'])->name('admin.verify-otp');
    Route::post('/verify-otp', [\App\Http\Controllers\Web\Admin\AdminAuthController::class, 'verifyOtp'])->name('admin.verify-otp.submit');
    Route::post('/logout', [\App\Http\Controllers\Web\Admin\AdminAuthController::class, 'logout'])->name('admin.logout');
});

Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Web\Admin\AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/cagnottes', [\App\Http\Controllers\Web\Admin\AdminCagnotteController::class, 'index'])->name('admin.cagnottes.index');
    Route::get('/cagnottes/{id}', [\App\Http\Controllers\Web\Admin\AdminCagnotteController::class, 'show'])->name('admin.cagnottes.show');
    Route::post('/cagnottes/{id}/approve-unlock', [\App\Http\Controllers\Web\Admin\AdminCagnotteController::class, 'approveUnlock'])->name('admin.cagnottes.approve-unlock');
    Route::post('/cagnottes/{id}/reject-unlock', [\App\Http\Controllers\Web\Admin\AdminCagnotteController::class, 'rejectUnlock'])->name('admin.cagnottes.reject-unlock');
    Route::get('/transactions', [\App\Http\Controllers\Web\Admin\AdminTransactionController::class, 'index'])->name('admin.transactions.index');

    // Audit Logs
    Route::get('/audit', [\App\Http\Controllers\Web\Admin\AdminAuditLogController::class, 'index'])->name('admin.audit.index');
    Route::get('/audit/{id}', [\App\Http\Controllers\Web\Admin\AdminAuditLogController::class, 'show'])->name('admin.audit.show');
});


