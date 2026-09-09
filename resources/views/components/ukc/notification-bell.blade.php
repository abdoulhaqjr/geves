@php
    // Cloche de notifications (Blade + Alpine, sans Livewire). Guard-aware :
    // cible le compte participant ou l'utilisateur staff connecté.
    $notifiable = auth('account')->user() ?? auth('web')->user();
    $unreadCount = $notifiable ? $notifiable->unreadNotifications()->count() : 0;
    $recent = $notifiable ? $notifiable->notifications()->latest()->limit(8)->get() : collect();
    $allRoute = auth('account')->check() ? 'account.notifications' : 'admin.notifications';
@endphp

<div x-data="{ open: false }" @click.outside="open = false" style="position:relative;">
    <button type="button" @click="open = !open" class="ukc-bell" aria-label="{{ __('Notifications') }}"
            style="position:relative;background:none;border:none;cursor:pointer;color:var(--ukc-text);
                   display:inline-flex;align-items:center;padding:6px;border-radius:10px;">
        <x-ukc.icon name="bell" :size="22" />
        @if ($unreadCount > 0)
            <span style="position:absolute;top:0;right:0;min-width:17px;height:17px;padding:0 4px;border-radius:9px;
                         background:var(--ukc-blue);color:#fff;font-size:10px;font-weight:800;line-height:17px;
                         text-align:center;">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
        @endif
    </button>

    <div x-show="open" x-cloak x-transition
         style="position:absolute;right:0;top:calc(100% + 8px);width:340px;max-width:90vw;z-index:50;
                background:#fff;border:1px solid var(--ukc-border);border-radius:14px;
                box-shadow:var(--ukc-shadow-lg);overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;
                    padding:12px 14px;border-bottom:1px solid var(--ukc-border);">
            <strong style="font-size:.95rem;">{{ __('Notifications') }}</strong>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit"
                            style="background:none;border:none;color:var(--ukc-blue);font-size:.78rem;font-weight:700;cursor:pointer;">
                        {{ __('Tout marquer comme lu') }}
                    </button>
                </form>
            @endif
        </div>

        <div style="max-height:360px;overflow-y:auto;">
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
        </div>

        <a href="{{ route($allRoute) }}"
           style="display:block;text-align:center;padding:11px;font-size:.83rem;font-weight:700;
                  color:var(--ukc-blue);border-top:1px solid var(--ukc-border);">
            {{ __('Voir toutes les notifications') }}
        </a>
    </div>
</div>
