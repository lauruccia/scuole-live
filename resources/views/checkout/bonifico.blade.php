@extends('public.layout')

@php
    use App\Models\SchoolSetting;
    $schoolName    = SchoolSetting::schoolName();
    $schoolIban    = SchoolSetting::bankIban() ?: 'IBAN non configurato — contatta la scuola';
    $schoolIntest  = SchoolSetting::bankIntestatario() ?: $schoolName;
@endphp

@section('title', 'Istruzioni bonifico — ' . $schoolName)

@push('styles')
<style>
    *, *::before, *::after { box-sizing: border-box; }
    body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f6faff; color: #06152f; }
    :root { --blue:#0057d9; --blue-dark:#001b3f; --blue-deep:#001126; --border:#dbe7f4; --muted:#526173; --shadow:0 18px 50px rgba(0,37,91,.16); }

    /* Navbar gestita dal layout. .c ristretto a 760px per la pagina bonifico. */
    .c { width: min(760px, calc(100% - 40px)); margin: 0 auto; }

    .page-wrap { padding: 50px 0 80px; }

    .card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 16px;
        box-shadow: var(--shadow);
        overflow: hidden;
    }
    .card-header {
        background: linear-gradient(110deg, #7c4a00, #d97706);
        color: #fff; padding: 28px 32px;
        display: flex; align-items: center; gap: 18px;
    }
    .card-header .icon { font-size: 2.4rem; }
    .card-header h1 { margin: 0; font-size: 20px; font-weight: 900; }
    .card-header p  { margin: 4px 0 0; opacity: .8; font-size: 14px; }
    .card-body { padding: 32px; }

    .iban-box {
        background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px;
        padding: 22px 26px; margin-bottom: 24px;
    }
    .iban-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 8px 0; border-bottom: 1px solid #dbeafe; font-size: 14px;
    }
    .iban-row:last-child { border: none; }
    .iban-row .lbl { color: var(--muted); font-weight: 700; }
    .iban-row .val { font-weight: 900; color: var(--blue-dark); font-family: 'Courier New', monospace; font-size: 15px; }

    .ref-box {
        background: #fffbeb; border: 2px dashed #f59e0b; border-radius: 12px;
        padding: 18px 22px; text-align: center; margin-bottom: 28px;
    }
    .ref-box p    { margin: 0 0 6px; font-size: 13px; color: #92400e; font-weight: 700; }
    .ref-box code { font-size: 22px; font-weight: 900; color: #78350f; letter-spacing: .06em; }

    .steps { list-style: none; padding: 0; margin: 0 0 28px; }
    .step-item { display: flex; gap: 14px; padding: 12px 0; border-bottom: 1px solid var(--border); font-size: 14px; color: #1e2d42; }
    .step-item:last-child { border: none; }
    .step-badge { width: 26px; height: 26px; border-radius: 50%; background: var(--blue); color: #fff; font-size: 12px; font-weight: 900; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 1px; }

    .info-note { font-size: 13px; color: var(--muted); background: #f8fafc; border-radius: 8px; padding: 12px 16px; margin-bottom: 24px; line-height: 1.6; }

    .btn-back {
        display: inline-flex; align-items: center; gap: 8px;
        background: var(--blue); color: #fff;
        padding: 12px 26px; border-radius: 9px;
        font-size: 14px; font-weight: 900; text-decoration: none;
        box-shadow: 0 8px 20px rgba(0,87,217,.25); transition: .2s;
    }
    .btn-back:hover { background: #0045b3; transform: translateY(-2px); }

    footer { background: #001126 !important; color: #fff !important; margin-top: 0 !important; }
</style>
@endpush

@section('content')
<div class="page-wrap">
    <div class="c">
        <div class="card">
            <div class="card-header">
                <div class="icon">🏦</div>
                <div>
                    <h1>Istruzioni per il bonifico</h1>
                    <p>La tua iscrizione è in attesa di conferma del pagamento</p>
                </div>
            </div>
            <div class="card-body">

                <p style="font-size:15px;margin:0 0 22px;line-height:1.6;">
                    Grazie per aver scelto <strong>{{ $schoolName }}</strong>!<br>
                    Per completare l'iscrizione al corso <strong>{{ $purchase->course->name }}</strong>,
                    esegui un bonifico con questi dati:
                </p>

                <div class="iban-box">
                    <div class="iban-row">
                        <span class="lbl">Intestatario</span>
                        <span class="val">{{ $schoolIntest }}</span>
                    </div>
                    <div class="iban-row">
                        <span class="lbl">IBAN</span>
                        <span class="val">{{ $schoolIban }}</span>
                    </div>
                    <div class="iban-row">
                        <span class="lbl">Importo</span>
                        <span class="val" style="font-size:18px;color:#0057d9;">€ {{ number_format($purchase->amount, 2, ',', '.') }}</span>
                    </div>
                </div>

                <div class="ref-box">
                    <p>⚠️ Inserisci questa causale esatta nel bonifico</p>
                    <code>{{ $purchase->bank_transfer_ref }}</code>
                </div>

                <ol class="steps">
                    <li class="step-item"><span class="step-badge">1</span>Accedi alla tua banca online o vai allo sportello.</li>
                    <li class="step-item"><span class="step-badge">2</span>Imposta l'importo esatto: <strong>€ {{ number_format($purchase->amount, 2, ',', '.') }}</strong>.</li>
                    <li class="step-item"><span class="step-badge">3</span>Inserisci la causale <strong>{{ $purchase->bank_transfer_ref }}</strong> — è il codice che ci permette di abbinare il pagamento alla tua iscrizione.</li>
                    <li class="step-item"><span class="step-badge">4</span>Riceverai una email di conferma entro 1–2 giorni lavorativi dalla ricezione del bonifico.</li>
                    <li class="step-item"><span class="step-badge">5</span>Il nostro staff ti contatterà per definire gli orari e assegnarti il docente.</li>
                </ol>

                <div class="info-note">
                    📧 Hai ricevuto queste istruzioni anche all'indirizzo <strong>{{ $purchase->billing_email }}</strong>.<br>
                    Se non la trovi, controlla la cartella spam.
                </div>

                <a href="{{ route('checkout.catalogo') }}" class="btn-back">← Torna ai corsi</a>

            </div>
        </div>
    </div>
</div>
@endsection
