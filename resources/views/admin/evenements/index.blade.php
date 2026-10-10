<x-layouts.admin title="Événements" subtitle="Colloques & conférences · Université de Kara">
    <x-slot:actions>
        <a href="{{ route('admin.evenements.create') }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">
            <x-ukc.icon name="plus" :size="16" /> Nouvel événement
        </a>
    </x-slot:actions>

    <div class="admin-page admin-editions">
        @php
            $statusMeta = [
                'draft' => ['Brouillon', 'muted'],
                'open' => ['Active', 'ok'],
                'closed' => ['Clôturée', 'warn'],
                'archived' => ['Archivée', 'muted'],
            ];
            $thumbStyle = function ($e) {
                if ($e->banner_url) {
                    return "background-image:url('".e($e->banner_url)."');";
                }
                return 'background:linear-gradient(135deg,#223ea4,#009933);';
            };
        @endphp

        {{-- Indicateurs --}}
        <div class="ukc-stats">
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="calendar" :size="20" /></div><div class="lbl">Événements</div><div class="val blue">{{ $stats['total'] }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="calendar-check" :size="20" /></div><div class="lbl">Événement active</div><div class="val green">{{ $stats['activeYear'] ?? '-' }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--violet"><x-ukc.icon name="inbox" :size="20" /></div><div class="lbl">Soumissions (active)</div><div class="val">{{ $stats['activeSubmissions'] }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--amber"><x-ukc.icon name="card" :size="20" /></div><div class="lbl">Participants payés</div><div class="val">{{ $stats['paidParticipants'] }}</div></div>
        </div>

        {{-- Barre d'outils : recherche + statut. ⚠️ Filtrage en AJAX
             (`data-filter`, cf. app.js) : la recherche s'applique à la frappe,
             sans valider ni recharger la page. --}}
        <form method="GET" class="filter-bar" style="margin-bottom:16px;" data-filter="events">
            <input type="search" name="search" value="{{ $search }}" placeholder="Rechercher un événement…"
                   style="min-width:220px;">
            <select name="status">
                <option value="">Tous les statuts</option>
                @foreach ($statusMeta as $key => $meta)
                    <option value="{{ $key }}" @selected($status === $key)>{{ $meta[0] }}</option>
                @endforeach
            </select>
            <span data-filter-part="events-reset">
                @if ($search !== '' || $status !== '')
                    <a href="{{ route('admin.evenements.index') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Réinitialiser</a>
                @endif
            </span>
        </form>

        {{-- Cartes événements --}}
        <div data-ajax-root="events" data-filter-part="events-list">
        @forelse ($events as $e)
            <div class="ed-card {{ $e->status === 'open' ? 'active-ed' : '' }}">
                <div class="thumb" style="{{ $thumbStyle($e) }}"></div>
                <div class="body">
                    <h3>{{ $e->title ?: $e->series?->name }}
                        <span class="ukc-pill ukc-pill--{{ $statusMeta[$e->status][1] ?? 'muted' }}">{{ $statusMeta[$e->status][0] ?? $e->status }}</span>
                        @if ($e->status !== 'draft')
                            <span class="ukc-pill ukc-pill--{{ $e->phase()->pill() }}" title="Phase du cycle de vie">{{ $e->phase()->label() }}</span>
                        @endif
                    </h3>
                    @if ($e->theme)<p>{{ \Illuminate\Support\Str::limit($e->theme, 90) }}</p>@endif
                    <div class="meta">
                        <span>@if ($e->date_start){{ $e->date_start->translatedFormat('d M') }} – {{ $e->date_end?->translatedFormat('d M Y') }}@else{{ $e->year }}@endif</span>
                        @if ($e->location)<span>·</span><span>{{ $e->location }}</span>@endif
                        <span>·</span><span>{{ $e->submissions_count }} soumission(s) · {{ $e->participants_count }} inscrit(s)</span>
                    </div>
                </div>
                <div class="ukc-actions">
                    <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('admin.evenements.edit', $e) }}"><x-ukc.icon name="pen" :size="14" /> Éditer</a>
                    <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('admin.evenements.export', $e) }}" title="Exporter cet événement (JSON)"><x-ukc.icon name="download" :size="14" /> Exporter</a>
                    @if ($e->status === 'open')
                        <a class="ukc-btn ukc-btn--blue ukc-btn--sm" href="{{ route('admin.submissions.index', ['event_id' => $e->id]) }}"><x-ukc.icon name="inbox" :size="14" /> Soumissions</a>
                    @else
                        <form method="POST" action="{{ route('admin.evenements.activate', $e) }}"
                              data-ajax data-ajax-target="events" data-ajax-msg="Événement activée.">
                            @csrf
                            <button type="submit" class="ukc-btn ukc-btn--ghost ukc-btn--sm"><x-ukc.icon name="check" :size="14" /> Activer</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.evenements.destroy', $e) }}"
                          data-ajax data-ajax-target="events" data-ajax-msg="Événement supprimée."
                          data-confirm="Supprimer cet événement et tout son contenu ?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="ukc-btn ukc-btn--danger ukc-btn--sm" title="Supprimer"><x-ukc.icon name="trash" :size="14" /></button>
                    </form>
                </div>
            </div>
        @empty
            <div class="ukc-card"><div class="ukc-empty"><h3>Aucun événement</h3><p>Créez votre premier événement de colloque.</p><a href="{{ route('admin.evenements.create') }}" class="ukc-btn ukc-btn--blue">Nouvel événement</a></div></div>
        @endforelse
        </div>

        <div style="margin-top:12px;" data-filter-part="events-foot">{{ $events->links() }}</div>
    </div>
</x-layouts.admin>
