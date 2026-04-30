<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SchoolSetting extends Model
{
    protected $table = 'school_settings';

    protected $fillable = ['key', 'value'];

    // ─── Helper statici ───────────────────────────────────────────────────────

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("school_setting_{$key}", 300, function () use ($key, $default) {
            $row = static::where('key', $key)->first();
            return $row ? $row->value : $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("school_setting_{$key}");
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $val = static::get($key);
        if ($val === null) return $default;
        return in_array($val, ['1', 'true', 'yes', true], true);
    }

    // ─── Chiavi predefinite ───────────────────────────────────────────────────

    /** La firma digitale OTP è abilitata per questa scuola? */
    public static function isDigitalSignatureEnabled(): bool
    {
        return static::bool('digital_signature_enabled', false);
    }

    // ─── Brand / Identità scuola ─────────────────────────────────────────────

    /** Nome commerciale (es. "A&A Language Center") */
    public static function schoolName(): string
    {
        return static::get('school_name', config('app.name', 'A&A Language Center'));
    }

    /** Ragione sociale completa (es. "A&A Language Center Srl") */
    public static function schoolLegalName(): string
    {
        return static::get('school_legal_name', static::schoolName());
    }

    /** Indirizzo (es. "Viale Leonardo Da Vinci 193") */
    public static function schoolAddress(): string
    {
        return static::get('school_address', '');
    }

    /** Città (es. "Roma") */
    public static function schoolCity(): string
    {
        return static::get('school_city', '');
    }

    /** CAP (es. "00145") */
    public static function schoolZip(): string
    {
        return static::get('school_zip', '');
    }

    /** Riga indirizzo completa (es. "Viale Leonardo Da Vinci 193, 00145 Roma") */
    public static function schoolFullAddress(): string
    {
        $parts = array_filter([
            static::schoolAddress(),
            trim(static::schoolZip() . ' ' . static::schoolCity()),
        ]);
        return implode(', ', $parts);
    }

    /** Telefono fisso (es. "+39 06.5743734") */
    public static function schoolPhone(): string
    {
        return static::get('school_phone', '');
    }

    /** Mobile / WhatsApp (es. "+39 346 3836175") */
    public static function schoolMobile(): string
    {
        return static::get('school_mobile', '');
    }

    /** Sito web (es. "https://www.aealanguagecenter.it") */
    public static function schoolWebsite(): string
    {
        return static::get('school_website', config('app.url', ''));
    }

    /** Email pubblica (es. "info@aealanguagecenter.it") */
    public static function schoolEmail(): string
    {
        return static::get('school_email', config('mail.from.address', ''));
    }

    // ─── Dati bancari ─────────────────────────────────────────────────────────

    /** IBAN per pagamenti tramite bonifico */
    public static function bankIban(): string
    {
        return static::get('bank_iban', config('services.bank.iban', ''));
    }

    /** Intestatario conto corrente */
    public static function bankIntestatario(): string
    {
        return static::get('bank_intestatario', config('services.bank.intestatario', static::schoolLegalName()));
    }

    // ─── Ricevute PDF ─────────────────────────────────────────────────────────

    /** Il download ricevuta rata è abilitato? */
    public static function isRicevutaEnabled(): bool
    {
        return static::bool('ricevuta_enabled', true);
    }

    /** Etichetta documento (es. "RICEVUTA" o "QUIETANZA DI PAGAMENTO") */
    public static function ricevutaLabel(): string
    {
        return static::get('ricevuta_label', 'RICEVUTA');
    }

    /** Sottotitolo header email/PDF (es. "Scuola di lingue — Roma") */
    public static function ricevutaHeaderNote(): string
    {
        return static::get('ricevuta_header_note', '');
    }

    /** Testo di ringraziamento sotto la tabella (visibile solo se rata pagata) */
    public static function ricevutaThankYouText(): string
    {
        return static::get(
            'ricevuta_thank_you_text',
            'Grazie per il pagamento. Questa ricevuta conferma la ricezione dell\'importo.'
        );
    }

    /** Nota legale/fiscale nel footer */
    public static function ricevutaDisclaimerText(): string
    {
        return static::get(
            'ricevuta_disclaimer',
            'Documento generato automaticamente — Non ha valore fiscale ai sensi del D.P.R. 633/72.'
        );
    }
}
