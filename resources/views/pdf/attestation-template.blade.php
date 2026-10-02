<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    {{-- ⚠️ dompdf ne va pas chercher une feuille de styles Google Fonts : ce PDF
         rend donc la pile de repli (Georgia / DejaVu Sans), là où l'aperçu et
         l'impression navigateur ont Jost et Playfair. Pour une fidélité totale,
         il faudrait héberger les fichiers de police et les déclarer en
         @font-face local. L'impression depuis la page de l'attestation, elle,
         est fidèle. --}}
    <style>
        @page { margin: 0; }
        html, body { margin: 0; padding: 0; }
        * { box-sizing: border-box; }
        body { font-family: 'Jost', DejaVu Sans, sans-serif; }
    </style>
</head>
<body>
    {{-- Mise en page enregistrée dans l'éditeur (modèle du type), rendue par LayoutRenderer. --}}
    {!! $canvas !!}
</body>
</html>
