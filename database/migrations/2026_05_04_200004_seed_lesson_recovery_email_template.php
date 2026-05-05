<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Inserisce il template email per la notifica di recupero automatico lezione.
 *
 * Variabili disponibili:
 *   {{nome_studente}}      — nome e cognome dello studente
 *   {{data_originale}}     — data/ora della lezione annullata (es. "lunedì 12 maggio 2026 alle 10:00")
 *   {{data_recupero}}      — data/ora del recupero pianificato
 *   {{docente}}            — nome del docente
 *   {{lingua}}             — lingua del corso
 *   {{nome_scuola}}        — nome della scuola (da SchoolSetting)
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('email_templates')->updateOrInsert(
            ['slug' => 'lesson_recovery_created'],
            [
                'name'          => 'Notifica recupero automatico lezione',
                'slug'          => 'lesson_recovery_created',
                'trigger_event' => 'lesson.recovery.created',
                'subject'       => 'La tua lezione è stata recuperata — {{data_recupero}}',
                'body_html'     => <<<'HTML'
<p>Gentile <strong>{{nome_studente}}</strong>,</p>

<p>ti informiamo che la lezione del <strong>{{data_originale}}</strong> è stata annullata e un recupero è stato pianificato automaticamente.</p>

<table cellpadding="0" cellspacing="0" style="background:#f0f6ff; border-left:4px solid #1e3a5f; border-radius:4px; padding:16px 20px; margin:20px 0; width:100%;">
  <tr>
    <td>
      <p style="margin:0 0 8px; font-size:14px; color:#555;">📅 <strong>Data recupero:</strong> {{data_recupero}}</p>
      <p style="margin:0 0 8px; font-size:14px; color:#555;">👩‍🏫 <strong>Docente:</strong> {{docente}}</p>
      <p style="margin:0;     font-size:14px; color:#555;">🗣️ <strong>Lingua:</strong> {{lingua}}</p>
    </td>
  </tr>
</table>

<p>Se hai domande o necessiti di modificare la data del recupero, contatta la segreteria.</p>

<p>Grazie,<br>
<strong>{{nome_scuola}}</strong></p>
HTML,
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('email_templates')->where('slug', 'lesson_recovery_created')->delete();
    }
};
