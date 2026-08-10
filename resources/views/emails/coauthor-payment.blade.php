<x-mail::message>
# Communication acceptée 🎉

Bonjour {{ $coauthorName ?: 'cher(e) co-auteur(e)' }},

La communication **« {{ $submission->title }} »** ({{ $submission->ref_code }}), à laquelle vous êtes associé(e) en tant que co-auteur, a été **acceptée** pour **{{ $submission->edition?->title }}**.

En tant que co-auteur communiquant, vous êtes invité(e) à régler vos frais de participation, au tarif de votre grade. Le règlement vous ouvre droit à votre **attestation de communication**.

<x-mail::button :url="$paymentUrl">
Régler mes frais de co-auteur
</x-mail::button>

Sur la page, vérifiez votre grade (il détermine le montant) puis choisissez votre moyen de paiement.

Au plaisir de vous accueillir,
Le comité d’organisation
</x-mail::message>
