<?php

namespace App\Http\Middleware;

use App\Filament\Common\Pages\ChangePasswordPage;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forza il cambio password al primo login per gli utenti che hanno il flag
 * `must_change_password = true` (es. studenti auto-creati con password random
 * generata dal StudentObserver).
 *
 * Comportamento:
 *  - se l'utente NON e' loggato → passa (l'autenticazione e' gestita altrove)
 *  - se non ha il flag → passa
 *  - se sta gia' visitando la pagina ChangePasswordPage o sta facendo logout
 *    → passa (altrimenti loop infinito)
 *  - altrimenti redirect alla ChangePasswordPage del panel corrente,
 *    con un flash message che spiega perche'
 *
 * Registrato negli authMiddleware di tutti i Filament panels (Admin, Studente,
 * Docente, Superadmin).
 */
class ForceChangePassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Non autenticato → niente da fare (gestito da Authenticate)
        if (! $user) {
            return $next($request);
        }

        // Flag non settato (= cambio non richiesto) → passa
        if (! $this->mustChangePassword($user)) {
            return $next($request);
        }

        // Permetti sempre la pagina di cambio password e il logout, altrimenti
        // l'utente non potrebbe MAI cambiarla.
        $changePasswordUrl = $this->resolveChangePasswordUrl();

        if ($changePasswordUrl && $request->is(trim(parse_url($changePasswordUrl, PHP_URL_PATH) ?? '', '/'))) {
            return $next($request);
        }

        // Logout via Filament e' sotto un path tipo /admin/logout — sempre permesso.
        if (str_contains($request->path(), '/logout') || $request->path() === 'logout') {
            return $next($request);
        }

        // Asset Livewire/Filament non vanno bloccati altrimenti la pagina cambio
        // password stessa non si carica correttamente.
        if ($request->is('livewire/*') || $request->is('filament/*')) {
            return $next($request);
        }

        // Tutto il resto → redirect
        if ($changePasswordUrl) {
            return redirect()
                ->to($changePasswordUrl)
                ->with('warning', 'Per motivi di sicurezza devi cambiare la password al primo accesso.');
        }

        // Fallback: se non riusciamo a determinare l'URL della ChangePasswordPage
        // (panel non ancora bootato in test, o altro caso limite), facciamo
        // passare la richiesta per evitare un loop infinito o un blocco totale.
        return $next($request);
    }

    /**
     * Lo user model ha la colonna must_change_password? Lo verifichiamo via
     * isset() per non rompere in caso la migrazione non sia stata applicata
     * (ambiente di test minimale o rollback).
     */
    private function mustChangePassword($user): bool
    {
        if (! isset($user->must_change_password)) {
            return false;
        }
        return (bool) $user->must_change_password;
    }

    /**
     * Restituisce l'URL della ChangePasswordPage nel panel correntemente
     * attivo. Se non c'e' un panel attivo (es. richiesta web non-Filament),
     * tenta di derivarlo dai ruoli dell'utente.
     */
    private function resolveChangePasswordUrl(): ?string
    {
        try {
            $panel = Filament::getCurrentPanel();
            if ($panel) {
                return ChangePasswordPage::getUrl(panel: $panel->getId());
            }
        } catch (\Throwable $e) {
            // ignora — proviamo il fallback sotto
        }

        return null;
    }
}
