<?php

namespace App\Http\Controllers;

use App\Models\GoogleAccount;
use Google\Client as GoogleClient;
use Google\Service\Calendar;
use Google\Service\Oauth2;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GoogleOAuthController extends Controller
{
    private function ensureCanAccess(): void
    {
        $user = Auth::user();

        abort_unless(
            $user && $user->hasAnyRole(['superadmin', 'Amministrazione', 'Segreteria']),
            403,
            'Non autorizzato al collegamento Google.'
        );
    }

    public function redirect()
    {
        $this->ensureCanAccess();

        // Genera e salva in sessione un token CSRF anti-CSRF per il callback OAuth
        $state = Str::random(40);
        session(['google_oauth_state' => $state]);

        $client = $this->makeClient();
        $client->setState($state);

        return redirect()->away($client->createAuthUrl());
    }

    public function callback(Request $request)
    {
        $this->ensureCanAccess();

        if ($request->filled('error')) {
            return redirect('/superadmin/google-settings')
                ->with('danger', 'Collegamento Google annullato o non autorizzato: ' . $request->query('error'));
        }

        // Verifica state anti-CSRF
        $expectedState = session('google_oauth_state');
        $returnedState = $request->query('state');

        if (! $expectedState || ! hash_equals($expectedState, (string) $returnedState)) {
            abort(403, 'OAuth state mismatch — possibile attacco CSRF.');
        }

        session()->forget('google_oauth_state');

        $code = $request->query('code');
        abort_unless($code, 400, 'Missing Google OAuth code.');

        $client = $this->makeClient();

        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (! empty($token['error'])) {
            return redirect('/superadmin/google-settings')
                ->with('danger', 'Errore Google OAuth: ' . ($token['error_description'] ?? $token['error']));
        }

        $client->setAccessToken($token);

        $oauth2 = new Oauth2($client);
        $me = $oauth2->userinfo->get();

        $account = GoogleAccount::query()->firstOrCreate(
            ['id' => 1],
            [
                'label' => 'Scuola',
                'calendar_id' => config('services.google.calendar_id', 'primary'),
            ]
        );

        // refresh_token spesso arriva solo al primo consenso o con prompt=consent
        $refreshToken = $token['refresh_token'] ?? $account->refresh_token;

        $account->update([
            'label' => 'Scuola',
            'email' => $me->email ?? $account->email,
            'calendar_id' => $account->calendar_id ?: config('services.google.calendar_id', 'primary'),
            'access_token' => json_encode($token),
            'refresh_token' => $refreshToken,
            'expires_at' => now()->addSeconds((int) ($token['expires_in'] ?? 3600)),
        ]);

        return redirect('/superadmin/google-settings')
            ->with('success', '✅ Account Google collegato correttamente.');
    }

    private function makeClient(): GoogleClient
    {
        $client = new GoogleClient();

        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));

        $client->setAccessType('offline');
        $client->setPrompt('consent');

        $client->addScope(Calendar::CALENDAR);
        $client->addScope(Oauth2::USERINFO_EMAIL);
        $client->addScope(Oauth2::USERINFO_PROFILE);

        return $client;
    }
}
