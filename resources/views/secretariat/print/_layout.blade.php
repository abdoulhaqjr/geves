<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $docTitle) - {{ $event->title ?: $event->year }}</title>
    <style>
        :root { --ink:#1e293b; --muted:#64748b; --line:#cbd5e1; --blue:#223ea4; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { font-family: "Public Sans", "Jost", system-ui, -apple-system, Segoe UI, Roboto, sans-serif; color: var(--ink); background: #eef1f6; }
        @page { size: A4; margin: 16mm; }

        .toolbar { position: sticky; top: 0; z-index: 10; display: flex; align-items: center; gap: 14px;
            padding: 10px 16px; background: #1e293b; color: #fff; }
        .toolbar strong { font-size: 14px; }
        .toolbar .sub { color: #cbd5e1; font-size: 12.5px; }
        .toolbar .sp { flex: 1; }
        .toolbar button { font: inherit; font-size: 13px; font-weight: 600; cursor: pointer; border: 0;
            border-radius: 7px; padding: 7px 14px; }
        .toolbar .primary { background: #ffd700; color: #14205a; }
        .toolbar .ghost { background: transparent; color: #fff; border: 1px solid rgba(255,255,255,.35); }

        .sheet { background: #fff; max-width: 800px; margin: 18px auto; padding: 28px 30px;
            box-shadow: 0 2px 16px rgba(15,23,42,.12); }
        .doc-head { border-bottom: 2px solid var(--blue); padding-bottom: 10px; margin-bottom: 16px; }
        .doc-head .app { font-size: 12px; letter-spacing: .4px; text-transform: uppercase; color: var(--muted); }
        .doc-head h1 { font-size: 20px; margin: 4px 0 2px; color: var(--blue); }
        .doc-head .ev { font-size: 13.5px; color: var(--ink); font-weight: 600; }
        .doc-head .meta { font-size: 12.5px; color: var(--muted); margin-top: 2px; }
        .note-line { margin: 12px 0 4px; font-size: 13.5px; }

        table.grid { width: 100%; border-collapse: collapse; font-size: 13px; }
        table.grid th, table.grid td { border: 1px solid var(--line); padding: 7px 9px; text-align: left; vertical-align: middle; }
        table.grid th { background: #f1f5f9; font-size: 11.5px; text-transform: uppercase; letter-spacing: .3px; color: var(--muted); }
        table.grid td.num { width: 34px; text-align: center; color: var(--muted); }
        table.grid td.sign { width: 180px; }
        table.grid tr { page-break-inside: avoid; }

        .empty { color: var(--muted); font-size: 13.5px; padding: 20px 0; }

        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .sheet { box-shadow: none; margin: 0; max-width: none; padding: 0; }
        }
        @stack('style')
    </style>
</head>
<body>
    <div class="toolbar">
        <strong>@yield('title', $docTitle)</strong>
        <span class="sub">{{ $event->series?->name ? $event->series->name.' - ' : '' }}{{ $event->title ?: $event->year }}</span>
        <span class="sp"></span>
        <button type="button" class="primary" onclick="window.print()">Imprimer</button>
        <button type="button" class="ghost" onclick="window.close()">Fermer</button>
    </div>

    @yield('body')
</body>
</html>
