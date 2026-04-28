<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name')) — Scuola di Lingue</title>
    <meta name="description" content="@yield('description', 'Corsi di lingue straniere per tutti i livelli. Iscriviti online.')">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet"/>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:   #b45309;
            --primary-d: #92400e;
            --primary-l: #fef3c7;
            --text:      #1c1917;
            --muted:     #78716c;
            --border:    #e7e5e4;
            --bg:        #fafaf9;
            --white:     #ffffff;
            --radius:    10px;
        }

        body {
            font-family: 'Instrument Sans', system-ui, sans-serif;
            color: var(--text);
            background: var(--bg);
            line-height: 1.6;
        }

        /* NAV */
        nav {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 64px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .nav-brand {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nav-brand img { height: 40px; }
        .nav-links { display: flex; gap: 1.5rem; align-items: center; }
        .nav-links a {
            color: var(--muted);
            text-decoration: none;
            font-size: .9rem;
            font-weight: 500;
            transition: color .2s;
        }
        .nav-links a:hover { color: var(--primary); }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: .55rem 1.25rem;
            border-radius: var(--radius);
            font-size: .9rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all .2s;
            border: none;
        }
        .btn-primary {
            background: var(--primary);
            color: #fff;
        }
        .btn-primary:hover { background: var(--primary-d); }
        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 1.5px solid var(--primary);
        }
        .btn-outline:hover { background: var(--primary-l); }

        /* CONTAINER */
        .container { max-width: 1100px; margin: 0 auto; padding: 0 1.5rem; }

        /* FOOTER */
        footer {
            background: #1c1917;
            color: #a8a29e;
            padding: 2.5rem 2rem;
            text-align: center;
            font-size: .85rem;
            margin-top: 4rem;
        }
        footer a { color: #d6d3d1; text-decoration: none; }
        footer a:hover { color: #fff; }

        @media (max-width: 640px) {
            nav { padding: 0 1rem; }
            .nav-links .hide-mobile { display: none; }
        }
    </style>
    @stack('styles')
</head>
<body>

<nav>
    <a href="{{ route('home') }}" class="nav-brand">
        @if(file_exists(public_path('images/logo-scuola.png')))
            <img src="{{ asset('images/logo-scuola.png') }}" alt="{{ config('app.name') }}">
        @else
            {{ config('app.name') }}
        @endif
    </a>
    @php
        $isHome = request()->routeIs('home');
        $corsiUrl    = $isHome ? '#corsi'    : route('home') . '#corsi';
        $contattiUrl = $isHome ? '#contatti' : route('home') . '#contatti';
    @endphp
    <div class="nav-links">
        <a href="{{ route('home') }}" class="hide-mobile">Home</a>
        <a href="{{ route('checkout.catalogo') }}" class="hide-mobile">Corsi</a>
        <a href="{{ $contattiUrl }}" class="hide-mobile">Contatti</a>
        <a href="{{ route('iscrizione') }}" class="btn btn-outline hide-mobile">Info</a>
        <a href="{{ route('checkout.catalogo') }}" class="btn btn-primary">Iscriviti</a>
    </div>
</nav>

<main>
    @yield('content')
</main>

<footer>
    <div class="container">
        <p>© {{ date('Y') }} {{ config('app.name') }} — Tutti i diritti riservati</p>
        <p style="margin-top:.5rem;">
            <a href="{{ route('iscrizione') }}">Iscrizioni</a> ·
            <a href="{{ route('home') }}#contatti">Contatti</a> ·
            <a href="{{ route('privacy') }}">Privacy Policy</a>
        </p>
    </div>
</footer>

</body>
</html>
