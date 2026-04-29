<?php

namespace Database\Seeders;

use App\Models\SchoolSetting;
use Illuminate\Database\Seeder;

class SchoolSettingSeeder extends Seeder
{
    /**
     * Popola le impostazioni di base della scuola.
     * Usa updateOrCreate: eseguibile più volte senza duplicati.
     */
    public function run(): void
    {
        $defaults = [
            // ── Brand ─────────────────────────────────────────────────────────
            'school_name'        => 'A&A Language Center',
            'school_legal_name'  => 'A&A Language Center Srl',

            // ── Sede ──────────────────────────────────────────────────────────
            'school_address'     => 'Viale Leonardo Da Vinci 193',
            'school_city'        => 'Roma',
            'school_zip'         => '00145',

            // ── Contatti ──────────────────────────────────────────────────────
            'school_phone'       => '+39 06.5743734',
            'school_mobile'      => '+39 346 3836175',
            'school_website'     => 'https://www.aealanguagecenter.it',
            'school_email'       => 'info@aealanguagecenter.it',

            // ── Banca ─────────────────────────────────────────────────────────
            'bank_iban'          => '',          // ← compilare prima del go-live
            'bank_intestatario'  => 'A&A Language Center Srl',

            // ── Funzionalità ──────────────────────────────────────────────────
            'digital_signature_enabled' => '0',

            // ── Ricevute PDF ──────────────────────────────────────────────────
            'ricevuta_enabled'       => '1',
            'ricevuta_label'         => 'RICEVUTA',
            'ricevuta_header_note'   => 'Scuola di lingue',
            'ricevuta_thank_you_text'  => 'Grazie per il pagamento. Questa ricevuta conferma la ricezione dell\'importo indicato.',
            'ricevuta_disclaimer'    => 'Documento generato automaticamente — Non ha valore fiscale ai sensi del D.P.R. 633/72.',
        ];

        foreach ($defaults as $key => $value) {
            SchoolSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
