{{-- Sous-onglet « Attestations délivrées » (?tab=attestations&stab=delivrees).
     Filtres, tri et pagination restent sur cette URL. --}}
@php($base = ['edition_id' => $edition_id, 'tab' => 'attestations', 'stab' => 'delivrees'])

<div class="ukc-stats">
    <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="diploma" :size="20" /></div><div class="lbl">Délivrées</div><div class="val blue">{{ $issuedStats['total'] }}</div></div>
    <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="check-circle" :size="20" /></div><div class="lbl">Valides</div><div class="val green">{{ $issuedStats['valid'] }}</div></div>
    <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--red"><x-ukc.icon name="close" :size="20" /></div><div class="lbl">Révoquées</div><div class="val" style="color:#dc2626;">{{ $issuedStats['revoked'] }}</div></div>
    {{-- ⚠️ Délivrance papier : « ce qui reste à remettre » est LA question du
         guichet. Émettre n'est pas remettre, et sans ce compteur une attestation
         imprimée peut attendre des mois dans une chemise. --}}
    <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--slate"><x-ukc.icon name="users" :size="20" /></div><div class="lbl">Reste à remettre</div><div class="val">{{ $issuedStats['pending'] }}</div></div>
    {{-- ⚠️ **Délivrer n'est pas être reçu.** Une attestation en ligne émise,
         notifiée et jamais ouverte était indiscernable d'une attestation lue et
         partagée : c'est ce compteur qui déclenche une relance. --}}
    <div class="ukc-stat-card tw-stat" title="Attestations valides que personne n’a encore ouvertes en ligne. Les consultations du back-office ne comptent pas.">
        <div class="tw-stat__icon tw-stat__icon--amber"><x-ukc.icon name="eye-off" :size="20" /></div>
        <div class="lbl">Jamais ouvertes</div><div class="val" style="color:#b45309;">{{ $issuedStats['unopened'] }}</div>
    </div>
    {{-- L'accusé de réception : le TITULAIRE, connecté à son espace, a ouvert son
         document. Une ouverture anonyme n'en est pas un - le lien circule. --}}
    <div class="ukc-stat-card tw-stat" title="Le titulaire, connecté à son espace, a ouvert son attestation.">
        <div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="inbox" :size="20" /></div>
        <div class="lbl">Accusés de réception</div><div class="val green">{{ $issuedStats['acknowledged'] }}</div>
    </div>
</div>

<form method="GET" class="filter-bar">
    <input type="hidden" name="edition_id" value="{{ $edition_id }}">
    <input type="hidden" name="tab" value="attestations">
    <input type="hidden" name="stab" value="delivrees">
    <select name="type" onchange="this.form.submit()">
        <option value="">Tous les types</option>
        @foreach ($types as $key => $label)<option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>@endforeach
    </select>
    <select name="state" onchange="this.form.submit()">
        <option value="">Tous les états</option>
        <option value="valid" @selected($state === 'valid')>Valides</option>
        <option value="revoked" @selected($state === 'revoked')>Révoquées</option>
    </select>
    {{-- Remise en main propre : le filtre du guichet. --}}
    <select name="delivery" onchange="this.form.submit()" title="Remise en main propre (délivrance papier)">
        <option value="">Remises et non remises</option>
        <option value="pending" @selected($delivery === 'pending')>À remettre</option>
        <option value="done" @selected($delivery === 'done')>Déjà remises</option>
    </select>
    {{-- Suivi après délivrance : « qui n'a jamais ouvert la sienne ? » --}}
    <select name="trail" onchange="this.form.submit()" title="Suivi après délivrance (ouverture, accusé de réception, partage)">
        <option value="">Tout le suivi</option>
        @foreach (\App\Support\AttestationTrail::FILTRES as $cle => $libelle)
            <option value="{{ $cle }}" @selected($trail === $cle)>{{ $libelle }}</option>
        @endforeach
    </select>
    <input type="search" name="search" value="{{ $search }}" placeholder="Nom ou e-mail du bénéficiaire…" style="min-width:230px;"
           onkeydown="if(event.key==='Enter'){this.form.submit();}">
    <span class="sep" style="width:1px;height:24px;background:var(--ukc-border);align-self:center;"></span>
    <input type="date" name="from" value="{{ $from }}" title="Émise à partir du" onchange="this.form.submit()">
    <input type="date" name="to" value="{{ $to }}" title="Émise jusqu’au" onchange="this.form.submit()">
    @if ($type !== '' || $state !== '' || $delivery !== '' || $trail !== '' || $search !== '' || $from !== '' || $to !== '')
        <a href="{{ route('admin.documents.attestations', $base) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Réinitialiser</a>
    @endif
</form>

{{-- ⚠️ Les exports portent sur LES FILTRES CI-DESSUS : on imprime ce qu'on
     regarde. Sans cela, le gestionnaire imprimerait un lot qui ne correspond pas
     à sa liste, sans rien pour s'en apercevoir. --}}
@php($lots = (int) ceil(max($issued->total(), 1) / $exportLot))
<div class="ukc-toolbar" style="margin-bottom:12px;">
    <span style="font-size:13px;color:var(--ukc-muted);">
        Sortie papier de ce filtre ({{ $issued->total() }} attestation(s))
    </span>
    <span class="sep"></span>
    @if ($type === '')
        {{-- Un lot PDF n'a qu'une seule taille de page : elle vient de la mise en
             page du modèle, et mêler les types sortirait des documents rognés. --}}
        <span class="ukc-pill ukc-pill--muted" title="Choisissez un type ci-dessus : un lot PDF n’a qu’une seule taille de page.">
            Choisissez un type pour exporter le lot
        </span>
    @elseif ($issued->total() > 0)
        {{-- Lots bornés : une attestation = une page composée élément par élément,
             et un millier d’un coup épuiserait la mémoire du générateur AU MILIEU
             du rendu. Un lot borné échoue vite, ou réussit vite. --}}
        @for ($l = 1; $l <= $lots; $l++)
            <a class="ukc-btn ukc-btn--outline ukc-btn--sm"
               href="{{ route('admin.documents.attestations.lot', $exportParams + ['edition_id' => $edition_id, 'lot' => $l]) }}">
                <x-ukc.icon name="diploma" :size="14" />
                {{ $lots > 1 ? 'Lot '.$l.' · '.(($l - 1) * $exportLot + 1).'-'.min($l * $exportLot, $issued->total()) : 'Lot PDF à imprimer' }}
            </a>
        @endfor
    @endif
    <a class="ukc-btn ukc-btn--ghost ukc-btn--sm"
       href="{{ route('admin.documents.attestations.emargement', $exportParams + ['edition_id' => $edition_id]) }}">
        <x-ukc.icon name="check-circle" :size="14" /> Liste d’émargement
    </a>
</div>

<div class="ukc-card" style="padding:0;" data-ajax-root="attestations">
    <div class="ukc-table-wrap">
        <table class="ukc-table">
            <thead>
                <tr>
                    <x-ukc.th sort="recipient_name" :current="$sort" :dir="$dir">Bénéficiaire</x-ukc.th>
                    <x-ukc.th sort="type" :current="$sort" :dir="$dir">Type</x-ukc.th>
                    <x-ukc.th sort="uuid" :current="$sort" :dir="$dir">Référence</x-ukc.th>
                    <x-ukc.th sort="issued_at" :current="$sort" :dir="$dir">Émise le</x-ukc.th>
                    <x-ukc.th sort="revoked" :current="$sort" :dir="$dir">État</x-ukc.th>
                    <x-ukc.th sort="delivered_at" :current="$sort" :dir="$dir">Remise</x-ukc.th>
                    <x-ukc.th sort="last_viewed_at" :current="$sort" :dir="$dir">Suivi</x-ukc.th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($issued as $a)
                    <tr>
                        <td><strong>{{ $a->recipient_name ?: $a->participant?->full_name }}</strong><div style="font-size:12px;color:var(--ukc-muted);">{{ $a->recipient_email ?: $a->participant?->email }}</div></td>
                        <td><span class="ukc-pill ukc-pill--info">{{ $a->typeLabel() }}</span></td>
                        <td style="font-family:var(--ukc-mono);font-size:12px;">{{ \Illuminate\Support\Str::limit($a->uuid, 18) }}</td>
                        <td style="white-space:nowrap;">{{ $a->issued_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td>@if ($a->revoked)<span class="ukc-pill ukc-pill--err">Révoquée</span>@else<span class="ukc-pill ukc-pill--ok">Valide</span>@endif</td>
                        <td style="white-space:nowrap;">
                            @if ($a->delivered_at)
                                <span class="ukc-pill ukc-pill--ok" title="Remise en main propre">{{ $a->delivered_at->format('d/m/Y') }}</span>
                            @else
                                <span class="ukc-pill ukc-pill--muted">À remettre</span>
                            @endif
                        </td>
                        {{-- ⚠️ Chaque pastille dit ce qui a été CONSTATÉ, et son
                             infobulle dit quoi exactement : « LinkedIn » est un
                             départ vers le formulaire « Ajouter au profil », pas
                             une confirmation - LinkedIn ne rappelle personne.
                             Libellés et nuances : App\Support\AttestationTrail. --}}
                        <td>
                            <div style="display:flex;flex-wrap:wrap;gap:4px;max-width:220px;">
                                @foreach (\App\Support\AttestationTrail::pastilles($a) as $pastille)
                                    <span class="ukc-pill ukc-pill--{{ $pastille['tone'] }}" title="{{ $pastille['title'] }}">{{ $pastille['label'] }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td>
                            <div class="ukc-actions" style="justify-content:flex-end;">
                                <a href="{{ route('attestation.document', $a->uuid) }}" target="_blank" class="ukc-btn--icon" title="Document"><x-ukc.icon name="diploma" :size="16" /></a>
                                <a href="{{ route('attestation.document', $a->uuid) }}" target="_blank" class="ukc-btn--icon" title="Vérifier"><x-ukc.icon name="check-circle" :size="16" /></a>
                                {{-- ⚠️ Réversible, contrairement au passage d'une communication :
                                     se tromper de ligne au guichet est banal, et une remise fausse
                                     ferait croire que quelqu'un a déjà son document. --}}
                                <form method="POST" action="{{ route('admin.documents.attestations.deliver', $a) }}" data-ajax data-ajax-msg="Remise mise à jour." style="display:inline;">
                                    @csrf
                                    <button type="submit" class="ukc-btn ukc-btn--ghost ukc-btn--sm">{{ $a->delivered_at ? 'Annuler la remise' : 'Marquer remise' }}</button>
                                </form>
                                @if ($a->revoked)
                                    <form method="POST" action="{{ route('admin.documents.attestations.restore', $a) }}" data-ajax data-ajax-msg="Attestation rétablie." style="display:inline;">
                                        @csrf
                                        <button type="submit" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Rétablir</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.documents.attestations.revoke', $a) }}" data-ajax data-ajax-msg="Attestation révoquée." data-confirm="Révoquer cette attestation ?" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="ukc-btn ukc-btn--danger ukc-btn--sm">Révoquer</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="padding:28px;text-align:center;color:var(--ukc-muted);">
                        Aucune attestation délivrée pour ce filtre. Émettez-en depuis l’onglet
                        <a href="{{ route('admin.documents.attestations', ['edition_id' => $edition_id, 'tab' => 'attestations', 'stab' => 'modeles']) }}">Modèles</a>.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Compteur + pagination (les liens conservent les filtres via withQueryString). --}}
<div style="margin-top:14px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <span style="font-size:12.5px;color:var(--ukc-muted);">
        @if ($issued->total() > 0)
            {{ $issued->firstItem() }}–{{ $issued->lastItem() }} sur {{ $issued->total() }} attestation(s)
        @else
            Aucune attestation pour ce filtre.
        @endif
    </span>
    @if ($issued->hasPages())
        <div>{{ $issued->links() }}</div>
    @endif
</div>
