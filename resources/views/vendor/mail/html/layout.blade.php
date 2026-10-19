<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<title>{{ config('app.name') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
{{-- Police Jost (charte UK) pour les clients qui chargent les polices web ;
     repli sans-serif via le thème pour tous les autres. --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* ⚠️ SUR TÉLÉPHONE, L'E-MAIL VA BORD À BORD : la carte prend toute la largeur
   offerte, perd ses coins arrondis et son cadre gris, et son rembourrage
   intérieur se resserre. Les 24px de côté et le liseré gris se lisaient comme
   une marge autour du message.
   ⚠️ Ce qui RESTE autour n'est pas à nous : l'application Gmail pose sa propre
   marge autour de tout message, et aucun gabarit ne l'enlève. */
@media only screen and (max-width: 600px) {
.inner-body {
width: 100% !important;
border-radius: 0 !important;
box-shadow: none !important;
}

.header {
border-radius: 0 !important;
}

.wrapper,
.body {
background-color: #ffffff !important;
border-color: #ffffff !important;
}

.content-cell {
padding: 20px 16px !important;
}

.footer .content-cell {
padding: 12px 16px 16px !important;
}

.footer {
width: 100% !important;
}
}

@media only screen and (max-width: 500px) {
.button {
width: 100% !important;
}
}
</style>
{!! $head ?? '' !!}
</head>
<body>

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center">
<table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<!-- Email Body -->
<tr>
<td class="body" width="100%" cellpadding="0" cellspacing="0" style="border: hidden !important;">
<table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
{{-- ⚠️ LE BANDEAU EST DANS LA CARTE, pas au-dessus d'elle : posé dans la table
     extérieure, il s'étirait sur toute la largeur de la fenêtre pendant que le
     message restait à 570px, et l'e-mail se lisait comme deux objets empilés.
     Il fait désormais la largeur du contenu, sur grand écran comme sur
     téléphone - où carte et bandeau prennent ensemble toute la place offerte. --}}
{!! $header ?? '' !!}
<!-- Body content -->
<tr>
<td class="content-cell">
{!! Illuminate\Mail\Markdown::parse($slot) !!}

{!! $subcopy ?? '' !!}
</td>
</tr>
</table>
</td>
</tr>

{!! $footer ?? '' !!}
</table>
</td>
</tr>
</table>
</body>
</html>
