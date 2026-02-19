<?php

namespace App\Http\Controllers;

use App\Models\GoogleAccount;
use Google\Client as GoogleClient;
use Google\Service\Calendar;
use Google\Service\Oauth2;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoogleOAuthController extends Controller
{
    private function ensureCanAccess(): void
    {
        $u = Auth::user();

        abort_unless(
            $u && $u->hasAnyRole(['superadmin', 'amministrazione', 'segreteria']),
            403
        );
    }

    public function redirect()
    {
        $this->ensureCanAccess();

        $client = $this->makeClient();
        return redirect()->away($client->createAuthUrl());
    }

    public function callback(Request $request)
    {
        $this->ensureCanAccess();

        $client = $this->makeClient();

        $code = $request->query('code');
        abort_unless($code, 400, 'Missing code');

        $token = $client->fetchAccessTokenWithAuthCode($code);
        abort_if(isset($token['error']), 400, 'Google OAuth error: ' . ($token['error_description'] ?? $token['error']));

        // Recupero email account Google collegato
        $client->setAccessToken($token);
        $oauth2 = new Oauth2($client);
        $me = $oauth2->userinfo->get();

        $account = GoogleAccount::query()->firstOrCreate(
            ['id' => 1],
            ['label' => 'Scuola', 'calendar_id' => config('services.google.calendar_id', 'primary')]
        );

        // IMPORTANTE: refresh_token arriva solo al primo consenso o con prompt=consent
        $refresh = $token['refresh_token'] ?? $account->refresh_token;

        $account->update([
            'email' => $me->email ?? $account->email,
            'access_token' => json_encode($token),
            'refresh_token' => $refresh,
            'expires_at' => now()->addSeconds((int)($token['expires_in'] ?? 3600)),
        ]);

        return redirect('/superadmin/google-settings')
            ->with('success', '✅ Account Google collegato!');
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

        return $client;
    }
}
