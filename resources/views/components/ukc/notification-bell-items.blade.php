@forelse ($recent as $n)
    <form method="POST" action="{{ route('notifications.open', $n->id) }}">
        @csrf
        <button type="submit"
                style="display:flex;gap:10px;width:100%;text-align:left;padding:11px 14px;border:none;
                       border-bottom:1px solid var(--ukc-border);cursor:pointer;
                       background:{{ $n->read_at ? '#fff' : 'rgba(34,62,164,.05)' }};">
            <span style="flex-shrink:0;color:var(--ukc-blue);margin-top:1px;">
                <x-ukc.icon :name="$n->data['icon'] ?? 'bell'" :size="18" />
            </span>
            <span style="min-width:0;flex:1;">
                <span style="display:block;font-size:.85rem;font-weight:700;color:var(--ukc-text);">{{ $n->data['title'] ?? __('Notification') }}</span>
                @if (! empty($n->data['body']))
                    <span style="display:block;font-size:.8rem;color:var(--ukc-muted);margin-top:1px;
                                 overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $n->data['body'] }}</span>
                @endif
                <span style="display:block;font-size:.72rem;color:var(--ukc-light);margin-top:2px;">
                    {{ $n->created_at->diffForHumans() }}
                </span>
            </span>
            @unless ($n->read_at)
                <span style="flex-shrink:0;width:8px;height:8px;border-radius:50%;background:var(--ukc-blue);margin-top:6px;"></span>
            @endunless
        </button>
    </form>
@empty
    <div style="padding:26px 14px;text-align:center;color:var(--ukc-muted);font-size:.85rem;">
        <x-ukc.icon name="bell" :size="26" /><br>{{ __('Aucune notification.') }}
    </div>
@endforelse
