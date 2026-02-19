<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContractDocumentController;
use App\Http\Controllers\GoogleOAuthController;

Route::get('/', fn () => view('welcome'));

// ✅ solo staff scuola
Route::middleware(['web', 'auth', 'role:superadmin|amministrazione|segreteria'])
    ->group(function () {

        Route::get('/contracts/{contract}/print', [ContractDocumentController::class, 'show'])
            ->name('contracts.print');

        Route::get('/contracts/{contract}/pdf', [ContractDocumentController::class, 'pdf'])
            ->name('contracts.pdf');

        // ✅ download PDF
        Route::get('/contracts/{contract}/download', [ContractDocumentController::class, 'download'])
            ->name('contracts.download');

        // ✅ Google OAuth (collega account scuola)
        Route::get('/google/oauth/redirect', [GoogleOAuthController::class, 'redirect'])
            ->name('google.oauth.redirect');

        Route::get('/google/oauth/callback', [GoogleOAuthController::class, 'callback'])
            ->name('google.oauth.callback');
    });
