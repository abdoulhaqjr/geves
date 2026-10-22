@php
    // Cloche de notifications (Blade + Alpine, sans Livewire). Guard-aware :
    // cible le compte participant ou l'utilisateur staff connecté.
    $notifiable = auth('account')->user() ?? auth('web')->user();
    $unreadCount = $notifiable ? $notifiable->unreadNotifications()->count() : 0;
    $recent = $notifiable ? $notifiable->notifications()->latest()->limit(8)->get() : collect();
    $allRoute = auth('account')->check() ? 'account.notifications' : 'admin.notifications';
@endphp

<div x-data="ukcNotificationBell(@js([
        'userId' => $notifiable?->getKey(),
        'unreadCount' => $unreadCount,
        'previewUrl' => route('notifications.preview'),
        'realtime' => \App\Support\Realtime::clientConfig(),
    ]))" @click.outside="open = false" style="position:relative;">
    <button type="button" @click="open = !open" class="ukc-bell" aria-label="{{ __('Notifications') }}"
            style="position:relative;background:none;border:none;cursor:pointer;color:var(--ukc-text);
                   display:inline-flex;align-items:center;padding:6px;border-radius:10px;">
        <x-ukc.icon name="bell" :size="22" />
            <span x-show="count > 0" x-cloak x-text="count > 9 ? '9+' : count"
                  style="position:absolute;top:0;right:0;min-width:17px;height:17px;padding:0 4px;border-radius:9px;
                         background:var(--ukc-blue);color:#fff;font-size:10px;font-weight:800;line-height:17px;
                         text-align:center;">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
    </button>

    <div x-show="open" x-cloak x-transition
         style="position:absolute;right:0;top:calc(100% + 8px);width:340px;max-width:90vw;z-index:50;
                background:#fff;border:1px solid var(--ukc-border);border-radius:14px;
                box-shadow:var(--ukc-shadow-lg);overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;
                    padding:12px 14px;border-bottom:1px solid var(--ukc-border);">
            <strong style="font-size:.95rem;">{{ __('Notifications') }}</strong>
                <form x-show="count > 0" x-cloak method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit"
                            style="background:none;border:none;color:var(--ukc-blue);font-size:.78rem;font-weight:700;cursor:pointer;">
                        {{ __('Tout marquer comme lu') }}
                    </button>
                </form>
        </div>

        <div x-ref="items" style="max-height:360px;overflow-y:auto;">
            @include('components.ukc.notification-bell-items', ['recent' => $recent])
        </div>

        <a href="{{ route($allRoute) }}"
           style="display:block;text-align:center;padding:11px;font-size:.83rem;font-weight:700;
                  color:var(--ukc-blue);border-top:1px solid var(--ukc-border);">
            {{ __('Voir toutes les notifications') }}
        </a>
    </div>
</div>
