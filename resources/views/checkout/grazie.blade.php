@extends('public.layout')

@section('title', 'Iscrizione confermata! — A&A Language Center')

@push('styles')
<style>
    *, *::before, *::after { box-sizing: border-box; }
    body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f6faff; color: #06152f; }
    :root { --blue:#0057d9; --blue-dark:#001b3f; --border:#dbe7f4; --muted:#526173; --shadow:0 18px 50px rgba(0,37,91,.16); }

    nav {
        background: linear-gradient(90deg, #001126, #061b3f) !important;
        border-bottom: none !important; height: 92px !important;
        padding: 0 max(20px, calc((100vw - 1120px) / 2)) !important;
        box-shadow: 0 8px 30px rgba(0,0,0,.18) !important;
    }
    .nav-brand { color: #fff !important; }
    .nav-brand img { height: 74px !important; }
    .nav-links a { color: rgba(255,255,255,.9) !important; font-size: 14px !important; font-weight: 700 !important; }
    .nav-links .btn-primary { background: #0069f2 !important; color: #fff !important; font-size: 14px !important; font-weight: 800 !important; padding: 18px 28px !important; border-radius: 7px !important; }
    .nav-links .btn-outline { background: transparent !important; color: rgba(255,255,255,.85) !important; border: 1.5px solid rgba(255,255,255,.35) !important; font-size: 13px !important; padding: 10px 20px !important; border-radius: 7px !important; }

    .c { width: min(680px, calc(100% - 40px)); margin: 0 auto; }
    .page-wrap { padding: 60px 0 80px; text-align: center; }

    .success-circle {
        width: 80px; height: 80px; border-radius: 50%;
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: #fff; font-size: 2.4rem;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 24px;
        box-shadow: 0 12px 30px rgba(22,163,74,.3);
    }
    h1 { font-size: 32px; font-weight: 900; color: var(--blue-dark); margin: 0 0 10px; letter-spacing: -.8px; }
    .subtitle { font-size: 16px; color: var(--muted); margin: 0 0 32px; line-height: 1.5; }

    .recap-card {
        background: #fff; border: 1px solid var(--border);
        border-radius: 14px; box-shadow: var(--shadow);
        padding: 26px 30px; margin-bottom: 28px; text-align: left;
    }
    .recap-card h3 { font-size: 13px; font-weight: 800; letter-spacing: .05em; color: var(--muted); text-transform: uppercase; margin: 0 0 16px; }
    .recap-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border); font-size: 14px; }
    .recap-row:last-child { border: none; }
    .recap-row .lbl { color: var(--muted); }
    .recap-row .val { font-weight: 800; }

    .next-card {
        background: linear-gradient(110deg, #001733, #003d94);
        color: #fff; border-radius: 14px;
        padding: 26px 30px; margin-bottom: 28px; text-align: left;
    }
    .next-card h3 { font-size: 13px; font-weight: 800; letter-spacing: .05em; opacity: .7; text-transform: uppercase; margin: 0 0 16px; }
    .next-step { display: flex; gap: 12px; align-items: flex-start; padding: 8px 0; font-size: 14px; color: #c8dcf5; }
    .next-step .dot { width: 6px; height: 6px; background: #ffd800; border-radius: 50%; margin-top: 7px; flex-shrink: 0; }

    .btn-home {
        display: inline-flex; align-items: center; gap: 8px;
        background: var(--blue); color: #fff;
        padding: 14px 32px; border-radius: 10px;
        font-size: 15px; font-weight: 900; text-decoration: none;
        box-shadow: 0 10px 25px rgba(0,87,217,.3); transition: .2s;
    }
    .btn-home:hover { background: #0045b3; transform: translateY(-2px); }

    footer { background: #001126 !important; color: #fff !important; margin-top: 0 !important; }
</style>
@endpush

@section('content')
<div class="page-wrap">
    <div class="c">
        <div class="success-circle">✓</div>
        <h1>Iscrizione confermata!</h1>
        <p class="subtitle">
            Grazie <strong>{{ $purchase->buyer_name }}</strong>! Il pagamento è stato ricevuto con successo.<br>
            Ti abbiamo inviato una conferma a <strong>{{ $purchase->billing_email }}</strong>.
        </p>

        <div class="recap-card">
            <h3>Riepilogo acquisto</h3>
            <div class="recap-row"><span class="lbl">Corso</span><span class="val">{{ $purchase->course->name }}</span></div>
            <div class="recap-row"><span class="lbl">Importo pagato</span><span class="val">€ {{ number_format($purchase->amount, 2, ',', '.') }}</span></div>
            <div class="recap-row"><span class="lbl">Metodo di pagamento</span><span class="val">{{ $purchase->payment_method_label }}</span></div>
            <div class="recap-row"><span class="lbl">Riferimento</span><span class="val">#{{ $purchase->id }}</span></div>
        </div>

        <div class="next-card">
            <h3>Cosa succede adesso</h3>
            <div class="next-step"><div class="dot"></div>Riceverai una email di conferma con il riepilogo dell'acquisto.</div>
            <div class="next-step"><div class="dot"></div>Il nostro staff ti contatterà entro 24 ore per definire orari e docente.</div>
            <div class="next-step"><div class="dot"></div>Riceverai le credenziali di accesso al portale studenti.</div>
            <div class="next-step"><div class="dot"></div>Potrai monitorare le tue lezioni, materiali e progressi direttamente online.</div>
        </div>

        <a href="{{ route('home') }}" class="btn-home">← Torna alla home</a>
    </div>
</div>
@endsection
