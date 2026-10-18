<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 26px; }
        body { font-family: DejaVu Sans, sans-serif; margin: 0; }
    </style>
</head>
<body>
{{-- ⚠️ **Le PDF et la page rendent LE MÊME document** : cette vue n'est qu'une
     enveloppe (page, marges, police). Ils ont vécu en deux fichiers jumeaux, et
     ils ont divergé - l'un portait la devise, l'autre non. `pdf: true` ne change
     que l'embarquement du logo, jamais une ligne du reçu. --}}
@include('receipts._document', ['payment' => $payment, 'pdf' => true])
</body>
</html>
