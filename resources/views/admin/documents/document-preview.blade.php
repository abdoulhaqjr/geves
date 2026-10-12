<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aperçu - {{ $title }}</title>
    @include('documents._fonts')
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef1f6; color: #1a2332; font-family: 'Jost', system-ui, -apple-system, sans-serif; }
        .bar { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 12px 18px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; position: sticky; top: 0; }
        .bar h1 { font-size: 15px; margin: 0; font-weight: 800; }
        .bar .tag { font-size: 12px; color: #5a6577; background: #eef1f6; border-radius: 999px; padding: 3px 10px; }
        .bar .spacer { flex: 1; }
        .bar button { font: inherit; font-size: 13px; border: 1px solid #cbd5e1; background: #fff; border-radius: 8px; padding: 6px 12px; cursor: pointer; }
        .wrap { display: flex; justify-content: center; padding: 32px 16px 48px; }
        .doc-shadow { box-shadow: 0 10px 40px rgba(15,32,90,.16); border-radius: 6px; overflow: hidden; background: #fff; }
        .note { max-width: 680px; margin: 0 auto 4px; text-align: center; color: #5a6577; font-size: 13px; padding: 0 16px; }
    </style>
</head>
<body>
    <div class="bar">
        <h1>Aperçu : {{ $title }}</h1>
        <span class="tag">valeurs d'exemple</span>
        <span class="spacer"></span>
        <button onclick="window.print()">Imprimer</button>
        <button onclick="window.close()">Fermer</button>
    </div>
    <p class="note" style="margin-top:20px;">Rendu réel du modèle, avec des données d'exemple. Les vraies attestations/badges reprendront ce gabarit avec les informations du bénéficiaire.</p>
    <div class="wrap">
        <div class="doc-shadow">{!! $html !!}</div>
    </div>
</body>
</html>
