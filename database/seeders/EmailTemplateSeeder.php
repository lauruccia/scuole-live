<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // NOTA: il body_html contiene solo il contenuto del messaggio.
        // Header (logo A&A), layout e firma vengono aggiunti automaticamente
        // da EmailTemplateService::wrapInLayout() a ogni invio.

        $templates = [

            // ─── BENVENUTO STUDENTE ───────────────────────────────────────────
            [
                'slug'     => 'welcome_student',
                'name'     => 'Benvenuto studente',
                'category' => 'Studenti',
                'subject'  => 'Benvenuto/a in A&A Language Center!',
                'trigger_event' => 'student.created',
                'available_variables' => [
                    ['key' => 'nome',        'description' => 'Nome dello studente'],
                    ['key' => 'cognome',     'description' => 'Cognome dello studente'],
                    ['key' => 'email',       'description' => 'Email dello studente'],
                    ['key' => 'password',    'description' => 'Password provvisoria'],
                    ['key' => 'portale_url', 'description' => 'URL portale studente'],
                    ['key' => 'app_name',    'description' => 'Nome della scuola'],
                ],
                'body_html' => <<<'HTML'
<p>Ciao <strong>{{nome}}</strong>,</p>

<p>tutto lo staff di <strong>A&amp;A Language Center</strong> ti dà un caloroso benvenuto! 🎉</p>

<p>Siamo entusiaste di averti con noi. <strong>Il tuo viaggio verso la crescita personale inizia ora!</strong></p>

<p>Assicurati di memorizzare questo indirizzo di posta per evitare che finisca nello <strong>SPAM</strong>.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f5ff; border:1px solid #c3d4ef; border-radius:8px; margin:20px 0;">
  <tr><td style="padding:18px 20px;">
    <p style="margin:0 0 8px; font-weight:bold; color:#1e3a5f;">🔐 Le tue credenziali di accesso</p>
    <p style="margin:0 0 4px;">📌 <strong>Portale:</strong> <a href="{{portale_url}}" style="color:#1e3a5f;">{{portale_url}}</a></p>
    <p style="margin:0 0 4px;">✉️ <strong>Email:</strong> {{email}}</p>
    <p style="margin:0 0 8px;">🔑 <strong>Password provvisoria:</strong> <span style="font-family:monospace; background:#e8eef8; padding:2px 8px; border-radius:4px;">{{password}}</span></p>
    <p style="margin:0; font-size:13px; color:#555;">Ti consigliamo di cambiare la password al primo accesso.</p>
  </td></tr>
</table>

<p>Per qualsiasi domanda o assistenza siamo a tua disposizione:</p>
<ul>
  <li>📱 WhatsApp: <a href="https://wa.me/393463836175" style="color:#1e3a5f;">+39 346 3836175</a></li>
  <li>✉️ Email: <a href="mailto:info@aealanguagecenter.it" style="color:#1e3a5f;">info@aealanguagecenter.it</a></li>
  <li>☎️ Telefono: <strong>06 5743734</strong></li>
</ul>

<p><strong>Orari di apertura:</strong><br>
Lunedì – Venerdì: 10:00 – 19:00 | Sabato: 09:00 – 13:00</p>

<p>⚠️ Le lezioni prenotate vanno sempre disdette con almeno <strong>24 ore di preavviso</strong>, altrimenti saranno considerate come fruite.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#fff8e6; border-left:4px solid #f0b429; border-radius:4px; margin:20px 0;">
  <tr><td style="padding:14px 18px; font-style:italic; color:#555;">
    "Chi non conosce le lingue straniere, non sa nulla della propria."<br>
    <span style="font-style:normal; font-weight:bold; color:#333;">— Goethe</span>
  </td></tr>
</table>
HTML,
            ],

            // ─── LEZIONE ANNULLATA — RECUPERO ────────────────────────────────
            [
                'slug'     => 'lesson_cancelled_recoverable',
                'name'     => 'Lezione annullata (con recupero)',
                'category' => 'Lezioni',
                'subject'  => 'Comunicazione lezione — A&A Language Center',
                'trigger_event' => 'lesson.cancelled.recoverable',
                'available_variables' => [
                    ['key' => 'nome',         'description' => 'Nome studente'],
                    ['key' => 'data_lezione', 'description' => 'Data lezione (gg/mm/aaaa)'],
                    ['key' => 'ora_inizio',   'description' => 'Ora inizio'],
                    ['key' => 'ora_fine',     'description' => 'Ora fine'],
                    ['key' => 'lingua',       'description' => 'Lingua del corso'],
                    ['key' => 'docente',      'description' => 'Nome docente'],
                    ['key' => 'motivo',       'description' => 'Motivo annullamento'],
                ],
                'body_html' => <<<'HTML'
<p>Ciao <strong>{{nome}}</strong>,</p>

<p>ti comunichiamo che la seguente lezione è stata annullata:</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f9fb; border:1px solid #dde3ec; border-radius:8px; margin:16px 0;">
  <tr><td style="padding:16px 20px;">
    <p style="margin:0 0 4px;">📅 <strong>Data:</strong> {{data_lezione}}</p>
    <p style="margin:0 0 4px;">🕐 <strong>Orario:</strong> {{ora_inizio}} – {{ora_fine}}</p>
    <p style="margin:0 0 4px;">🌍 <strong>Lingua:</strong> {{lingua}}</p>
    <p style="margin:0;">👩‍🏫 <strong>Docente:</strong> {{docente}}</p>
  </td></tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#e8f5e9; border:1px solid #a5d6a7; border-radius:8px; margin:16px 0;">
  <tr><td style="padding:16px 20px;">
    <p style="margin:0 0 6px; font-weight:bold; color:#2e7d32;">✅ Lezione annullata — recupero previsto</p>
    <p style="margin:0;">La tua lezione è stata annullata con sufficiente preavviso (oltre 24 ore). Verrà programmata una lezione di recupero. Ti contatteremo per concordare la data.</p>
  </td></tr>
</table>
HTML,
            ],

            // ─── LEZIONE ANNULLATA — ORE SCALATE ─────────────────────────────
            [
                'slug'     => 'lesson_cancelled_consumed',
                'name'     => 'Lezione annullata (ore scalate)',
                'category' => 'Lezioni',
                'subject'  => 'Comunicazione lezione — A&A Language Center',
                'trigger_event' => 'lesson.cancelled.consumed',
                'available_variables' => [
                    ['key' => 'nome',         'description' => 'Nome studente'],
                    ['key' => 'data_lezione', 'description' => 'Data lezione'],
                    ['key' => 'ora_inizio',   'description' => 'Ora inizio'],
                    ['key' => 'ora_fine',     'description' => 'Ora fine'],
                    ['key' => 'lingua',       'description' => 'Lingua del corso'],
                    ['key' => 'docente',      'description' => 'Nome docente'],
                ],
                'body_html' => <<<'HTML'
<p>Ciao <strong>{{nome}}</strong>,</p>

<p>ti comunichiamo che la seguente lezione è stata annullata:</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f9fb; border:1px solid #dde3ec; border-radius:8px; margin:16px 0;">
  <tr><td style="padding:16px 20px;">
    <p style="margin:0 0 4px;">📅 <strong>Data:</strong> {{data_lezione}}</p>
    <p style="margin:0 0 4px;">🕐 <strong>Orario:</strong> {{ora_inizio}} – {{ora_fine}}</p>
    <p style="margin:0 0 4px;">🌍 <strong>Lingua:</strong> {{lingua}}</p>
    <p style="margin:0;">👩‍🏫 <strong>Docente:</strong> {{docente}}</p>
  </td></tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#fff3e0; border:1px solid #ffcc80; border-radius:8px; margin:16px 0;">
  <tr><td style="padding:16px 20px;">
    <p style="margin:0 0 6px; font-weight:bold; color:#e65100;">⚠️ Lezione annullata — ore scalate</p>
    <p style="margin:0 0 8px;">La tua lezione è stata annullata con meno di 24 ore di preavviso. Come da regolamento, le ore verranno scalate dal tuo contratto.</p>
    <p style="margin:0; font-size:13px; color:#555;">Ricorda: disdici sempre con almeno <strong>24 ore di anticipo</strong>.</p>
  </td></tr>
</table>
HTML,
            ],

            // ─── LEZIONE ANNULLATA — PERMANENTE ──────────────────────────────
            [
                'slug'     => 'lesson_cancelled_permanent',
                'name'     => 'Lezione annullata (definitivo)',
                'category' => 'Lezioni',
                'subject'  => 'Comunicazione lezione — A&A Language Center',
                'trigger_event' => 'lesson.cancelled.permanent',
                'available_variables' => [
                    ['key' => 'nome',         'description' => 'Nome studente'],
                    ['key' => 'data_lezione', 'description' => 'Data lezione'],
                    ['key' => 'ora_inizio',   'description' => 'Ora inizio'],
                    ['key' => 'ora_fine',     'description' => 'Ora fine'],
                    ['key' => 'lingua',       'description' => 'Lingua del corso'],
                    ['key' => 'docente',      'description' => 'Nome docente'],
                ],
                'body_html' => <<<'HTML'
<p>Ciao <strong>{{nome}}</strong>,</p>

<p>ti comunichiamo che la seguente lezione è stata annullata definitivamente:</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f9fb; border:1px solid #dde3ec; border-radius:8px; margin:16px 0;">
  <tr><td style="padding:16px 20px;">
    <p style="margin:0 0 4px;">📅 <strong>Data:</strong> {{data_lezione}}</p>
    <p style="margin:0 0 4px;">🕐 <strong>Orario:</strong> {{ora_inizio}} – {{ora_fine}}</p>
    <p style="margin:0 0 4px;">🌍 <strong>Lingua:</strong> {{lingua}}</p>
    <p style="margin:0;">👩‍🏫 <strong>Docente:</strong> {{docente}}</p>
  </td></tr>
</table>

<p>Le ore <strong>non</strong> verranno scalate dal tuo contratto. Per maggiori informazioni contatta la segreteria.</p>
HTML,
            ],

            // ─── CONTRATTO PDF ────────────────────────────────────────────────
            [
                'slug'     => 'contract_pdf',
                'name'     => 'Invio contratto PDF',
                'category' => 'Contratti',
                'subject'  => 'Il tuo contratto A&A Language Center — #{{numero_contratto}}',
                'trigger_event' => 'contract.sent',
                'available_variables' => [
                    ['key' => 'nome',              'description' => 'Nome studente'],
                    ['key' => 'cognome',           'description' => 'Cognome studente'],
                    ['key' => 'numero_contratto',  'description' => 'ID contratto'],
                    ['key' => 'lingua',            'description' => 'Lingua del corso'],
                    ['key' => 'nome_corso',        'description' => 'Nome del corso'],
                ],
                'body_html' => <<<'HTML'
<p>Ciao <strong>{{nome}}</strong>,</p>

<p>in allegato trovi il contratto <strong>#{{numero_contratto}}</strong> relativo al tuo percorso formativo.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f5ff; border:1px solid #c3d4ef; border-radius:8px; margin:16px 0;">
  <tr><td style="padding:16px 20px;">
    <p style="margin:0 0 6px; font-weight:bold; color:#1e3a5f;">Dettagli iscrizione</p>
    <p style="margin:0 0 4px;">📋 <strong>Contratto:</strong> #{{numero_contratto}}</p>
    <p style="margin:0 0 4px;">🌍 <strong>Lingua:</strong> {{lingua}}</p>
    <p style="margin:0;">📚 <strong>Corso:</strong> {{nome_corso}}</p>
  </td></tr>
</table>

<p>Ti preghiamo di leggere attentamente il documento. Per qualsiasi chiarimento siamo a tua disposizione.</p>
HTML,
            ],

            // ─── MATERIALE DIDATTICO ASSEGNATO ───────────────────────────────
            [
                'slug'     => 'material_assigned',
                'name'     => 'Nuovo materiale didattico disponibile',
                'category' => 'Didattica',
                'subject'  => 'Nuovo materiale disponibile: {{titolo_materiale}}',
                'trigger_event' => 'material.assigned',
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
            ],

            // ─── PROMEMORIA FINE CORSO ────────────────────────────────────────
            [
                'slug'     => 'course_end_reminder',
                'name'     => 'Promemoria fine corso',
                'category' => 'Contratti',
                'subject'  => 'Il tuo corso "{{nome_corso}}" si concluderà il {{data_fine_corso}}',
                'trigger_event' => null,
                'is_active' => true,
                'available_variables' => [
                    ['key' => 'nome_intestatario', 'description' => 'Nome intestatario contratto'],
                    ['key' => 'nome_corso',        'description' => 'Nome del corso'],
                    ['key' => 'data_fine_corso',   'description' => 'Data fine corso (gg/mm/aaaa)'],
                    ['key' => 'nome_scuola',       'description' => 'Nome della scuola'],
                ],
                'body_html' => <<<'HTML'
<p>Ciao <strong>{{nome_intestatario}}</strong>,</p>

<p>ti ricordiamo che il tuo corso <strong>{{nome_corso}}</strong> si concluderà il <strong>{{data_fine_corso}}</strong>.</p>

<p>Se desideri rinnovare l'iscrizione o hai domande, contattaci pure: saremo felici di aiutarti.</p>

<p>A presto,<br>Lo staff di <strong>{{nome_scuola}}</strong></p>
HTML,
            ],

            // ─── COMUNICAZIONE GENERICA ───────────────────────────────────────
            [
                'slug'     => 'student_communication',
                'name'     => 'Comunicazione agli studenti',
                'category' => 'Comunicazioni',
                'subject'  => '{{oggetto}}',
                'trigger_event' => null,
                'available_variables' => [
                    ['key' => 'nome',      'description' => 'Nome studente'],
                    ['key' => 'oggetto',   'description' => 'Oggetto del messaggio'],
                    ['key' => 'contenuto', 'description' => 'Corpo del messaggio'],
                ],
                'body_html' => <<<'HTML'
<p>Ciao <strong>{{nome}}</strong>,</p>

<div style="font-size:15px; line-height:1.7; margin:0 0 24px;">{{contenuto}}</div>
HTML,
            ],

        ];

        foreach ($templates as $tpl) {
            EmailTemplate::updateOrCreate(
                ['slug' => $tpl['slug']],
                $tpl
            );
        }
    }
}
