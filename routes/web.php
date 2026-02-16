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
