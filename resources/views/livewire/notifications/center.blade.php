<div style="max-width:760px;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
        <div>
            <h2 style="font-size:1.4rem;font-weight:800;">Notifications</h2>
            <p style="color:var(--ukc-muted);">
                @if ($unreadCount > 0)
                    {{ $unreadCount }} non lue{{ $unreadCount > 1 ? 's' : '' }}.
                @else
                    Vous êtes à jour.
                @endif
            </p>
        </div>
        @if ($unreadCount > 0)
            <button type="button" wire:click="markAllRead" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                <x-ukc.icon name="check" :size="16" /> Tout marquer comme lu
            </button>
        @endif
    </div>

    <div class="ukc-card" style="padding:0;overflow:hidden;">
        @forelse ($notifications as $n)
            <button type="button" wire:click="open('{{ $n->id }}')" wire:key="notif-{{ $n->id }}"
                    style="display:flex;gap:12px;width:100%;text-align:left;padding:14px 16px;border:none;
                           border-bottom:1px solid var(--ukc-border);cursor:pointer;
                           background:{{ $n->read_at ? '#fff' : 'rgba(34,62,164,.05)' }};">
                <span style="flex-shrink:0;color:var(--ukc-blue);margin-top:1px;">
                    <x-ukc.icon :name="$n->data['icon'] ?? 'bell'" :size="20" />
                </span>
                <span style="min-width:0;flex:1;">
                    <span style="display:block;font-weight:700;color:var(--ukc-text);">{{ $n->data['title'] ?? 'Notification' }}</span>
                    @if (! empty($n->data['body']))
                        <span style="display:block;font-size:.88rem;color:var(--ukc-muted);margin-top:2px;">{{ $n->data['body'] }}</span>
                    @endif
                    <span style="display:block;font-size:.75rem;color:var(--ukc-light);margin-top:4px;">
                        {{ $n->created_at->diffForHumans() }}
                    </span>
                </span>
                @unless ($n->read_at)
                    <span style="flex-shrink:0;width:9px;height:9px;border-radius:50%;background:var(--ukc-blue);margin-top:6px;"
                          title="Non lue"></span>
                @endunless
            </button>
        @empty
            <div class="ukc-empty" style="margin:0;border:none;">
                <span class="ico"><x-ukc.icon name="bell" :size="28" /></span>
                <h3>Aucune notification</h3>
                <p>Vos notifications apparaîtront ici (décisions, paiements, attestations…).</p>
            </div>
        @endforelse
    </div>

    @if ($notifications instanceof \Illuminate\Contracts\Pagination\Paginator || $notifications instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div style="margin-top:16px;">{{ $notifications->links() }}</div>
    @endif
</div>
