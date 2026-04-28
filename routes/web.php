<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContractDocumentController;
use App\Http\Controllers\GoogleOAuthController;
use App\Http\Controllers\StudentContractPrintController;
use App\Http\Controllers\Reports\TeacherHoursPdfController;

Route::get('/', fn () => view('welcome'));

// ✅ solo staff scuola
Route::middleware(['auth', 'role:superadmin|Amministrazione|Segreteria'])->group(function () {
    // ✅ stampa (view)
    Route::get('/contracts/{contract}/print', [ContractDocumentController::class, 'show'])
        ->name('contracts.print');

    // ✅ PDF inline
    Route::get('/contracts/{contract}/pdf', [ContractDocumentController::class, 'pdf'])
        ->name('contracts.pdf');

    // ✅ download PDF
    Route::get('/contracts/{contract}/download', [ContractDocumentController::class, 'download'])
        ->name('contracts.download');

    // ✅ Export PDF report ore docenti
    Route::get('/reports/teacher-hours/pdf', TeacherHoursPdfController::class)
        ->name('reports.teacher-hours.pdf');

    // ✅ Google OAuth - avvio collegamento
    Route::get('/google/oauth/redirect', [GoogleOAuthController::class, 'redirect'])
        ->name('google.oauth.redirect');
});

// ✅ Callback OAuth: basta auth, il controllo ruolo lo facciamo nel controller
Route::middleware(['auth'])->get('/google/oauth/callback', [GoogleOAuthController::class, 'callback'])
    ->name('google.oauth.callback');

Route::middleware(['auth'])->get(
    '/studente/contracts/{contract}/print',
    StudentContractPrintController::class
)->name('student.contracts.print');
