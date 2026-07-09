<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContractDocumentController;
use App\Http\Controllers\GoogleOAuthController;
use App\Http\Controllers\HomeworkGradeController;
use App\Http\Controllers\MaterialVisibilityController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StudentContractPrintController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\Reports\TeacherHoursPdfController;

// ─── SEO: sitemap dinamica ───────────────────────────────────────────────────
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// ─── Pagine pubbliche ─────────────────────────────────────────────────────────
Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/iscriviti', [PublicController::class, 'iscriviti'])->name('iscrizione');
Route::get('/grazie', [PublicController::class, 'grazie'])->name('iscrizione.grazie');
Route::get('/privacy', [PublicController::class, 'privacy'])->name('privacy');
Route::get('/la-scuola', [PublicController::class, 'laScuola'])->name('la-scuola');
Route::get('/per-le-aziende', [PublicController::class, 'perLeAziende'])->name('per-le-aziende');
Route::get('/servizi', [PublicController::class, 'servizi'])->name('servizi');
Route::get('/lavora-con-noi', [PublicController::class, 'lavoraConNoi'])->name('lavora-con-noi');

// Candidatura docenti (form con upload CV) — throttle anti-spam come /iscriviti
Route::post('/lavora-con-noi', [PublicController::class, 'lavoraConNoiStore'])
    ->middleware('throttle:5,1')
    ->name('lavora-con-noi.store');
Route::get('/contattaci', [PublicController::class, 'contattaci'])->name('contattaci');
Route::get('/le-certificazioni', [PublicController::class, 'leCertificazioni'])->name('le-certificazioni');

// ─── News ed Eventi ───────────────────────────────────────────────────────────
Route::get('/news', [PublicController::class, 'newsIndex'])->name('news.index');
Route::get('/news/{slug}', [PublicController::class, 'newsShow'])->name('news.show');

// ─── SEO Landing pages (long-tail keyword) ───────────────────────────────────
Route::get('/corsi-inglese-roma', [PublicController::class, 'landingInglese'])->name('landing.inglese');
Route::get('/corsi-italiano-stranieri-roma', [PublicController::class, 'landingItalianoStranieri'])->name('landing.italiano-stranieri');
Route::get('/corsi-aziendali-roma', [PublicController::class, 'landingAziendali'])->name('landing.aziendali');

// Throttle anti-spam: max 5 invii per IP al minuto
Route::post('/iscriviti', [PublicController::class, 'iscrivitiStore'])
    ->middleware('throttle:5,1')
    ->name('iscrizione.store');

// ─── Catalogo corsi + checkout ────────────────────────────────────────────────
Route::get('/corsi', [CheckoutController::class, 'catalogo'])->name('checkout.catalogo');
Route::get('/corsi/{course}', [CheckoutController::class, 'show'])->name('checkout.show');

// Throttle anti-abuse: max 3 checkout per IP al minuto (evita session Stripe duplicate)
Route::post('/corsi/{course}/checkout', [CheckoutController::class, 'store'])
    ->middleware('throttle:3,1')
    ->name('checkout.store');
Route::get('/checkout/bonifico/{purchase}', [CheckoutController::class, 'bonifico'])->name('checkout.bonifico');
Route::get('/checkout/stripe/return', [CheckoutController::class, 'stripeReturn'])->name('checkout.stripe.return');
Route::get('/checkout/paypal/return', [CheckoutController::class, 'paypalReturn'])->name('checkout.paypal.return');
Route::get('/checkout/paypal/cancel', [CheckoutController::class, 'paypalCancel'])->name('checkout.paypal.cancel');
// Pagine di esito checkout: protette da signed URL per evitare info leak
// (chi conosce un purchase id non puo' visitarle senza il token firmato).
// Il middleware 'signed' valida la firma e l'eventuale scadenza.
Route::get('/checkout/grazie/{purchase}', [CheckoutController::class, 'grazie'])
    ->middleware('signed')
    ->name('checkout.grazie');
Route::get('/checkout/errore/{purchase}', [CheckoutController::class, 'errore'])
    ->middleware('signed')
    ->name('checkout.errore');

// ─── Webhook gateway (CSRF escluso tramite VerifyCsrfToken) ──────────────────
Route::post('/webhook/stripe', [WebhookController::class, 'stripe'])->name('webhook.stripe');
Route::post('/webhook/paypal', [WebhookController::class, 'paypal'])->name('webhook.paypal');

// ─── Valutazione compiti (staff e docenti) ────────────────────────────────────
Route::middleware(['auth', 'role:superadmin|Amministrazione|Segreteria|docente|Docente'])
    ->post('/admin/homework/{submission}/grade', HomeworkGradeController::class)
    ->name('admin.homework.grade');

// ─── Toggle visibilità materiale per contratto ────────────────────────────────
Route::middleware(['auth', 'role:superadmin|Amministrazione|Segreteria|admin'])
    ->patch('/admin/material/{material}/contract/{contract}/visibility', MaterialVisibilityController::class)
    ->name('admin.material.toggle-visibility');

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
//    (verifica state anti-CSRF è gestita nel controller stesso)
Route::middleware(['auth'])
    ->get('/google/oauth/callback', [GoogleOAuthController::class, 'callback'])
    ->name('google.oauth.callback');

// ─── Stampa contratto firmato dal pannello studente ──────────────────────────
//    (controller invokable: __invoke gestisce sia visualizzazione che PDF)
Route::middleware(['auth'])
    ->get('/studente/contratto/{contract}/print', StudentContractPrintController::class)
    ->name('studente.contratto.print');

// ─── Download backup (solo Superadmin) ───────────────────────────────────────
Route::middleware(['auth'])
    ->get('/admin/backup/download/{filename}', function (\Illuminate\Http\Request $request, string $filename) {
        // Solo Superadmin
        if (! auth()->user()?->hasAnyRole(['Superadmin', 'superadmin', 'super_admin'])) {
            abort(403);
        }

        // Sanity check: solo .zip, nessun path traversal nel filename
        if (! str_ends_with($filename, '.zip') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            abort(400, 'Nome file non valido.');
        }

        $disk    = \Illuminate\Support\Facades\Storage::disk('local-backups');
        $appName = config('backup.backup.name', config('app.name', 'ScuoleLive'));

        // Prova il percorso con folder passato come query param, poi con appName, poi root
        $folder    = $request->query('folder', $appName);
        $candidate = $folder && $folder !== '.' ? ltrim($folder, '/\\') . '/' . $filename : $filename;

        // Sanity check sul folder (nessun traversal)
        if (str_contains($candidate, '..')) {
            abort(400, 'Percorso non valido.');
        }

        foreach ([$candidate, $appName . '/' . $filename, $filename] as $path) {
            if ($disk->exists($path)) {
                return response()->download($disk->path($path), $filename);
            }
        }

        abort(404, 'Backup non trovato.');
    })
    ->name('backup.download');

// ─── Disiscrizione GDPR (token HMAC autocontenuto, no auth necessaria) ────────
Route::get('/unsubscribe/{token}', [\App\Http\Controllers\UnsubscribeController::class, 'show'])
    ->name('unsubscribe.show');
Route::post('/unsubscribe/{token}', [\App\Http\Controllers\UnsubscribeController::class, 'confirm'])
    ->middleware('throttle:10,1')
    ->name('unsubscribe.confirm');
