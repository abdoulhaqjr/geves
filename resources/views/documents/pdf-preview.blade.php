<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} - {{ config('app.name', 'GEVES') }}</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('img/favicon-16.png') }}">
    <link rel="apple-touch-icon" href="{{ \App\Support\Branding::mark() }}">
    <style>
        @font-face { font-family: Jost; src: url('{{ asset('fonts/jost-400.woff2') }}') format('woff2'); font-weight: 400; font-display: swap; }
        @font-face { font-family: Jost; src: url('{{ asset('fonts/jost-700.woff2') }}') format('woff2'); font-weight: 700; font-display: swap; }
        :root { color-scheme: light; font-family: Jost, Arial, sans-serif; color: #172440; background: #e9eef5; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; flex-direction: column; }
        .bar { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 10px 22px; background: #fff; border-bottom: 1px solid #dce4ef; }
        .identity { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .identity img { width: 34px; height: 34px; object-fit: contain; flex: none; }
        .identity strong { display: block; color: #243da2; font-size: 16px; line-height: 1.2; }
        .identity span { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 13px; color: #566681; }
        .download { flex: none; padding: 10px 16px; border-radius: 9px; background: #243da2; color: #fff; font-size: 13px; font-weight: 700; text-decoration: none; }
        .download:hover, .download:focus-visible { background: #16894b; }
        iframe { display: block; width: 100%; flex: 1; min-height: calc(100vh - 65px); border: 0; background: #fff; }
        @media (max-width: 600px) { .bar { padding: 10px 12px; } .identity span { max-width: 48vw; } .download { padding: 9px 11px; } }
    </style>
</head>
<body>
    <header class="bar">
        <div class="identity">
            <img src="{{ \App\Support\Branding::mark() }}" alt="">
            <div><strong>{{ config('app.name', 'GEVES') }}</strong><span>{{ $title }}</span></div>
        </div>
        <a class="download" href="{{ $downloadUrl }}">Télécharger le PDF</a>
    </header>
    <iframe src="{{ $pdfUrl }}" title="{{ $title }}"></iframe>
</body>
</html>
