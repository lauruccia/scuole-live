<?php

namespace App\Http\Controllers;

use App\Models\StudentUnsubscribe;
use App\Support\UnsubscribeToken;
use Illuminate\Http\Request;

/**
 * Controller per il flusso di disiscrizione email GDPR.
 *
 * Route:
 *   GET  /unsubscribe/{token}        → show()    (pagina conferma)
 *   POST /unsubscribe/{token}        → confirm() (esegue la disiscrizione)
 */
class UnsubscribeController extends Controller
{
    /**
     * Mostra la pagina con bottone di conferma.
     */
    public function show(string $token)
    {
        $email = UnsubscribeToken::verify($token);

        if (! $email) {
            return response()
                ->view('unsubscribe.invalid', [], 410);
        }

        // Già disiscritto?
        if (StudentUnsubscribe::isUnsubscribed($email)) {
            return view('unsubscribe.confirmed', [
                'email'   => $email,
                'already' => true,
            ]);
        }

        return view('unsubscribe.show', [
            'email' => $email,
            'token' => $token,
        ]);
    }

    /**
     * Esegue la disiscrizione.
     */
    public function confirm(Request $request, string $token)
    {
        $email = UnsubscribeToken::verify($token);

        if (! $email) {
            return response()
                ->view('unsubscribe.invalid', [], 410);
        }

        $reason = $request->input('reason');
        if (is_string($reason)) {
            $reason = mb_substr(trim($reason), 0, 100);
        }

        StudentUnsubscribe::firstOrCreate(
            ['email' => $email],
            [
                'reason'          => $reason ?: null,
                'ip_address'      => $request->ip(),
                'user_agent'      => substr((string) $request->userAgent(), 0, 500),
                'unsubscribed_at' => now(),
            ]
        );

        return view('unsubscribe.confirmed', [
            'email'   => $email,
            'already' => false,
        ]);
    }
}
