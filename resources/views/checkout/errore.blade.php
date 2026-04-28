@extends('public.layout')

@section('title', 'Pagamento non riuscito — A&A Language Center')

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

    .c { width: min(600px, calc(100% - 40px)); margin: 0 auto; }
    .page-wrap { padding: 60px 0 80px; text-align: center; }

    .err-circle {
        width: 80px; height: 80px; border-radius: 50%;
        background: linear-gradient(135deg, #f87171, #dc2626);
        color: #fff; font-size: 2.4rem;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 24px;
        box-shadow: 0 12px 30px rgba(220,38,38,.25);
    }
    h1 { font-size: 28px; font-weight: 900; color: var(--blue-dark); margin: 0 0 12px; }
    .subtitle { font-size: 15px; color: var(--muted); margin: 0 0 32px; line-height: 1.6; }

    .err-card {
        background: #fff; border: 1px solid var(--border);
        border-radius: 14px; box-shadow: var(--shadow);
        padding: 26px 30px; margin-bottom: 28px;
    }
    .contact-row { font-size: 14px; color: var(--muted); line-height: 2; }
    .contact-row a { color: var(--blue); font-weight: 700; }

    .btn-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
    .btn-retry {
        display: inline-flex; align-items: center; gap: 8px;
        background: var(--blue); color: #fff;
        padding: 12px 26px; border-radius: 9px;
        font-size: 14px; font-weight: 900; text-decoration: none;
        box-shadow: 0 8px 20px rgba(0,87,217,.25); transition: .2s;
    }
    .btn-retry:hover { background: #0045b3; transform: translateY(-2px); }
    .btn-outline {
        display: inline-flex; align-items: center; gap: 8px;
        background: #fff; color: var(--blue-dark);
        padding: 12px 26px; border-radius: 9px;
        border: 2px solid var(--border);
        font-size: 14px; font-weight: 700; text-decoration: none; transition: .2s;
    }
    .btn-outline:hover { border-color: #93c5fd; }

    footer { background: #001126 !important; color: #fff !important; margin-top: 0 !important; }
</style>
@endpush

@section('content')
<div class="page-wrap">
    <div class="c">
        <div class="err-circle">✕</div>
        <h1>Pagamento non riuscito</h1>
        <p class="subtitle">
            Il pagamento per il corso <strong>{{ $purchase->course->name }}</strong> non è andato a buon fine.<br>
            Nessun addebito è stato effettuato sul tuo conto.
        </p>

        <div class="err-card">
            <p style="font-size:14px;font-weight:700;color:var(--blue-dark);margin:0 0 12px;">Hai bisogno di aiuto?</p>
            <div class="contact-row">
                📱 WhatsApp: <a href="https://wa.me/393463836175">+39 346 3836175</a><br>
                ✉️ Email: <a href="mailto:info@aealanguagecenter.it">info@aealanguagecenter.it</a><br>
                ☎️ Telefono: <a href="tel:+390657437364">06 5743734</a>
            </div>
        </div>

        <div class="btn-actions">
            <a href="{{ route('checkout.show', $purchase->course) }}" class="btn-retry">↺ Riprova il pagamento</a>
            <a href="{{ route('checkout.catalogo') }}" class="btn-outline">← Torna ai corsi</a>
        </div>
    </div>
</div>
@endsection
