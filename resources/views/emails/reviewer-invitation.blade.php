<x-mail::message>
# Invitation à la relecture

Bonjour {{ $reviewer->full_name }},

Vous êtes invité(e) à relire une ou plusieurs communications soumises au colloque.
La relecture se fait **en aveugle** (l’identité des auteurs n’est pas affichée).

Accédez à votre portail de relecture — aucun compte n’est nécessaire, ce lien vous est personnel :

<x-mail::button :url="$portalUrl">
Accéder à mes relectures
</x-mail::button>

Merci pour votre contribution scientifique.

Le comité scientifique
</x-mail::message>
