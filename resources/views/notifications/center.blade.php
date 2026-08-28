<x-dynamic-component :component="$isAccount ? 'layouts.participant' : 'layouts.admin'"
                     title="Notifications" subtitle="Alertes, décisions et rappels">
    <div style="max-width:760px;">
        @if ($isAccount)
            @include('partials.account-nav')
        @endif

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
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                        <x-ukc.icon name="check" :size="16" /> Tout marquer comme lu
                    </button>
                </form>
            @endif
        </div>

        {{-- Préférences par type / canal --}}
        <div class="ukc-card" style="margin-bottom:18px;" x-data="{ open: false }">
            <button type="button" @click="open = !open"
                    style="display:flex;align-items:center;justify-content:space-between;gap:8px;width:100%;
                           background:none;border:none;cursor:pointer;padding:0;text-align:left;">
                <span style="display:inline-flex;align-items:center;gap:8px;font-weight:700;color:var(--ukc-text);">
                    <x-ukc.icon name="settings" :size="18" /> Préférences de notification
                </span>
                <span x-show="!open"><x-ukc.icon name="chevron" :size="18" /></span>
                <span x-show="open" style="transform:rotate(90deg);"><x-ukc.icon name="chevron" :size="18" /></span>
            </button>

            <form method="POST" action="{{ route('notifications.prefs') }}" x-show="open" x-cloak style="margin-top:14px;">
                @csrf
                <div style="overflow-x:auto;">
                    <table class="ukc-table" style="min-width:420px;">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th style="text-align:center;width:90px;">In-app</th>
                                <th style="text-align:center;width:90px;">E-mail</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kinds as $kind => $def)
                                <tr>
                                    <td>{{ $def['label'] }}</td>
                                    <td style="text-align:center;">
                                        <input type="checkbox" name="prefs[{{ $kind }}][inapp]" value="1"
                                               @checked($prefs[$kind]['inapp'] ?? false) aria-label="In-app {{ $def['label'] }}">
                                    </td>
                                    <td style="text-align:center;">
                                        <input type="checkbox" name="prefs[{{ $kind }}][email]" value="1"
                                               @checked($prefs[$kind]['email'] ?? false) aria-label="E-mail {{ $def['label'] }}">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="margin-top:12px;">
                    <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                        <x-ukc.icon name="check" :size="16" /> Enregistrer les préférences
                    </button>
                </div>
            </form>
        </div>

        <div class="ukc-card" style="padding:0;overflow:hidden;">
            @forelse ($notifications as $n)
                <form method="POST" action="{{ route('notifications.open', $n->id) }}">
                    @csrf
                    <button type="submit"
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
                </form>
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
</x-dynamic-component>
