<x-mail::message>
# {{ __('Invitation à la relecture') }}

{{ __('Bonjour') }} {{ $reviewer->full_name }},

{!! __('Vous êtes invité(e) à relire une ou plusieurs communications soumises au colloque. La relecture se fait **en aveugle** (l’identité des auteurs n’est pas affichée).') !!}

{{ __('Connectez-vous à votre espace pour accéder à vos relectures :') }}

<x-mail::button :url="$portalUrl">
{{ __('Accéder à mes relectures') }}
</x-mail::button>

{{ __('Merci pour votre contribution scientifique.') }}

{{ __('Le comité scientifique') }}
</x-mail::message>
