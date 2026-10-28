<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    {{-- Polices lues depuis le disque (public/fonts) : dompdf n'ira chercher
         aucune feuille de styles distante, et ce PDF est joint à l'e-mail. --}}
    @include('documents._fonts', ['pdf' => true])
    <style>
        @page { margin: 0; }
        html, body { margin: 0; padding: 0; }
        * { box-sizing: border-box; }
        body { font-family: 'Lato', DejaVu Sans, sans-serif; }
    </style>
</head>
<body>
    {{-- Mise en page enregistrée dans le concepteur (type « invitation »), rendue par LayoutRenderer. --}}
    {!! $canvas !!}
</body>
</html>
