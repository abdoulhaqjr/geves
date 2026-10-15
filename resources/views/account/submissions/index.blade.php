<x-layouts.participant :title="__('Communications')" :subtitle="__('Vos communications & dépôt d’un résumé')">
<div>
    {{-- Bandeau : échéance / appel à communications --}}
    @php
        $now = now();
        $deadlinePassed = $deadline && $deadline->isPast();
    @endphp
    @if ($callOpen && $deadline && ! $deadlinePassed)
        <div class="ukc-alert ukc-alert--info">
            <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
            <div>
                {!! __('L’appel à communications est <strong>ouvert</strong>. Date limite de soumission :') !!}
                <strong>{{ $deadline->translatedFormat('j F Y') }}</strong>. {{ __('Utilisez le formulaire guidé pour déposer un résumé.') }}
            </div>
        </div>
    @elseif ($deadline)
        <div class="ukc-alert ukc-alert--warn">
            <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
            <div>
                {{ __('La date limite de soumission était le') }} <strong>{{ $deadline->translatedFormat('j F Y') }}</strong>.
                {{ __('Consultez le statut de vos dépôts existants ci-dessous.') }}
            </div>
        </div>
    @endif

    {{-- Mes communications --}}
    <div class="ukc-card" style="margin-bottom:20px;">
        <h3 style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
            {{ __('Mes communications') }}
            @if ($submitEdition && $submitEdition->submissionsAreOpen() && $submitRemaining > 0)
                <a href="{{ route('account.submissions.create', $submitEdition->slug) }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                    <x-ukc.icon name="plus" :size="16" /> {{ __('Nouvelle soumission') }}
                </a>
            @endif
        </h3>

        @if ($submissions->count())
            <div class="ukc-table-wrap">
                <table class="ukc-table">
                    <thead>
                        <tr><th>{{ __('Titre') }}</th><th>{{ __('Type') }}</th><th>{{ __('Atelier') }}</th><th>{{ __('Statut') }}</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($submissions as $s)
                            @php
                                $words = preg_match_all('/\p{L}+/u', strip_tags((string) $s->abstract));
                                $metaBits = [(string) $s->ref_code];
                                if ($s->created_at) { $metaBits[] = __('Déposée le').' '.$s->created_at->format('d/m/Y'); }
                                if ($words) { $metaBits[] = $words.' '.__('mots'); }
                            @endphp
                            <tr>
                                <td>
                                    <div class="title"><a href="{{ route('account.submissions.show', $s) }}" style="color:var(--ukc-blue);font-weight:600;">{{ $s->title }}</a></div>
                                    <div class="meta" style="font-size:12px;color:var(--ukc-muted);">
                                        {{ implode(' - ', $metaBits) }}
                                        @if ($s->status === 'revision') - <span style="color:#b45309;font-weight:700;">{{ __('Correction demandée') }}</span>@endif
                                    </div>
                                </td>
                                <td>{{ $s->type === 'poster' ? __('Poster') : __('Orale') }}</td>
                                <td>{{ $s->track ?: '-' }}</td>
                                <td><span class="ukc-pill ukc-pill--{{ $s->statusPill() }}">{{ $s->statusLabel() }}</span></td>
                                <td style="text-align:right;white-space:nowrap;">
                                    <a href="{{ route('account.submissions.show', $s) }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">{{ __('Voir') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p style="font-size:.9rem;color:var(--ukc-muted);margin:8px 0 0;">{{ __('Vous n’avez pas encore soumis de communication.') }}</p>
        @endif
    </div>

    {{-- Déposer un nouveau résumé --}}
    <div class="ukc-card">
        <h3>{{ __('Déposer un nouveau résumé') }}</h3>
        <p style="font-size:13.5px;color:var(--ukc-muted);margin-bottom:16px;">
            {{ __('Formulaire guidé : informations générales, auteurs, résumé structuré (≤ 250 mots), fichier PDF et validation.') }}
            {{ __('Consignes : français ou anglais - objectifs / méthodes / résultats - 3 à 5 mots-clés.') }}
        </p>
        <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
            @if ($submitEdition && $submitEdition->submissionsAreOpen() && $submitRemaining > 0)
                <a href="{{ route('account.submissions.create', $submitEdition->slug) }}" class="ukc-btn ukc-btn--blue">
                    <x-ukc.icon name="upload" :size="16" /> {{ __('Ouvrir le formulaire de soumission') }}
                </a>
            @elseif ($submitEdition && $submitEdition->submissionsAreOpen() && $submitRemaining <= 0)
                {{-- Contrôle en amont : quota atteint pour le colloque sélectionné. --}}
                <span class="ukc-pill ukc-pill--muted">{{ __('Nombre maximum de communications atteint pour ce colloque.') }}</span>
            @else
                <span class="ukc-pill ukc-pill--muted">{{ __('Appel à communications fermé') }}</span>
            @endif
            @if ($submissions->isNotEmpty())
                <a href="{{ route('account.submissions.show', $submissions->first()) }}" class="ukc-btn ukc-btn--ghost">
                    {{ __('Suivi de ma dernière soumission') }}
                </a>
            @endif
        </div>
    </div>
</div>
</x-layouts.participant>
