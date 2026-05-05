<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Token autocontenuto per il link di disiscrizione email.
 *
 * Formato: base64url( email | timestamp | hmac_sha256(email|timestamp, APP_KEY) )
 *
 * Vantaggi:
 *  - NESSUN database lookup per generare/validare (scala bene).
 *  - Auto-scadenza configurabile (default 1 anno).
 *  - Firma HMAC con APP_KEY: nessuno può forgiare un token valido senza la key.
 *
 * Uso:
 *   $token = UnsubscribeToken::generate('mario@example.com');
 *   $url   = url('/unsubscribe/' . $token);
 *   $email = UnsubscribeToken::verify($token);  // null se invalido/scaduto
 */
class UnsubscribeToken
{
    /** Validità token in secondi (default: 365 giorni) */
    private const TTL_SECONDS = 365 * 24 * 60 * 60;

    public static function generate(string $email): string
    {
        $email = strtolower(trim($email));
        $ts    = time();
        $payload = $email . '|' . $ts;
        $hmac    = self::sign($payload);

        // base64url
        $token = rtrim(strtr(base64_encode($payload . '|' . $hmac), '+/', '-_'), '=');
        return $token;
    }

    /**
     * Verifica un token e restituisce l'email se valido, null altrimenti.
     */
    public static function verify(string $token): ?string
    {
        try {
            $padded  = str_pad(strtr($token, '-_', '+/'), strlen($token) % 4 === 0 ? strlen($token) : strlen($token) + (4 - strlen($token) % 4), '=');
            $decoded = base64_decode($padded, true);

            if ($decoded === false) {
                return null;
            }

            $parts = explode('|', $decoded);
            if (count($parts) !== 3) {
                return null;
            }

            [$email, $ts, $providedHmac] = $parts;

            // Verifica firma costante-time
            $expectedHmac = self::sign($email . '|' . $ts);
            if (! hash_equals($expectedHmac, $providedHmac)) {
                return null;
            }

            // Verifica scadenza
            $issuedAt = (int) $ts;
            if ($issuedAt + self::TTL_SECONDS < time()) {
                return null;
            }

            return $email;
        } catch (\Throwable $e) {
            Log::warning('UnsubscribeToken: errore verifica token', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private static function sign(string $payload): string
    {
        $key = (string) config('app.key');
        // Se APP_KEY è in formato "base64:...", lo decodifichiamo
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true) ?: $key;
        }
        return hash_hmac('sha256', $payload, $key);
    }
}
