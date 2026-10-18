<x-mail::message>
# {{ __('Invitation à l’évaluation') }}

{{ __('Bonjour') }} {{ $reviewer->full_name }},

{!! __('Vous êtes invité(e) à évaluer une ou plusieurs communications soumises au colloque. L’évaluation se fait **en aveugle** (l’identité des auteurs n’est pas affichée).') !!}

@if (! empty($needsPassword))
{{ __('Un espace évaluateur a été créé pour vous. Définissez votre mot de passe pour y accéder :') }}

<x-mail::button :url="$portalUrl">
{{ __('Créer mon mot de passe') }}
</x-mail::button>

{{ __('Ensuite, retrouvez vos évaluations dans « Mes évaluations » de votre espace.') }}
@else
{{ __('Connectez-vous à votre espace pour accéder à vos évaluations :') }}

<x-mail::button :url="$portalUrl">
{{ __('Accéder à mes évaluations') }}
</x-mail::button>
@endif

@if (! empty($due))
{{ __('Échéance de remise :') }} **{{ $due }}**
@endif

{{ __('Depuis « Mes évaluations », vous pouvez accepter cette évaluation ou la refuser (un motif, même bref, aide le comité à la confier à quelqu’un d’autre).') }}

{{ __('Merci pour votre contribution scientifique.') }}

{{ __('Le comité scientifique') }}
</x-mail::message>
