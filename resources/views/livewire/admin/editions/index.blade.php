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

    @if (session('ukc_status'))
        <div class="ukc-alert ukc-alert--ok"><span class="ico"><x-ukc.icon name="check-circle" :size="18" /></span><div>{{ session('ukc_status') }}</div></div>
    @endif

    {{-- Indicateurs --}}
    <div class="ukc-stats">
        <div class="ukc-stat-card"><div class="lbl">Éditions</div><div class="val blue">{{ $stats['total'] }}</div></div>
        <div class="ukc-stat-card"><div class="lbl">Édition active</div><div class="val green">{{ $stats['activeYear'] ?? '—' }}</div></div>
        <div class="ukc-stat-card"><div class="lbl">Soumissions (active)</div><div class="val">{{ $stats['activeSubmissions'] }}</div></div>
        <div class="ukc-stat-card"><div class="lbl">Participants payés</div><div class="val">{{ $stats['paidParticipants'] }}</div></div>
    </div>

    {{-- Barre d'outils --}}
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <div class="ukc-field" style="margin:0;min-width:220px;">
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Rechercher une édition…">
            </div>
            <div class="ukc-field" style="margin:0;min-width:150px;">
                <select wire:model.live="status">
                    <option value="">Tous les statuts</option>
                    @foreach ($statusMeta as $key => $meta)<option value="{{ $key }}">{{ $meta[0] }}</option>@endforeach
                </select>
            </div>
        </div>
        <a href="{{ route('admin.editions.create') }}" wire:navigate class="ukc-btn ukc-btn--blue">
            <x-ukc.icon name="plus" :size="18" /> Nouvelle édition
        </a>
    </div>

    {{-- Cartes éditions --}}
    @forelse ($editions as $e)
        <div class="ed-card {{ $e->status === 'open' ? 'active-ed' : '' }}" wire:key="ed-{{ $e->id }}">
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
                <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('admin.editions.edit', $e) }}" wire:navigate><x-ukc.icon name="pen" :size="14" /> Éditer</a>
                <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('admin.submissions.index') }}" wire:navigate><x-ukc.icon name="inbox" :size="14" /> Soumissions</a>
                @if ($e->status !== 'open')
                    <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" wire:click="activate({{ $e->id }})"><x-ukc.icon name="check" :size="14" /> Activer</button>
                @endif
                <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm" wire:click="delete({{ $e->id }})" wire:confirm="Supprimer cette édition et tout son contenu ?"><x-ukc.icon name="trash" :size="14" /></button>
            </div>
        </div>
    @empty
        <div class="ukc-card"><div class="ukc-empty"><h3>Aucune édition</h3><p>Créez votre première édition de colloque.</p><a href="{{ route('admin.editions.create') }}" wire:navigate class="ukc-btn ukc-btn--blue">Nouvelle édition</a></div></div>
    @endforelse

    <div style="margin-top:12px;">{{ $editions->links() }}</div>
</div>
