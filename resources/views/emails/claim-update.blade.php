<x-mail::message>
# {{ $resolved ? 'Votre '.($claim->type === 'incident' ? 'incident a été traité' : 'réclamation a été traitée') : 'Vous avez reçu une réponse' }}

Bonjour{{ filled($claim->name) ? ' '.$claim->name : '' }},

@if ($resolved)
Votre {{ $claim->type === 'incident' ? 'incident' : 'réclamation' }} a été marqué{{ $claim->type === 'incident' ? '' : 'e' }} comme résolu{{ $claim->type === 'incident' ? '' : 'e' }}.
@else
L'équipe chargée de votre {{ $claim->type === 'incident' ? 'incident' : 'réclamation' }} vous a adressé une réponse.
@endif

- **Référence :** {{ $claim->reference }}
- **Objet :** {{ $claim->subject }}
@if ($claim->event)
- **Événement :** {{ $claim->event->title }}
@endif

@if (filled($response))
**Message de l'équipe :**

<x-mail::panel>
{{ $response }}
</x-mail::panel>
@endif

Cordialement,

L'équipe d'organisation
</x-mail::message>
