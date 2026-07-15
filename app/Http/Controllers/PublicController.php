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

    // ─── Vacanze Studio (Summer Camp + programmi estero) ──────────────────────

    public function vacanzeStudio()
    {
        return view('public.vacanze-studio');
    }

    // ─── Insegnanti (profili pubblici) ────────────────────────────────────────

    public function insegnantiIndex()
    {
        $teachers = \App\Models\TeacherProfile::published()->get();

        return view('public.insegnanti.index', compact('teachers'));
    }

    public function insegnantiShow(string $slug)
    {
        $teacher = \App\Models\TeacherProfile::published()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('public.insegnanti.show', compact('teacher'));
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
        // try/catch: se la tabella teacher_profiles non è ancora migrata
        // (deploy codice prima della migration) la pagina non deve rompersi.
        try {
            $teachers = \App\Models\TeacherProfile::published()->get();
        } catch (\Throwable $e) {
            report($e);
            $teachers = collect();
        }

        return view('public.la-scuola', compact('teachers'));
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

    /**
     * Riceve la candidatura docente dal form /lavora-con-noi.
     *
     * Il CV viene salvato su storage/app/candidature (privato, mai esposto
     * via web) e allegato all'email inviata all'indirizzo configurato dal
     * pannello (Contenuti sito → Lavora con Noi → "Email candidature").
     *
     * Anti-spam: honeypot "website" (campo nascosto che i bot compilano) +
     * throttle 5/min sulla route. Se il honeypot è pieno rispondiamo con lo
     * stesso redirect di successo senza processare nulla, così il bot non
     * capisce di essere stato scartato.
     */
    public function lavoraConNoiStore(Request $request)
    {
        // Honeypot: gli umani non vedono (né compilano) questo campo.
        if ($request->filled('website')) {
            return redirect()->route('lavora-con-noi')->with('candidatura_ok', true);
        }

        $data = $request->validate([
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'email'          => 'required|email|max:255',
            'phone'          => 'required|string|max:50',
            'lingua'         => 'required|string|max:120',
            'laurea'         => 'nullable|string|max:255',
            'certificazioni' => 'nullable|string|max:255',
            'esperienze'     => 'nullable|string|max:3000',
            'message'        => 'nullable|string|max:3000',
            'cv'             => 'required|file|mimes:pdf,doc,docx|max:5120',
            'privacy'        => 'accepted',
        ], [
            'first_name.required' => 'Il nome è obbligatorio.',
            'last_name.required'  => 'Il cognome è obbligatorio.',
            'email.required'      => 'L\'email è obbligatoria.',
            'email.email'         => 'Inserisci un indirizzo email valido.',
            'phone.required'      => 'Il telefono è obbligatorio.',
            'lingua.required'     => 'Indica la lingua (o le lingue) che insegni.',
            'cv.required'         => 'Allega il tuo CV.',
            'cv.mimes'            => 'Il CV deve essere in formato PDF o Word (doc/docx).',
            'cv.max'              => 'Il CV non può superare i 5 MB.',
            'privacy.accepted'    => 'Devi accettare la privacy policy per inviare la candidatura.',
        ]);

        // Salva il CV su disk local (storage/app/candidature — NON pubblico).
        $cvFile     = $request->file('cv');
        $cvFilename = 'CV ' . $data['first_name'] . ' ' . $data['last_name'] . '.' . $cvFile->getClientOriginalExtension();
        $cvPath     = $cvFile->store('candidature', 'local');

        $to = \App\Models\PageContent::text('lavora-con-noi', 'cand_email', 'direzione@aealanguagecenter.it');

        try {
            Mail::to(trim($to))->queue(new \App\Mail\JobApplicationMail(
                collect($data)->except(['cv', 'privacy'])->all(),
                $cvPath,
                $cvFilename,
            ));
        } catch (\Throwable $e) {
            // Il CV è comunque salvato in storage/app/candidature: la
            // candidatura non va persa anche se l'email fallisce.
            Log::error('Email candidatura NON accodata: ' . $e->getMessage(), ['cv' => $cvPath]);
            report($e);
        }

        return redirect()->route('lavora-con-noi')->with('candidatura_ok', true);
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

    public function landingRagazzi()
    {
        return view('public.landing.ragazzi');
    }

    public function landingAdulti()
    {
        return view('public.landing.adulti');
    }

    /*
    |--------------------------------------------------------------------------
    | Test di livello online (SEO — stessi slug del vecchio sito WP)
    |--------------------------------------------------------------------------
    | Le 5 pagine "test di lingua" del vecchio sito intercettavano ricerche
    | di valore ("test di inglese online", ecc.). Qui le ricreiamo sugli
    | stessi slug: hub /test-sul-livello-di-lingua + 4 quiz per lingua.
    | Le domande vivono in config/level_tests.php; i testi sono editabili
    | dal pannello Contenuti sito (pagina "test-livello").
    */
    public function testLivello()
    {
        $tests = config('level_tests', []);

        return view('public.test.index', compact('tests'));
    }

    public function testLingua(string $lingua)
    {
        $test = config("level_tests.{$lingua}");

        abort_unless(is_array($test), 404);

        return view('public.test.quiz', compact('lingua', 'test'));
    }
}
