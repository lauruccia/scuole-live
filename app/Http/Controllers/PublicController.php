<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PublicController extends Controller
{
    public function home()
    {
        return view('public.home');
    }

    public function iscriviti()
    {
        return view('public.iscriviti');
    }

    public function iscrivitiStore(Request $request)
    {
        $data = $request->validate([
            'first_name'      => 'required|string|max:100',
            'last_name'       => 'required|string|max:100',
            'email'           => 'required|email|max:255',
            'phone'           => 'nullable|string|max:50',
            'course_interest' => 'nullable|string|max:255',
            'message'         => 'nullable|string|max:2000',
            'privacy'         => 'accepted',
        ], [
            'first_name.required'  => 'Il nome è obbligatorio.',
            'last_name.required'   => 'Il cognome è obbligatorio.',
            'email.required'       => 'L\'email è obbligatoria.',
            'email.email'          => 'Inserisci un indirizzo email valido.',
            'privacy.accepted'     => 'Devi accettare la privacy policy per procedere.',
        ]);

        // Controlla quali colonne esistono nel DB per compatibilità
        $hasCourseInterest = \Illuminate\Support\Facades\Schema::hasColumn('leads', 'course_interest');
        $hasInterestNotes  = \Illuminate\Support\Facades\Schema::hasColumn('leads', 'interest_notes');

        // Costruisce il payload in base alle colonne disponibili
        $payload = [
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'source'     => 'website',
            'status'     => 'new',
        ];

        if ($hasCourseInterest) {
            $payload['course_interest'] = $data['course_interest'] ?? null;
        }

        if ($hasInterestNotes) {
            // Se course_interest non esiste, lo anteponiamo alle note
            $notePrefix = (! $hasCourseInterest && ! empty($data['course_interest']))
                ? 'Corso richiesto: ' . $data['course_interest'] . "\n\n"
                : '';
            $payload['interest_notes'] = $notePrefix . ($data['message'] ?? '');
        }

        // Crea il lead nel CRM
        $lead = Lead::create($payload);

        // Registra attività nel CRM
        LeadActivity::create([
            'lead_id'     => $lead->id,
            'user_id'     => null,
            'type'        => 'note',
            'subject'     => 'Richiesta dal sito web',
            'body'        => 'Lead creato automaticamente dal form di iscrizione sul sito.',
            'occurred_at' => now(),
        ]);

        // Invia email di conferma all'iscritto
        $this->sendConfirmationEmail($lead, $data['message'] ?? null);

        return redirect()->route('iscrizione.grazie');
    }

    /**
     * Invia email di conferma della richiesta all'utente che si è iscritto.
     */
    private function sendConfirmationEmail(Lead $lead, ?string $message): void
    {
        $corso    = $lead->course_interest ?? 'Non specificato';
        $nome     = $lead->first_name . ' ' . $lead->last_name;
        $appName  = config('app.name', 'A&A Language Center');

        $html = '<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Richiesta ricevuta</title>
</head>
<body style="margin:0;padding:0;background:#f6faff;font-family:Arial,Helvetica,sans-serif;color:#18243a;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f6faff;padding:40px 20px;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(0,37,91,.10);">

      <!-- Header -->
      <tr>
        <td style="background:linear-gradient(110deg,#001126 0%,#003580 55%,#0057d9 100%);padding:36px 40px;text-align:center;">
          <p style="margin:0;color:#fff;font-size:26px;font-weight:900;letter-spacing:-0.5px;">A&amp;A Language Center</p>
          <p style="margin:6px 0 0;color:#ccdcf7;font-size:13px;">Scuola di lingue — Roma San Paolo</p>
        </td>
      </tr>

      <!-- Body -->
      <tr>
        <td style="padding:36px 40px;">
          <p style="margin:0 0 6px;font-size:18px;font-weight:800;color:#001b3f;">Ciao ' . htmlspecialchars($lead->first_name) . '! 👋</p>
          <p style="margin:0 0 24px;font-size:15px;color:#526173;line-height:1.7;">
            Abbiamo ricevuto la tua richiesta di informazioni. Il nostro staff ti contatterà entro
            <strong style="color:#001b3f;">24 ore lavorative</strong> per fissare un colloquio conoscitivo
            e trovare il percorso più adatto a te.
          </p>

          <!-- Riepilogo richiesta -->
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f6ff;border-radius:10px;margin-bottom:24px;">
            <tr><td style="padding:20px 24px;">
              <p style="margin:0 0 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#526173;">Riepilogo richiesta</p>
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:5px 0;font-size:13px;color:#526173;width:40%;">Nome</td>
                  <td style="padding:5px 0;font-size:13px;font-weight:600;color:#001b3f;">' . htmlspecialchars($nome) . '</td>
                </tr>
                <tr>
                  <td style="padding:5px 0;font-size:13px;color:#526173;">Email</td>
                  <td style="padding:5px 0;font-size:13px;font-weight:600;color:#001b3f;">' . htmlspecialchars($lead->email) . '</td>
                </tr>
                ' . ($lead->phone ? '<tr>
                  <td style="padding:5px 0;font-size:13px;color:#526173;">Telefono</td>
                  <td style="padding:5px 0;font-size:13px;font-weight:600;color:#001b3f;">' . htmlspecialchars($lead->phone) . '</td>
                </tr>' : '') . '
                <tr>
                  <td style="padding:5px 0;font-size:13px;color:#526173;">Corso richiesto</td>
                  <td style="padding:5px 0;font-size:13px;font-weight:600;color:#001b3f;">' . htmlspecialchars($corso) . '</td>
                </tr>
              </table>
            </td></tr>
          </table>

          ' . ($message ? '<p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#001b3f;">Il tuo messaggio:</p>
          <p style="margin:0 0 24px;font-size:13px;color:#526173;line-height:1.7;background:#f9fafb;border-left:3px solid #0057d9;padding:12px 16px;border-radius:0 8px 8px 0;">' . nl2br(htmlspecialchars($message)) . '</p>' : '') . '

          <p style="margin:0 0 4px;font-size:14px;color:#18243a;line-height:1.7;">
            Se nel frattempo hai domande, puoi contattarci direttamente:
          </p>
          <p style="margin:0 0 28px;font-size:14px;">
            📞 <a href="tel:+390657437364" style="color:#0057d9;font-weight:600;">06 574 3734</a>
            &nbsp;&nbsp;✉️ <a href="mailto:info@aealanguagecenter.it" style="color:#0057d9;font-weight:600;">info@aealanguagecenter.it</a>
          </p>

          <table width="100%" cellpadding="0" cellspacing="0">
            <tr><td align="center">
              <a href="' . config('app.url') . '" style="display:inline-block;background:#0057d9;color:#fff;padding:14px 36px;border-radius:8px;font-size:14px;font-weight:800;text-decoration:none;letter-spacing:.02em;">
                Visita il nostro sito →
              </a>
            </td></tr>
          </table>
        </td>
      </tr>

      <!-- Footer -->
      <tr>
        <td style="background:#f0f4ff;padding:20px 40px;text-align:center;border-top:1px solid #dbe7f4;">
          <p style="margin:0;font-size:12px;color:#7a8ca8;line-height:1.6;">
            <strong style="color:#001b3f;">A&amp;A Language Center</strong><br>
            Viale Leonardo da Vinci, 193 – 00145 Roma (RM)<br>
            Hai ricevuto questa email perché hai compilato il modulo di contatto sul nostro sito.<br>
            Per esercitare i tuoi diritti privacy: <a href="' . config('app.url') . '/privacy" style="color:#0057d9;">Privacy Policy</a>
          </p>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>';

        try {
            Mail::html($html, function ($mail) use ($lead, $appName) {
                $mail->to($lead->email, $lead->first_name . ' ' . $lead->last_name)
                     ->subject('Richiesta ricevuta — ' . $appName)
                     ->from(config('mail.from.address'), config('mail.from.name'));
            });
        } catch (\Throwable $e) {
            // Non blocchiamo il flusso se l'email fallisce — il lead è già salvato nel CRM
            \Illuminate\Support\Facades\Log::warning('Email conferma iscrizione non inviata: ' . $e->getMessage());
        }
    }

    public function grazie()
    {
        return view('public.grazie');
    }

    public function privacy()
    {
        return view('public.privacy');
    }
}
