<x-layouts.admin title="Éditions" subtitle="Colloques & conférences · Université de Kara">
    <x-slot:actions>
        <a href="{{ route('admin.editions.create') }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">
            <x-ukc.icon name="plus" :size="16" /> Nouvelle édition
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
            <div class="ukc-stat-card"><div class="lbl">Éditions</div><div class="val blue">{{ $stats['total'] }}</div></div>
            <div class="ukc-stat-card"><div class="lbl">Édition active</div><div class="val green">{{ $stats['activeYear'] ?? '—' }}</div></div>
            <div class="ukc-stat-card"><div class="lbl">Soumissions (active)</div><div class="val">{{ $stats['activeSubmissions'] }}</div></div>
            <div class="ukc-stat-card"><div class="lbl">Participants payés</div><div class="val">{{ $stats['paidParticipants'] }}</div></div>
        </div>

        {{-- Barre d'outils : recherche + statut (GET, auto-submit) --}}
        <form method="GET" class="filter-bar" style="margin-bottom:16px;">
            <input type="search" name="search" value="{{ $search }}" placeholder="Rechercher une édition…"
                   style="min-width:220px;" onkeydown="if(event.key==='Enter'){this.form.submit();}">
            <select name="status" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                @foreach ($statusMeta as $key => $meta)
                    <option value="{{ $key }}" @selected($status === $key)>{{ $meta[0] }}</option>
                @endforeach
            </select>
            @if ($search !== '' || $status !== '')
                <a href="{{ route('admin.editions.index') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Réinitialiser</a>
            @endif
        </form>

        {{-- Cartes éditions --}}
        @forelse ($editions as $e)
            <div class="ed-card {{ $e->status === 'open' ? 'active-ed' : '' }}">
                <div class="thumb" style="{{ $thumbStyle($e) }}"></div>
                <div class="body">
                    <h3>{{ $e->title ?: $e->colloque?->name }}
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
                    <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('admin.editions.edit', $e) }}"><x-ukc.icon name="pen" :size="14" /> Éditer</a>
                    <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('admin.submissions.index') }}"><x-ukc.icon name="inbox" :size="14" /> Soumissions</a>
                    @if ($e->status !== 'open')
                        <form method="POST" action="{{ route('admin.editions.activate', $e) }}">
                            @csrf
                            <button type="submit" class="ukc-btn ukc-btn--ghost ukc-btn--sm"><x-ukc.icon name="check" :size="14" /> Activer</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.editions.destroy', $e) }}"
                          onsubmit="return confirm('Supprimer cette édition et tout son contenu ?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="ukc-btn ukc-btn--danger ukc-btn--sm"><x-ukc.icon name="trash" :size="14" /></button>
                    </form>
                </div>
            </div>
        @empty
            <div class="ukc-card"><div class="ukc-empty"><h3>Aucune édition</h3><p>Créez votre première édition de colloque.</p><a href="{{ route('admin.editions.create') }}" class="ukc-btn ukc-btn--blue">Nouvelle édition</a></div></div>
        @endforelse

        <div style="margin-top:12px;">{{ $editions->links() }}</div>
    </div>
</x-layouts.admin>
