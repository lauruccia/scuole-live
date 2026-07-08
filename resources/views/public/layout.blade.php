<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'A&A Language Center — Scuola di Lingue a Roma San Paolo')</title>

    {{-- ════════════════════════════════════════════
         SEO META — Indicizzazione & Crawling
    ════════════════════════════════════════════ --}}
    <meta name="description" content="@yield('description', 'A&A Language Center: scuola di lingue a Roma San Paolo dal 2002. Corsi di inglese, spagnolo, francese, tedesco, arabo, italiano per stranieri. Docenti qualificati madrelingua e/o bilingue, sede esami Trinity College London n° 8241.')">
    <meta name="keywords" content="@yield('keywords', 'scuola di lingue Roma, corsi di lingue Roma, corsi di inglese Roma, scuola di inglese Roma San Paolo, preparazione Trinity Roma, preparazione IELTS Roma, preparazione Cambridge Roma, italiano per stranieri Roma, corsi aziendali lingue Roma, docenti madrelingua e bilingue Roma, certificazioni internazionali lingue Roma, A&A Language Center')">
    <meta name="author" content="A&A Language Center">
    <meta name="robots" content="@yield('robots', 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1')">
    <meta name="googlebot" content="index, follow">
    <meta name="bingbot" content="index, follow">
    <meta name="rating" content="general">
    <meta name="revisit-after" content="7 days">
    <link rel="canonical" href="@yield('canonical', url()->current())">

    {{-- Geo / Local SEO --}}
    <meta name="geo.region" content="IT-RM">
    <meta name="geo.placename" content="Roma, San Paolo">
    <meta name="geo.position" content="41.8575;12.4735">
    <meta name="ICBM" content="41.8575, 12.4735">

    {{-- Language alternates (sito monolingua italiano + landing Italian for foreigners) --}}
    <link rel="alternate" hreflang="it-IT" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="x-default" href="{{ url()->current() }}">

    {{-- Search Console verification (configurabile da .env) --}}
    @if(config('seo.google_site_verification'))
        <meta name="google-site-verification" content="{{ config('seo.google_site_verification') }}">
    @endif
    @if(config('seo.bing_site_verification'))
        <meta name="msvalidate.01" content="{{ config('seo.bing_site_verification') }}">
    @endif
    @if(config('seo.facebook_domain_verification'))
        <meta name="facebook-domain-verification" content="{{ config('seo.facebook_domain_verification') }}">
    @endif

    {{-- Open Graph --}}
    <meta property="og:type" content="@yield('og-type', 'website')">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="@yield('og-title', View::getSection('title') ?? config('app.name'))">
    <meta property="og:description" content="@yield('og-description', View::getSection('description') ?? 'Scuola di lingue a Roma San Paolo dal 2002.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og-image', asset('images/og-default.jpg'))">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="@yield('og-image-alt', 'A&A Language Center — Scuola di Lingue a Roma San Paolo')">
    <meta property="og:locale" content="it_IT">
    <meta property="og:locale:alternate" content="en_US">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@aealanguage">
    <meta name="twitter:title" content="@yield('og-title', View::getSection('title') ?? config('app.name'))">
    <meta name="twitter:description" content="@yield('og-description', View::getSection('description') ?? '')">
    <meta name="twitter:image" content="@yield('og-image', asset('images/og-default.jpg'))">
    <meta name="twitter:image:alt" content="@yield('og-image-alt', 'A&A Language Center — Scuola di Lingue a Roma San Paolo')">

    {{-- Favicon & Touch icons --}}
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
    <meta name="theme-color" content="#071428">

    {{-- Preconnect / DNS Prefetch --}}
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="dns-prefetch" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://images.unsplash.com" crossorigin>
    <link rel="dns-prefetch" href="https://images.unsplash.com">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&family=inter:400,500,600" rel="stylesheet"/>

    {{-- Preload immagine hero della home (LCP image) --}}
    @if(request()->routeIs('home'))
        <link rel="preload" as="image"
              href="https://images.unsplash.com/photo-1543269865-cbf427effbad?auto=format&fit=crop&w=1920&q=85"
              fetchpriority="high">
    @endif

    {{-- Google Tag Manager (head) — opzionale --}}
    @if(config('seo.gtm_id'))
    <script>
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ config('seo.gtm_id') }}');
    </script>
    @endif

    {{-- Google Analytics 4 (gtag) — opzionale --}}
    @if(config('seo.ga4_id'))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('seo.ga4_id') }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ config('seo.ga4_id') }}', { 'anonymize_ip': true });
    </script>
    @endif

    {{-- Meta Pixel — opzionale --}}
    @if(config('seo.facebook_pixel_id'))
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window,document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '{{ config('seo.facebook_pixel_id') }}');
        fbq('track', 'PageView');
    </script>
    @endif

    <style>
    /* ════════════════════════════════════════════
       RESET & TOKENS
    ════════════════════════════════════════════ */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --navy:       #071428;
        --navy-mid:   #0D2045;
        --navy-light: #152B5C;
        --blue:       #1A56DB;
        --blue-h:     #1446BE;
        --blue-l:     #EEF3FF;
        --gold:       #C9A42C;
        --gold-d:     #A8881E;
        --gold-l:     #FEF9EC;
        --white:      #FFFFFF;
        --bg:         #F5F8FC;
        --text:       #0D1B2E;
        --muted:      #4E5D72;
        --border:     #DDE4EE;
        --shadow:     0 20px 60px rgba(10,22,40,.12);
        --radius:     10px;
        --radius-lg:  18px;
    }

    html { scroll-behavior: smooth; }

    body {
        font-family: 'Inter', system-ui, sans-serif;
        color: var(--text);
        background: var(--white);
        line-height: 1.6;
        -webkit-font-smoothing: antialiased;
    }

    h1, h2, h3, h4, h5, h6 {
        font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
        line-height: 1.2;
    }

    a { text-decoration: none; color: inherit; }
    img { max-width: 100%; height: auto; }

    /* ════════════════════════════════════════════
       CONTAINER
    ════════════════════════════════════════════ */
    .c {
        width: min(1140px, calc(100% - 40px));
        margin: 0 auto;
    }

    /* ════════════════════════════════════════════
       NAVBAR
    ════════════════════════════════════════════ */
    .site-nav {
        background: var(--navy);
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 4px 28px rgba(0,0,0,.3);
    }
    .nav-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 96px;
    }

    /* Brand */
    .nav-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        flex-shrink: 0;
    }
    .nav-brand img {
        height: 128px;
        width: auto;
        filter: drop-shadow(0 3px 10px rgba(0,0,0,.35));
    }
    .nav-brand-name {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 1rem;
        font-weight: 800;
        color: #fff;
        line-height: 1.25;
    }
    .nav-brand-sub {
        font-size: .6rem;
        font-weight: 500;
        color: rgba(255,255,255,.5);
        letter-spacing: .1em;
        text-transform: uppercase;
        display: block;
        margin-top: 1px;
    }

    /* Desktop links */
    .nav-links {
        display: flex;
        align-items: center;
        gap: 1.75rem;
    }
    .nav-links a.nav-link {
        color: rgba(255,255,255,.78);
        font-size: .875rem;
        font-weight: 500;
        text-decoration: none;
        transition: color .2s;
        letter-spacing: .01em;
        white-space: nowrap;
    }
    .nav-links a.nav-link:hover,
    .nav-links a.nav-link.active { color: var(--gold); }

    /* CTA pill */
    .btn-nav-cta {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: .5rem 1.25rem;
        background: var(--gold);
        color: var(--navy) !important;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-weight: 700 !important;
        font-size: .83rem !important;
        border-radius: 50px;
        text-decoration: none;
        transition: all .2s;
        white-space: nowrap;
    }
    .btn-nav-cta:hover {
        background: var(--gold-d);
        transform: translateY(-1px);
        box-shadow: 0 8px 20px rgba(201,164,44,.35);
    }

    /* Hamburger button */
    .nav-hamburger {
        display: none;
        flex-direction: column;
        justify-content: center;
        gap: 5px;
        width: 40px;
        height: 40px;
        background: none;
        border: 1px solid rgba(255,255,255,.15);
        border-radius: 8px;
        cursor: pointer;
        padding: 8px;
        transition: background .2s, border-color .2s;
        flex-shrink: 0;
    }
    .nav-hamburger:hover {
        background: rgba(255,255,255,.08);
        border-color: rgba(255,255,255,.3);
    }
    .nav-hamburger span {
        display: block;
        width: 100%;
        height: 2px;
        background: #fff;
        border-radius: 2px;
        transition: transform .3s, opacity .3s;
    }
    .nav-hamburger.is-open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
    .nav-hamburger.is-open span:nth-child(2) { opacity: 0; transform: scaleX(0); }
    .nav-hamburger.is-open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

    /* Mobile drawer */
    .nav-mobile {
        display: none;
        flex-direction: column;
        background: var(--navy-mid);
        border-top: 1px solid rgba(255,255,255,.08);
        overflow: hidden;
        max-height: 0;
        transition: max-height .35s ease;
    }
    .nav-mobile.is-open {
        display: flex;
        max-height: 600px;
    }
    .nav-mobile a {
        color: rgba(255,255,255,.8);
        font-size: .95rem;
        font-weight: 500;
        padding: .9rem 1.25rem;
        text-decoration: none;
        border-bottom: 1px solid rgba(255,255,255,.06);
        transition: background .15s, color .15s;
        display: block;
    }
    .nav-mobile a:hover { background: rgba(255,255,255,.06); color: #fff; }
    .nav-mobile a.mobile-cta {
        margin: .75rem 1.25rem .75rem;
        border: none;
        background: var(--gold);
        color: var(--navy);
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-weight: 700;
        border-radius: 8px;
        text-align: center;
    }
    .nav-mobile a.mobile-cta:hover { background: var(--gold-d); }

    /* ════════════════════════════════════════════
       SHARED BUTTONS
    ════════════════════════════════════════════ */
    .btn-primary {
        display: inline-flex; align-items: center; gap: 8px;
        padding: .75rem 1.75rem;
        background: var(--blue); color: #fff;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-weight: 700; font-size: .9rem;
        border-radius: var(--radius);
        text-decoration: none; transition: all .2s; border: none; cursor: pointer;
    }
    .btn-primary:hover {
        background: var(--blue-h); transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(26,86,219,.3);
    }
    .btn-gold {
        display: inline-flex; align-items: center; gap: 8px;
        padding: .75rem 1.75rem;
        background: var(--gold); color: var(--navy);
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-weight: 700; font-size: .9rem;
        border-radius: var(--radius);
        text-decoration: none; transition: all .2s;
    }
    .btn-gold:hover {
        background: var(--gold-d); transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(201,164,44,.35);
    }
    .btn-outline-white {
        display: inline-flex; align-items: center; gap: 8px;
        padding: .75rem 1.75rem;
        background: transparent; color: #fff;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-weight: 600; font-size: .9rem;
        border-radius: var(--radius);
        text-decoration: none;
        border: 1.5px solid rgba(255,255,255,.4);
        transition: all .2s;
    }
    .btn-outline-white:hover {
        background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.7);
    }

    /* ════════════════════════════════════════════
       PAGE HERO (inner pages)
    ════════════════════════════════════════════ */
    .page-hero {
        background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 55%, var(--navy-light) 100%);
        color: #fff;
        padding: 64px 0 52px;
        position: relative;
        overflow: hidden;
    }
    .page-hero::before {
        content: '';
        position: absolute;
        bottom: -60px; right: -60px;
        width: 320px; height: 320px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(201,164,44,.15) 0%, transparent 70%);
        pointer-events: none;
    }
    .page-hero::after {
        content: '';
        position: absolute;
        top: -40px; left: -40px;
        width: 200px; height: 200px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(26,86,219,.1) 0%, transparent 70%);
        pointer-events: none;
    }
    .page-hero-inner { position: relative; z-index: 1; }
    .breadcrumb {
        font-size: .78rem;
        color: rgba(255,255,255,.5);
        margin-bottom: 14px;
        display: flex; align-items: center; gap: 6px;
    }
    .breadcrumb a { color: rgba(255,255,255,.6); transition: color .2s; }
    .breadcrumb a:hover { color: var(--gold); }
    .breadcrumb .sep { color: rgba(255,255,255,.3); }
    .page-hero h1 {
        font-size: clamp(2rem, 4vw, 2.8rem);
        font-weight: 800;
        letter-spacing: -.04em;
        margin-bottom: 10px;
    }
    .page-hero h1 em { font-style: normal; color: var(--gold); }
    .page-hero .subtitle {
        font-size: 1rem;
        color: rgba(255,255,255,.72);
        max-width: 560px;
    }

    /* ════════════════════════════════════════════
       SECTION PRIMITIVES
    ════════════════════════════════════════════ */
    .sec  { padding: 80px 0; }
    .sec-sm { padding: 52px 0; }
    .sec-bg  { background: var(--bg); }
    .sec-dark { background: var(--navy); color: #fff; }
    .sec-dark-mid { background: var(--navy-mid); color: #fff; }

    .section-label {
        display: inline-flex; align-items: center; gap: 8px;
        font-size: .72rem; font-weight: 700;
        letter-spacing: .12em; text-transform: uppercase;
        color: var(--gold); margin-bottom: 12px;
    }
    .section-label::before {
        content: ''; width: 20px; height: 2px;
        background: var(--gold); border-radius: 2px;
    }
    .section-label.white { color: rgba(255,255,255,.7); }
    .section-label.white::before { background: rgba(255,255,255,.5); }

    .sec-heading {
        font-size: clamp(1.65rem, 3vw, 2.15rem);
        font-weight: 800; letter-spacing: -.04em;
        margin-bottom: 14px; color: var(--text);
    }
    .sec-heading.white { color: #fff; }
    .sec-heading em { font-style: normal; color: var(--blue); }
    .sec-heading em.gold { color: var(--gold); }

    .sec-subtext {
        font-size: .975rem; color: var(--muted);
        max-width: 560px; line-height: 1.75;
    }
    .sec-subtext.white { color: rgba(255,255,255,.7); }

    .sec-header { margin-bottom: 40px; }
    .sec-header.center { text-align: center; }
    .sec-header.center .sec-subtext { margin: 0 auto; }

    /* ════════════════════════════════════════════
       CTA BAND (shared)
    ════════════════════════════════════════════ */
    .cta-band {
        background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 60%, #1A3A8F 100%);
        color: #fff; padding: 80px 0;
        text-align: center; position: relative; overflow: hidden;
    }
    .cta-band::before {
        content: '';
        position: absolute; inset: 0;
        background: url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1500&q=40') center/cover;
        opacity: .05;
    }
    .cta-band-inner { position: relative; z-index: 1; }
    .cta-band .label {
        color: var(--gold); font-weight: 700; font-size: .72rem;
        letter-spacing: .12em; text-transform: uppercase; margin-bottom: 14px;
        display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .cta-band .label::before, .cta-band .label::after {
        content: ''; flex: 0 0 24px; height: 1px; background: var(--gold); opacity: .6;
    }
    .cta-band h2 {
        font-size: clamp(1.75rem, 3vw, 2.5rem);
        font-weight: 800; letter-spacing: -.04em; margin-bottom: 14px;
    }
    .cta-band p {
        font-size: 1rem; color: rgba(255,255,255,.72);
        max-width: 480px; margin: 0 auto 36px; line-height: 1.7;
    }
    .cta-actions { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }

    /* ════════════════════════════════════════════
       FOOTER
    ════════════════════════════════════════════ */
    .site-footer {
        background: var(--navy);
        color: rgba(255,255,255,.6);
        padding-top: 64px;
    }
    .footer-grid {
        display: grid;
        grid-template-columns: 1.5fr 1fr 1fr 1.1fr;
        gap: 48px;
        padding-bottom: 56px;
        border-bottom: 1px solid rgba(255,255,255,.1);
    }
    .footer-brand-logo img { height: 48px; width: auto; margin-bottom: 18px; display: block; }
    .footer-brand-name {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 1.05rem; font-weight: 800; color: #fff; margin-bottom: 18px;
    }
    .footer-desc {
        font-size: .85rem; line-height: 1.75; max-width: 240px;
        color: rgba(255,255,255,.55);
    }
    .footer-certs {
        margin-top: 20px;
        display: flex; flex-wrap: wrap; gap: 6px;
    }
    .footer-cert-tag {
        font-size: .68rem; font-weight: 600; color: rgba(255,255,255,.5);
        border: 1px solid rgba(255,255,255,.12); border-radius: 50px;
        padding: 3px 10px;
    }
    .footer-social { display: flex; gap: 8px; margin-top: 20px; }
    .footer-social a {
        width: 34px; height: 34px; border-radius: 8px;
        border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        font-size: .8rem; color: rgba(255,255,255,.6);
        transition: all .2s; text-decoration: none;
    }
    .footer-social a:hover {
        background: rgba(255,255,255,.08); color: var(--gold);
        border-color: rgba(201,164,44,.4);
    }
    .footer-col h4 {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: .8rem; font-weight: 700; color: #fff;
        margin-bottom: 18px; letter-spacing: .04em;
        text-transform: uppercase;
    }
    .footer-col ul { list-style: none; }
    .footer-col ul li { margin-bottom: 10px; }
    .footer-col ul li a {
        font-size: .85rem; color: rgba(255,255,255,.55);
        text-decoration: none; transition: color .2s;
    }
    .footer-col ul li a:hover { color: var(--gold); }
    .footer-contact-item {
        display: flex; gap: 10px; align-items: flex-start; margin-bottom: 14px;
    }
    .footer-contact-item .fci-icon {
        color: var(--gold); font-size: .9rem; flex-shrink: 0; margin-top: 1px;
    }
    .footer-contact-item a {
        font-size: .85rem; color: rgba(255,255,255,.55); text-decoration: none; transition: color .2s;
    }
    .footer-contact-item a:hover { color: #fff; }
    .footer-contact-item p { font-size: .85rem; color: rgba(255,255,255,.55); line-height: 1.55; }

    .footer-bottom {
        padding: 22px 0;
        display: flex; align-items: center;
        justify-content: space-between; flex-wrap: wrap; gap: 12px;
    }
    .footer-bottom-left { font-size: .78rem; color: rgba(255,255,255,.35); }
    .footer-bottom-right { display: flex; gap: 20px; flex-wrap: wrap; }
    .footer-bottom-right a {
        font-size: .78rem; color: rgba(255,255,255,.35);
        text-decoration: none; transition: color .2s;
    }
    .footer-bottom-right a:hover { color: rgba(255,255,255,.7); }

    /* ════════════════════════════════════════════
       RESPONSIVE NAV
    ════════════════════════════════════════════ */
    @media (max-width: 960px) {
        .nav-links { display: none; }
        .nav-hamburger { display: flex; }
        .nav-inner { height: 80px; }
        .nav-brand img { height: 100px; }
    }
    @media (max-width: 768px) {
        .footer-grid { grid-template-columns: 1fr 1fr; gap: 28px; }
    }
    @media (max-width: 480px) {
        .footer-grid { grid-template-columns: 1fr; gap: 24px; }
        .footer-bottom { flex-direction: column; text-align: center; }
        .footer-desc { max-width: 100%; }
    }
    </style>

    @stack('styles')
</head>
<body>

{{-- GTM noscript fallback --}}
@if(config('seo.gtm_id'))
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ config('seo.gtm_id') }}"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endif

{{-- ════════════════════════════════════════════════════════════════════
     Schema.org JSON-LD — Organization + LanguageSchool + WebSite (SearchAction)
════════════════════════════════════════════════════════════════════ --}}
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@graph": [
        {
            "@@type": "Organization",
            "@@id": "{{ config('app.url') }}/#organization",
            "name": "{{ config('app.name') }}",
            "legalName": "A&A Language Center S.r.l.",
            "url": "{{ config('app.url') }}",
            "logo": {
                "@@type": "ImageObject",
                "url": "{{ asset('images/logo-scuola.png') }}",
                "width": 512,
                "height": 512
            },
            "vatID": "IT09121441001",
            "foundingDate": "2002",
            "sameAs": [
                "https://www.facebook.com/aealanguagecenter.roma",
                "https://www.instagram.com/aealanguagecenter/",
                "https://twitter.com/aealanguage"
            ],
            "contactPoint": [{
                "@@type": "ContactPoint",
                "telephone": "+39-06-5743734",
                "contactType": "customer service",
                "email": "info@aealanguagecenter.it",
                "areaServed": "IT",
                "availableLanguage": ["Italian","English","Spanish","French","German"]
            }]
        },
        {
            "@@type": ["LanguageSchool","EducationalOrganization","LocalBusiness"],
            "@@id": "{{ config('app.url') }}/#languageschool",
            "name": "{{ config('app.name') }}",
            "alternateName": "A&A Language Center Roma",
            "description": "Scuola di lingue a Roma San Paolo dal 2002. Corsi di inglese, spagnolo, francese, tedesco, arabo, italiano per stranieri e altre lingue con docenti qualificati madrelingua e/o bilingue. Sede ufficiale esami Trinity College London n° 8241. Corsi individuali, mini gruppi, aziendali, online e in presenza.",
            "url": "{{ config('app.url') }}",
            "image": "{{ asset('images/og-default.jpg') }}",
            "telephone": "+39-06-5743734",
            "email": "info@aealanguagecenter.it",
            "priceRange": "€€",
            "currenciesAccepted": "EUR",
            "paymentAccepted": "Cash, Credit Card, Bank Transfer, PayPal, Stripe",
            "parentOrganization": { "@@id": "{{ config('app.url') }}/#organization" },
            "address": {
                "@@type": "PostalAddress",
                "streetAddress": "Viale Leonardo da Vinci, 193",
                "addressLocality": "Roma",
                "addressRegion": "RM",
                "postalCode": "00145",
                "addressCountry": "IT"
            },
            "geo": {
                "@@type": "GeoCoordinates",
                "latitude": 41.8575,
                "longitude": 12.4735
            },
            "hasMap": "https://www.google.com/maps?q=Viale+Leonardo+da+Vinci+193+Roma",
            "areaServed": [
                { "@@type": "City", "name": "Roma" },
                { "@@type": "AdministrativeArea", "name": "Lazio" }
            ],
            "knowsLanguage": ["English","Spanish","French","German","Arabic","Russian","Italian","Portuguese","Chinese"],
            "openingHoursSpecification": [
                {
                    "@@type": "OpeningHoursSpecification",
                    "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday"],
                    "opens": "09:00",
                    "closes": "20:00"
                },
                {
                    "@@type": "OpeningHoursSpecification",
                    "dayOfWeek": "Saturday",
                    "opens": "10:00",
                    "closes": "14:00"
                }
            ],
            "hasCredential": [
                { "@@type": "EducationalOccupationalCredential", "name": "Trinity College London — Sede Esami Ufficiale n° 8241", "credentialCategory": "certification" },
                { "@@type": "EducationalOccupationalCredential", "name": "Preparazione Cambridge Assessment English", "credentialCategory": "certification" },
                { "@@type": "EducationalOccupationalCredential", "name": "Preparazione IELTS", "credentialCategory": "certification" },
                { "@@type": "EducationalOccupationalCredential", "name": "Preparazione DELE — Instituto Cervantes", "credentialCategory": "certification" },
                { "@@type": "EducationalOccupationalCredential", "name": "Preparazione DELF/DALF — France Éducation International", "credentialCategory": "certification" },
                { "@@type": "EducationalOccupationalCredential", "name": "Preparazione Goethe-Zertifikat", "credentialCategory": "certification" }
            ],
            "aggregateRating": {
                "@@type": "AggregateRating",
                "ratingValue": "4.9",
                "reviewCount": "127",
                "bestRating": "5",
                "worstRating": "1"
            }
        },
        {
            "@@type": "WebSite",
            "@@id": "{{ config('app.url') }}/#website",
            "url": "{{ config('app.url') }}",
            "name": "{{ config('app.name') }}",
            "inLanguage": "it-IT",
            "publisher": { "@@id": "{{ config('app.url') }}/#organization" }
        }
    ]
}
</script>

@hasSection('breadcrumb-jsonld')
@yield('breadcrumb-jsonld')
@endif

@hasSection('extra-jsonld')
@yield('extra-jsonld')
@endif

{{-- NAVBAR --}}
<header class="site-nav">
    <div class="c">
        <nav class="nav-inner" role="navigation" aria-label="Navigazione principale">
            <a href="{{ route('home') }}" class="nav-brand" aria-label="{{ config('app.name') }} — Homepage">
                @if(file_exists(public_path('images/logo-scuola.png')))
                    <img src="{{ asset('images/logo-scuola.png') }}" alt="{{ config('app.name') }}" loading="eager">
                @else
                    <div>
                        <div class="nav-brand-name">A&A Language Center</div>
                        <span class="nav-brand-sub">Scuola di Lingue · Roma</span>
                    </div>
                @endif
            </a>

            <div class="nav-links">
                <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">Home</a>
                <a href="{{ route('la-scuola') }}" class="nav-link {{ request()->routeIs('la-scuola') ? 'active' : '' }}">La Scuola</a>
                <a href="{{ route('checkout.catalogo') }}" class="nav-link {{ request()->routeIs('checkout.catalogo') ? 'active' : '' }}">Corsi</a>
                <a href="{{ route('per-le-aziende') }}" class="nav-link {{ request()->routeIs('per-le-aziende') ? 'active' : '' }}">Per le Aziende</a>
                <a href="{{ route('servizi') }}" class="nav-link {{ request()->routeIs('servizi') ? 'active' : '' }}">Servizi</a>
                <a href="{{ route('news.index') }}" class="nav-link {{ request()->routeIs('news.*') ? 'active' : '' }}">News</a>
                <a href="{{ route('contattaci') }}" class="nav-link {{ request()->routeIs('contattaci') ? 'active' : '' }}">Contatti</a>
                <a href="{{ route('iscrizione') }}" class="btn-nav-cta">Iscriviti ↗</a>
            </div>

            <button class="nav-hamburger" id="navToggle" aria-expanded="false" aria-controls="navMobile" aria-label="Apri menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </nav>
    </div>

    <nav class="nav-mobile" id="navMobile" aria-label="Menu mobile">
        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">🏠 Home</a>
        <a href="{{ route('la-scuola') }}" class="{{ request()->routeIs('la-scuola') ? 'active' : '' }}">La Scuola</a>
        <a href="{{ route('checkout.catalogo') }}" class="{{ request()->routeIs('checkout.catalogo') ? 'active' : '' }}">Corsi</a>
        <a href="{{ route('per-le-aziende') }}" class="{{ request()->routeIs('per-le-aziende') ? 'active' : '' }}">Per le Aziende</a>
        <a href="{{ route('servizi') }}" class="{{ request()->routeIs('servizi') ? 'active' : '' }}">Servizi</a>
        <a href="{{ route('news.index') }}" class="{{ request()->routeIs('news.*') ? 'active' : '' }}">📰 News ed Eventi</a>
        <a href="{{ route('contattaci') }}" class="{{ request()->routeIs('contattaci') ? 'active' : '' }}">Contatti</a>
        <a href="{{ route('iscrizione') }}" class="mobile-cta">✨ Iscriviti ora →</a>
    </nav>
</header>

<main id="main-content">
    @yield('content')
</main>

{{-- FOOTER --}}
<footer class="site-footer" role="contentinfo">
    <div class="c">
        <div class="footer-grid">

            {{-- Col 1: Brand --}}
            <div>
                @if(file_exists(public_path('images/logo-scuola.png')))
                    <img src="{{ asset('images/logo-scuola.png') }}" alt="{{ config('app.name') }}" class="footer-brand-logo" style="height:72px;width:auto;display:block;margin-bottom:14px;">
                @else
                    <div class="footer-brand-name">A&A Language Center</div>
                @endif
                <p class="footer-desc">Scuola di lingue a Roma San Paolo dal 2002. Corsi personalizzati con docenti qualificati madrelingua e/o bilingue. Sede ufficiale esami Trinity College London n° 8241.</p>
                <div class="footer-certs">
                    <a href="{{ route('le-certificazioni') }}" class="footer-cert-tag" title="Le certificazioni Trinity College London">Trinity College London</a>
                    <span class="footer-cert-tag">Cambridge</span>
                    <span class="footer-cert-tag">IELTS</span>
                    <span class="footer-cert-tag">Goethe</span>
                    <span class="footer-cert-tag">DELE</span>
                    <span class="footer-cert-tag">DELF/DALF</span>
                </div>
                <div class="footer-social">
                    <a href="https://www.facebook.com/aealanguagecenter.roma" target="_blank" rel="noopener" aria-label="Facebook">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                    </a>
                    <a href="https://www.instagram.com/aealanguagecenter/" target="_blank" rel="noopener" aria-label="Instagram">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="20" x="2" y="2" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
                    </a>
                    <a href="https://twitter.com/aealanguage" target="_blank" rel="noopener" aria-label="Twitter / X">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                </div>
            </div>

            {{-- Col 2: Esplora --}}
            <div class="footer-col">
                <h4>Esplora</h4>
                <ul>
                    <li><a href="{{ route('home') }}">Home</a></li>
                    <li><a href="{{ route('la-scuola') }}">La Scuola</a></li>
                    <li><a href="{{ route('checkout.catalogo') }}">Tutti i Corsi</a></li>
                    <li><a href="{{ route('landing.inglese') }}">Corsi di Inglese a Roma</a></li>
                    <li><a href="{{ route('landing.italiano-stranieri') }}">Italiano per Stranieri</a></li>
                    <li><a href="{{ route('landing.aziendali') }}">Corsi Aziendali a Roma</a></li>
                    <li><a href="{{ route('servizi') }}">Servizi</a></li>
                    <li><a href="{{ route('le-certificazioni') }}">Le Certificazioni Trinity</a></li>
                    <li><a href="{{ route('news.index') }}">News ed Eventi</a></li>
                    <li><a href="{{ route('lavora-con-noi') }}">Lavora con Noi</a></li>
                </ul>
            </div>

            {{-- Col 3: Accesso --}}
            <div class="footer-col">
                <h4>Accesso</h4>
                <ul>
                    <li><a href="{{ route('iscrizione') }}">Iscriviti / Test Gratuito</a></li>
                    <li><a href="{{ route('checkout.catalogo') }}">Acquista Online</a></li>
                    <li><a href="{{ route('contattaci') }}">Contattaci</a></li>
                    <li><a href="{{ route('privacy') }}">Privacy Policy</a></li>
                </ul>
            </div>

            {{-- Col 4: Contatti --}}
            <div class="footer-col">
                <h4>Dove siamo</h4>
                <div class="footer-contact-item">
                    <span class="fci-icon">📍</span>
                    <p>Viale Leonardo da Vinci, 193<br>00145 Roma — Quartiere San Paolo</p>
                </div>
                <div class="footer-contact-item">
                    <span class="fci-icon">📞</span>
                    <a href="tel:+39065743734">06 5743734</a>
                </div>
                <div class="footer-contact-item">
                    <span class="fci-icon">✉️</span>
                    <a href="mailto:info@aealanguagecenter.it">info@aealanguagecenter.it</a>
                </div>
                <div class="footer-contact-item">
                    <span class="fci-icon">🕐</span>
                    <p>Lun–Ven: 9:00–20:00<br>Sab: 10:00–14:00</p>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="footer-bottom-left">
                © {{ date('Y') }} {{ config('app.name') }} — P.IVA 09121441001 — Tutti i diritti riservati
            </div>
            <div class="footer-bottom-right">
                <a href="{{ route('privacy') }}">Privacy Policy</a>
                <a href="{{ route('contattaci') }}">Contatti</a>
                <a href="{{ route('iscrizione') }}">Iscriviti</a>
            </div>
        </div>
    </div>
</footer>

<script>
(function () {
    var btn  = document.getElementById('navToggle');
    var menu = document.getElementById('navMobile');
    if (!btn || !menu) return;

    btn.addEventListener('click', function () {
        var open = menu.classList.toggle('is-open');
        btn.classList.toggle('is-open', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.setAttribute('aria-label', open ? 'Chiudi menu' : 'Apri menu');
    });

    // Close on link click (mobile)
    menu.querySelectorAll('a').forEach(function (a) {
        a.addEventListener('click', function () {
            menu.classList.remove('is-open');
            btn.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
        });
    });

    // Close on outside click
    document.addEventListener('click', function (e) {
        if (!btn.contains(e.target) && !menu.contains(e.target)) {
            menu.classList.remove('is-open');
            btn.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
        }
    });
})();
</script>

{{-- ══ AVVISO SITO (es. chiusura per ferie) ═══════════════════════════════════
     Attivabile da Filament → Impostazioni scuola → "Avviso sul sito".
     Resta visibile su tutte le pagine pubbliche finché lo staff non lo disattiva.
     Il visitatore può chiuderlo: la scelta vale per la sessione del browser
     (sessionStorage) e si azzera se lo staff modifica il testo. --}}
@php
    $siteNoticeOn    = \App\Models\SchoolSetting::siteNoticeEnabled();
    $siteNoticeTitle = \App\Models\SchoolSetting::siteNoticeTitle();
    $siteNoticeText  = \App\Models\SchoolSetting::siteNoticeText();
@endphp
@if($siteNoticeOn && trim($siteNoticeText) !== '')
<div id="siteNoticeOverlay" role="dialog" aria-modal="true" aria-labelledby="siteNoticeTitle" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(7,20,40,.72); backdrop-filter:blur(3px); align-items:center; justify-content:center; padding:20px;">
    <div style="background:#fff; border-radius:18px; max-width:520px; width:100%; padding:38px 34px 32px; text-align:center; box-shadow:0 30px 80px rgba(0,0,0,.35); position:relative;">
        <button type="button" id="siteNoticeClose" aria-label="Chiudi avviso" style="position:absolute; top:14px; right:14px; width:34px; height:34px; border:none; border-radius:50%; background:#EEF3FF; color:#1A56DB; font-size:1.05rem; font-weight:700; cursor:pointer; line-height:1;">✕</button>
        <div style="font-size:2.2rem; margin-bottom:10px;">📢</div>
        <h2 id="siteNoticeTitle" style="font-family:'Plus Jakarta Sans',sans-serif; font-size:1.35rem; font-weight:800; color:#071428; margin-bottom:12px;">{{ $siteNoticeTitle }}</h2>
        <div style="font-size:.98rem; color:#4E5D72; line-height:1.65; white-space:pre-line;">{{ $siteNoticeText }}</div>
        <button type="button" id="siteNoticeOk" style="margin-top:24px; padding:11px 30px; border:none; border-radius:999px; background:#1A56DB; color:#fff; font-weight:700; font-size:.9rem; cursor:pointer;">Ho capito</button>
    </div>
</div>
<script>
(function () {
    var overlay = document.getElementById('siteNoticeOverlay');
    if (!overlay) return;
    // Chiave legata al contenuto: se lo staff cambia il testo, l'avviso riappare.
    var key = 'siteNoticeDismissed_' + @json(md5($siteNoticeTitle . '|' . $siteNoticeText));
    var dismissed = false;
    try { dismissed = sessionStorage.getItem(key) === '1'; } catch (e) {}
    if (!dismissed) { overlay.style.display = 'flex'; }
    function close() {
        overlay.style.display = 'none';
        try { sessionStorage.setItem(key, '1'); } catch (e) {}
    }
    document.getElementById('siteNoticeClose').addEventListener('click', close);
    document.getElementById('siteNoticeOk').addEventListener('click', close);
    overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
})();
</script>
@endif

@stack('scripts')
</body>
</html>
