<?php

namespace App\Filament\Studente\Pages;

use App\Filament\Studente\Concerns\HasStudentScope;
use App\Mail\ContractSignatureOtpMail;
use App\Models\Contract;
use App\Models\SchoolSetting;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContrattoPage extends Page
{
    use HasStudentScope;

    protected static ?string $navigationIcon  = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Contratto';
    protected static ?string $title           = 'Il mio contratto';
    protected static string  $view            = 'filament.studente.pages.contratto-page';
    protected static ?string $navigationGroup = 'Area Studente';
    protected static ?int    $navigationSort  = 33;

    public ?Contract $contract = null;

    // Stato firma: 'idle' | 'otp' | 'signed'
    public string $signPhase = 'idle';
    public string $otpInput  = '';
    public string $otpError  = '';

    public function mount(): void
    {
        $student = $this->getStudent();

        if (! $student) {
            $this->contract = null;
            return;
        }

        $this->contract = Contract::query()
            ->with(['course'])
            ->whereHas('students', fn ($q) => $q->where('students.id', $student->id))
            ->where('status', 'active')
            ->latest('id')
            ->first()
            ?? Contract::query()
                ->with(['course'])
                ->whereHas('students', fn ($q) => $q->where('students.id', $student->id))
                ->latest('id')
                ->first();

        if ($this->contract?->isSigned()) {
            $this->signPhase = 'signed';
        }
    }

    /**
     * Passo 1: genera OTP e lo invia via email.
     */
    public function requestOtp(): void
    {
        if (! $this->contract || ! SchoolSetting::isDigitalSignatureEnabled()) {
            return;
        }

        if ($this->contract->isSigned()) {
            $this->signPhase = 'signed';
            return;
        }

        $email = $this->contract->signature_email;

        if (! $email) {
            Notification::make()
                ->title('Nessuna email disponibile per l\'invio del codice.')
                ->warning()
                ->send();
            return;
        }

        try {
            $otp       = $this->contract->generateAndSaveOtp();
            $firstName = $this->contract->billing_first_name
                ?? $this->getStudent()?->first_name
                ?? 'Cliente';

            Mail::to($email)->send(new ContractSignatureOtpMail(
                otpCode:    $otp,
                firstName:  $firstName,
                contractId: $this->contract->id,
            ));

            $this->signPhase = 'otp';
            $this->otpInput  = '';
            $this->otpError  = '';

            Notification::make()
                ->title('Codice inviato a ' . $email)
                ->body('Controlla la tua casella email e inserisci il codice a 6 cifre.')
                ->success()
                ->send();

        } catch (\Throwable $e) {
            Log::error('ContrattoPage OTP send failed: ' . $e->getMessage());
            Notification::make()
                ->title('Errore nell\'invio del codice. Riprova tra qualche istante.')
                ->danger()
                ->send();
        }
    }

    /**
     * Passo 2: verifica OTP inserito dallo studente.
     */
    public function confirmSignature(): void
    {
        if (! $this->contract) {
            return;
        }

        $this->contract->refresh();

        if ($this->contract->isSigned()) {
            $this->signPhase = 'signed';
            return;
        }

        if (! $this->contract->isOtpValid()) {
            $this->otpError  = 'Il codice è scaduto o hai esaurito i tentativi. Richiedi un nuovo codice.';
            $this->signPhase = 'idle';
            return;
        }

        $code = trim($this->otpInput);

        if (strlen($code) !== 6 || ! ctype_digit($code)) {
            $this->otpError = 'Inserisci un codice valido di 6 cifre.';
            return;
        }

        $ok = $this->contract->verifyOtp($code);

        if (! $ok) {
            $remaining = max(0, 4 - (int) $this->contract->fresh()->signature_otp_attempts);
            $this->otpError = 'Codice non corretto.'
                . ($remaining > 0
                    ? " Tentativi rimasti: {$remaining}."
                    : ' Nessun tentativo rimasto. Richiedi un nuovo codice.');

            if ($remaining === 0) {
                $this->signPhase = 'idle';
            }
            return;
        }

        $this->contract->markAsSigned(
            ip:        request()->ip() ?? '',
            userAgent: request()->userAgent() ?? '',
        );

        $this->contract->refresh();
        $this->signPhase = 'signed';
        $this->otpError  = '';

        Notification::make()
            ->title('Contratto firmato con successo!')
            ->body('La tua firma digitale è stata registrata il ' . now()->format('d/m/Y \a\l\l\e H:i') . '.')
            ->success()
            ->persistent()
            ->send();
    }

    /**
     * Annulla il flusso OTP e torna allo stato iniziale.
     */
    public function cancelOtp(): void
    {
        $this->signPhase = 'idle';
        $this->otpInput  = '';
        $this->otpError  = '';
    }
}
