<x-mail::message>
# {{ __('Invitation à la relecture') }}

{{ __('Bonjour') }} {{ $reviewer->full_name }},

{!! __('Vous êtes invité(e) à relire une ou plusieurs communications soumises au colloque. La relecture se fait **en aveugle** (l’identité des auteurs n’est pas affichée).') !!}

@if (! empty($needsPassword))
{{ __('Un espace relecteur a été créé pour vous. Définissez votre mot de passe pour y accéder :') }}

<x-mail::button :url="$portalUrl">
{{ __('Créer mon mot de passe') }}
</x-mail::button>

{{ __('Ensuite, retrouvez vos relectures dans « Mes relectures » de votre espace.') }}
@else
{{ __('Connectez-vous à votre espace pour accéder à vos relectures :') }}

<x-mail::button :url="$portalUrl">
{{ __('Accéder à mes relectures') }}
</x-mail::button>
@endif

@if (! empty($due))
{{ __('Échéance de remise :') }} **{{ $due }}**
@endif

{{ __('Depuis « Mes relectures », vous pouvez accepter cette relecture ou la refuser (un motif, même bref, aide le comité à la confier à quelqu’un d’autre).') }}

{{ __('Merci pour votre contribution scientifique.') }}

{{ __('Le comité scientifique') }}
</x-mail::message>
