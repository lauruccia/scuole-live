<?php

namespace App\Http\Controllers;

use App\Mail\LeadWelcomeMail;
use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        $payload = [
            'first_name'      => $data['first_name'],
            'last_name'       => $data['last_name'],
            'email'           => $data['email'],
            'phone'           => $data['phone'] ?? null,
            'source'          => 'website',
            'status'          => 'new',
            'course_interest' => $data['course_interest'] ?? null,
            'interest_notes'  => $data['message'] ?? '',
        ];

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
     * Invia email di conferma della richiesta all'utente che si e' iscritto.
     *
     * Tutto il branding (nome scuola, indirizzo, telefono, email pubblica)
     * viene letto da SchoolSetting nella view emails.lead-welcome.
     * Inviata via queue → non blocca la response del form.
     */
    private function sendConfirmationEmail(Lead $lead, ?string $message): void
    {
        try {
            Mail::to($lead->email, trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? '')))
                ->queue(new LeadWelcomeMail($lead, $message));
        } catch (\Throwable $e) {
            // Non blocchiamo il flusso se l'accodamento fallisce — il lead e'
            // gia' salvato nel CRM e la segreteria lo contattera' comunque.
            Log::warning('Email conferma lead NON accodata: ' . $e->getMessage(), [
                'lead_id' => $lead->id,
            ]);
            report($e);
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
