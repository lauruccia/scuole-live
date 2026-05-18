<?php

use App\Models\EmailTemplate;
use Illuminate\Database\Migrations\Migration;

/**
 * Inserisce (upsert) il template email "material_assigned" nella tabella
 * email_templates. Richiamando php artisan migrate il template sarà
 * immediatamente disponibile in produzione senza dover eseguire il seeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        EmailTemplate::updateOrCreate(
            ['slug' => 'material_assigned'],
            [
                'name'     => 'Nuovo materiale didattico disponibile',
                'category' => 'Didattica',
                'subject'  => 'Nuovo materiale disponibile: {{titolo_materiale}}',
                'trigger_event' => 'material.assigned',
                'is_active'     => true,
                'available_variables' => [
                    ['key' => 'nome',             'description' => 'Nome dello studente'],
                    ['key' => 'titolo_materiale', 'description' => 'Titolo del materiale'],
                    ['key' => 'tipo_materiale',   'description' => 'Tipo (Dispensa, Esercizio, Video…)'],
                    ['key' => 'lingua',           'description' => 'Lingua del materiale'],
                    ['key' => 'descrizione',      'description' => 'Descrizione del materiale (può essere vuota)'],
                    ['key' => 'docente',          'description' => 'Nome del docente che ha caricato il materiale'],
                    ['key' => 'portale_url',      'description' => 'URL sezione Materiali del portale studente'],
                ],
                'body_html' => <<<'HTML'
<p>Ciao <strong>{{nome}}</strong>,</p>

<p>il tuo docente ha reso disponibile un nuovo materiale didattico per il tuo corso.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f5ff; border:1px solid #c3d4ef; border-radius:8px; margin:20px 0;">
  <tr><td style="padding:18px 20px;">
    <p style="margin:0 0 6px; font-weight:bold; color:#1e3a5f;">📚 Dettagli materiale</p>
    <p style="margin:0 0 4px;">📌 <strong>Titolo:</strong> {{titolo_materiale}}</p>
    <p style="margin:0 0 4px;">🏷️ <strong>Tipo:</strong> {{tipo_materiale}}</p>
    <p style="margin:0 0 4px;">🌍 <strong>Lingua:</strong> {{lingua}}</p>
    <p style="margin:0 0 4px;">👩‍🏫 <strong>Docente:</strong> {{docente}}</p>
    <p style="margin:8px 0 0; color:#555; font-size:14px;">{{descrizione}}</p>
  </td></tr>
</table>

<p>Puoi accedere al materiale direttamente dal tuo portale:</p>

<table width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;">
  <tr>
    <td align="center">
      <a href="{{portale_url}}"
         style="display:inline-block; background:#1e3a5f; color:#ffffff; font-family:Arial,Helvetica,sans-serif;
                font-size:15px; font-weight:bold; text-decoration:none; padding:12px 32px;
                border-radius:6px;">
        📂 Vai ai materiali
      </a>
    </td>
  </tr>
</table>

<p style="font-size:13px; color:#888;">Se non riesci ad accedere, contatta la segreteria.</p>
HTML,
            ]
        );
    }

    public function down(): void
    {
        EmailTemplate::where('slug', 'material_assigned')->delete();
    }
};
