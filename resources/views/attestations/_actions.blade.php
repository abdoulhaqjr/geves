{{-- Gestes d'une attestation, rendus à deux endroits : en surimpression du
     document (au survol) et dans la colonne de gauche. Un seul fichier, pour
     que les deux ne divergent pas. --}}
<button type="button" class="ukc-btn ukc-btn--blue ukc-cred-action" data-cred-share data-url="{{ $pageUrl }}"
        data-title="{{ $attestation->typeLabel() }} - {{ $attestation->recipient_name }}">
    <x-ukc.icon name="share" :size="16" /> {{ __('Partager l’attestation') }}
</button>

<a href="{{ $linkedIn }}" target="_blank" rel="noopener" class="ukc-btn ukc-btn--outline ukc-cred-action">
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
