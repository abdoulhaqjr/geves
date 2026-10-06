{{-- Validation du passage des communications d'une séance, par son modérateur ou
     son rapporteur - hors visio (page dédiée, comme la présence). ⚠️ Chaque constat
     ÉMET les attestations des auteurs (en délivrance en ligne) et NE SE RETIRE PAS.
     Les boutons ne s'activent qu'une fois la séance commencée. --}}
<x-layouts.participant :title="__('Validation de passage')" :subtitle="$session->label">
<div class="account-passage" style="display:grid;grid-template-columns:minmax(0,1fr);gap:16px;">
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <a href="{{ route($backRoute) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
            <x-ukc.icon name="chevron" :size="16" /> {{ __('Mes séances à modérer') }}
        </a>
        @if ($meetingUrl)
            <a href="{{ $meetingUrl }}" id="retourVisio"
               @if ($meetingTab) target="{{ $meetingTab }}" @endif
               class="ukc-btn ukc-btn--green ukc-btn--sm">
                <x-ukc.icon name="video" :size="16" /> {{ __('Retour dans la visio') }}
            </a>
        @endif
        @if ($frozen)
            <span class="ukc-pill ukc-pill--muted">{{ __('Événement archivé · lecture seule') }}</span>
        @endif
    </div>

    <div class="ukc-card">
        <div style="font-size:12px;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.04em;">
            {{ $session->event?->series?->name ?: __('Colloque') }} · {{ $session->event?->title ?: $session->event?->year }}
        </div>
        <h3 style="margin:2px 0 4px;">{{ $session->label }}</h3>
        <div style="font-size:13px;color:var(--ukc-muted);">
            @if ($session->starts_at){{ $session->starts_at->translatedFormat('l j F · H:i') }}@endif
            @if ($session->ends_at) – {{ $session->ends_at->translatedFormat('H:i') }}@endif
            @if ($session->room) · {{ $session->room }}@endif
        </div>

        {{-- Ce que « Valider » déclenche : la page doit le dire AVANT le clic. --}}
        <p style="font-size:12.5px;color:var(--ukc-muted);margin-top:12px;display:flex;align-items:flex-start;gap:6px;">
            <x-ukc.icon name="info" :size="15" />
            @if ($issuesOnPassage)
                {{ __('Valider un passage émet aussitôt les attestations de ses auteurs (en ligne). Ce constat ne se retire pas.') }}
            @else
                {{ __('Valider un passage le constate pour le secrétariat, qui émettra et imprimera les attestations. Ce constat ne se retire pas.') }}
            @endif
        </p>
    </div>

    @if (! $communications)
        <div class="ukc-card" style="text-align:center;">
            <span class="ico" style="color:var(--ukc-muted);"><x-ukc.icon name="mic" :size="28" /></span>
            <h3 style="justify-content:center;">{{ __('Aucune communication programmée') }}</h3>
            <p style="color:var(--ukc-muted);margin-top:6px;">
                {{ __('Dès qu’une communication sera programmée dans cette séance, elle apparaîtra ici pour en valider le passage.') }}
            </p>
        </div>
    @elseif ($notStarted)
        {{-- ⚠️ Un modérateur ne valide pas un passage avant l'heure de sa séance :
             la route session.passage le refuse aussi côté serveur. --}}
        <div class="ukc-card" style="text-align:center;">
            <span class="ico" style="color:var(--ukc-muted);"><x-ukc.icon name="clock" :size="28" /></span>
            <h3 style="justify-content:center;">{{ __('Validation pas encore ouverte') }}</h3>
            <p style="color:var(--ukc-muted);margin-top:6px;">
                {{ __('Vous pourrez valider les passages dès le début de la séance') }}
                @if ($session->starts_at) - {{ $session->starts_at->translatedFormat('l j F Y à H:i') }}@endif.
            </p>
        </div>
    @else
        <div class="ukc-card">
            <div style="overflow-x:auto;">
                <table class="ukc-table" style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th style="text-align:left;width:1%;">#</th>
                            <th style="text-align:left;">{{ __('Communication') }}</th>
                            <th style="text-align:left;">{{ __('Auteur') }}</th>
                            <th style="text-align:left;width:1%;white-space:nowrap;">{{ __('Passage') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($communications as $c)
                            <tr>
                                <td style="color:var(--ukc-muted);">{{ $c['order'] }}</td>
                                <td><strong>{{ $c['title'] }}</strong></td>
                                <td style="font-size:13px;color:var(--ukc-muted);">{{ $c['name'] }}</td>
                                <td style="white-space:nowrap;">
                                    @if ($c['done'])
                                        <span class="ukc-pill ukc-pill--ok">
                                            <x-ukc.icon name="check" :size="14" /> {{ __('Passage validé') }}
                                        </span>
                                    @elseif ($frozen || ! $c['slotId'])
                                        <span class="ukc-pill ukc-pill--muted">{{ __('Non validé') }}</span>
                                    @else
                                        <form method="POST" action="{{ route('session.passage', [$session, $c['slotId']]) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm"
                                                    data-confirm="{{ $issuesOnPassage
                                                        ? __('Constater le passage de cette communication ? Ses auteurs recevront aussitôt leur attestation, et ce constat ne se retire pas.')
                                                        : __('Constater le passage de cette communication ? Ce constat ne se retire pas.') }}">
                                                <x-ukc.icon name="check" :size="14" /> {{ __('Valider le passage') }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@if ($meetingUrl)
<script>
    // Revenir dans la salle SANS la recharger (même procédé que la page de présence).
    (function () {
        var lien = document.getElementById('retourVisio');
        if (!lien) return;
        lien.addEventListener('click', function (e) {
            var salle = null;
            try { salle = window.opener && !window.opener.closed ? window.opener : null; } catch (err) { salle = null; }
            if (!salle) return;
            e.preventDefault();
            try { salle.focus(); } catch (err) { /* la vérification ci-dessous rattrape */ }
            try { window.close(); } catch (err) { /* le navigateur ne ferme que ce qu'il a ouvert */ }
            setTimeout(function () {
                if (document.visibilityState === 'visible') window.location.href = lien.href;
            }, 400);
        });
    })();
</script>
@endif
</x-layouts.participant>
