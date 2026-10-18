<x-mail::message>
# {{ $fromApplication ? __('Candidature acceptée') : __('Vous êtes évaluateur') }}

{{ __('Bonjour') }} {{ $reviewer->full_name }},

@if ($fromApplication)
{{ __('Votre proposition pour devenir évaluateur de l’événement « :event » a été acceptée par le gestionnaire.', ['event' => $reviewer->event?->title]) }}
@else
{{ __('Le gestionnaire vous a ajouté parmi les évaluateurs de l’événement « :event ».', ['event' => $reviewer->event?->title]) }}
@endif

{{ __('Vous faites désormais partie du vivier des évaluateurs. Une invitation distincte vous sera envoyée lorsqu’une communication vous sera attribuée.') }}

@if ($reviewer->user)
<x-mail::button :url="$portalUrl">
{{ __('Accéder à mon espace') }}
</x-mail::button>
@else
{{ __('Pour accéder à votre espace, créez un compte participant avec cette même adresse e-mail.') }}

<x-mail::button :url="$portalUrl">
{{ __('Créer mon compte') }}
</x-mail::button>
@endif

{{ __('Merci pour votre contribution scientifique.') }}

{{ __('Le comité scientifique') }}
</x-mail::message>
