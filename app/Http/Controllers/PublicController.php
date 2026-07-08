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
        // Lingue con almeno un corso pubblico/attivo, ordinate per nome lingua
        $coursesByLanguage = \App\Models\Course::where('is_public', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->groupBy('language_id')
            ->filter(fn ($group, $key) => ! empty($key));

        // Ultime news/eventi pubblicati da mostrare in home.
        // try/catch: se la tabella non è ancora migrata (deploy codice prima
        // della migration) la home non deve rompersi.
        try {
            $latestNews = \App\Models\NewsPost::published()->take(3)->get();
        } catch (\Throwable $e) {
            report($e);
            $latestNews = collect();
        }

        return view('public.home', compact('coursesByLanguage', 'latestNews'));
    }

    // ─── News ed Eventi ───────────────────────────────────────────────────────

    public function newsIndex()
    {
        $posts = \App\Models\NewsPost::published()->paginate(9);

        return view('public.news.index', compact('posts'));
    }

    public function newsShow(string $slug)
    {
        $post = \App\Models\NewsPost::published()
            ->where('slug', $slug)
            ->firstOrFail();

        $related = \App\Models\NewsPost::published()
            ->where('id', '!=', $post->id)
            ->take(3)
            ->get();

        return view('public.news.show', compact('post', 'related'));
    }

    // ─── Certificazioni (Trinity College London) ─────────────────────────────

    public function leCertificazioni()
    {
        return view('public.le-certificazioni');
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

    public function laScuola()
    {
        return view('public.la-scuola');
    }

    public function perLeAziende()
    {
        return view('public.per-le-aziende');
    }

    public function servizi()
    {
        return view('public.servizi');
    }

    public function lavoraConNoi()
    {
        return view('public.lavora-con-noi');
    }

    public function contattaci()
    {
        return view('public.contattaci');
    }

    /*
    |--------------------------------------------------------------------------
    | Landing SEO — long-tail keyword per lingua/segmento
    |--------------------------------------------------------------------------
    | Pagine dedicate progettate per posizionarsi su keyword specifiche che
    | la home (troppo generica) non riesce a coprire.
    */
    public function landingInglese()
    {
        return view('public.landing.inglese');
    }

    public function landingItalianoStranieri()
    {
        return view('public.landing.italiano-stranieri');
    }

    public function landingAziendali()
    {
        return view('public.landing.aziendali');
    }
}
