{{-- Gestes d'une attestation, rendus à deux endroits : en surimpression du
     document (au survol) et dans la colonne de gauche. Un seul fichier, pour
     que les deux ne divergent pas.

     ⚠️ En délivrance PHYSIQUE, il ne reste que la vérification : l'exemplaire qui
     fait foi est remis en main propre, et ce qui est à l'écran n'en est que le
     spécimen. Partager ou télécharger un spécimen reviendrait à faire circuler un
     second document que l'émetteur n'a jamais délivré. --}}
@if ($papier ?? false)
    @if ($variant === 'overlay')
        <button type="button" class="ukc-btn ukc-btn--green ukc-cred-action" data-cred-verify
                data-url="{{ route('attestation.verify.json', $attestation->uuid) }}">
            <x-ukc.icon name="check-circle" :size="16" /> {{ __('Vérifier l’attestation') }}
        </button>
    @endif
    <p class="ukc-cred-help">
        {{ __('Attestation délivrée sur papier.') }}
        @if ($attestation->delivered_at)
            {{ __('Remise le') }} {{ $attestation->delivered_at->format('d/m/Y') }}.
        @else
            {{ __('L’exemplaire officiel est à retirer auprès du secrétariat.') }}
        @endif
    </p>
@else
<button type="button" class="ukc-btn ukc-btn--blue ukc-cred-action" data-cred-share data-url="{{ $pageUrl }}"
        data-title="{{ $attestation->typeLabel() }} - {{ $attestation->recipient_name }}">
    <x-ukc.icon name="share" :size="16" /> {{ __('Partager l’attestation') }}
</button>

{{-- ⚠️ `data-cred-linkedin` ne constate QUE le départ vers le formulaire
     « Ajouter au profil », pré-rempli par nous. LinkedIn ne nous rappelle
     jamais : personne ici ne sait si l'ajout a été confirmé, et les libellés du
     back-office le disent (cf. AttestationTrail). --}}
<a href="{{ $linkedIn }}" target="_blank" rel="noopener" class="ukc-btn ukc-btn--outline ukc-cred-action" data-cred-linkedin>
    <x-ukc.icon name="linkedin" :size="16" /> {{ __('Ajouter au profil LinkedIn') }}
</a>

<div class="ukc-cred-row">
    <a href="{{ route('attestation.pdf', $attestation->uuid) }}" class="ukc-btn ukc-btn--outline ukc-cred-action">
        <x-ukc.icon name="download" :size="16" /> {{ __('Télécharger (PDF)') }}
    </a>
    <button type="button" class="ukc-btn ukc-btn--outline ukc-cred-icon" data-cred-copy data-url="{{ $pageUrl }}"
            title="{{ __('Copier le lien') }}" aria-label="{{ __('Copier le lien') }}">
        <x-ukc.icon name="link" :size="16" />
    </button>
</div>

{{-- En surimpression seulement : dans la colonne, la carte « Vérification »
     porte déjà ce bouton, à côté de l'état et de l'identifiant. --}}
@if ($variant === 'overlay')
    <button type="button" class="ukc-btn ukc-btn--green ukc-cred-action" data-cred-verify
            data-url="{{ route('attestation.verify.json', $attestation->uuid) }}">
        <x-ukc.icon name="check-circle" :size="16" /> {{ __('Vérifier l’attestation') }}
    </button>
@endif
@endif
