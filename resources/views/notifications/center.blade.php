<x-dynamic-component :component="$isAccount ? 'layouts.participant' : 'layouts.admin'"
                     :title="__('Notifications')" :subtitle="__('Alertes, décisions et rappels')">
    <div>
        @if ($isAccount)
            @include('partials.account-nav')
        @endif

        @php
            $kindDesc = [
                'submission_status' => __('Acceptation, refus, demande de révision'),
                'review_assigned' => __('Nouvelle évaluation qui vous est confiée'),
                'session_role' => __('Désignation comme modérateur ou rapporteur d’une séance'),
                'attestation' => __('Attestation ou reçu prêt à télécharger'),
                'invitation' => __('Invitations et rappels d’inscription'),
                'payment' => __('Confirmation, échec, remboursement'),
                'submission_new' => __('Nouvelle communication déposée'),
                'contact' => __('Nouveau message de contact'),
            ];
        @endphp

        {{-- ============ Centre de notifications ============ --}}
        <div class="ukc-card">
            <h3 style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                {{ __('Centre de notifications') }}
                @if ($unreadCount > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="ukc-btn ukc-btn--ghost ukc-btn--sm">{{ __('Tout marquer comme lu') }}</button>
                    </form>
                @endif
            </h3>
            <p style="color:var(--ukc-muted);font-size:.9rem;margin:4px 0 6px;">
                {{ __('Alertes liées à vos soumissions, paiements et documents.') }}
                @if ($unreadCount > 0)<strong>{{ trans_choice(':count non lue|:count non lues', $unreadCount, ['count' => $unreadCount]) }}.</strong>@endif
            </p>

            <div>
                @forelse ($notifications as $n)
                    <form method="POST" action="{{ route('notifications.open', $n->id) }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="notif-item {{ $n->read_at ? '' : 'unread' }}"
                                style="width:100%;text-align:left;border:none;background:{{ $n->read_at ? 'none' : '' }};cursor:pointer;">
                            <span class="dot {{ $n->read_at ? 'read' : '' }}"></span>
                            <span class="body">
                                <strong>{{ $n->data['title'] ?? __('Notification') }}</strong>
                                @if (! empty($n->data['body']))<p>{{ $n->data['body'] }}</p>@endif
                            </span>
                            <span class="when">{{ $n->created_at->translatedFormat('d/m - H:i') }}</span>
                        </button>
                    </form>
                @empty
                    <div class="ukc-empty" style="margin:0;border:none;">
                        <span class="ico"><x-ukc.icon name="bell" :size="28" /></span>
                        <h3>{{ __('Aucune notification') }}</h3>
                        <p>{{ __('Vos notifications apparaîtront ici (décisions, paiements, attestations…).') }}</p>
                    </div>
                @endforelse
            </div>

            @if ($notifications instanceof \Illuminate\Contracts\Pagination\Paginator || $notifications instanceof \Illuminate\Pagination\LengthAwarePaginator)
                @if ($notifications->hasPages())
                    <div style="margin-top:14px;">{{ $notifications->links() }}</div>
                @endif
            @endif
        </div>

        {{-- ============ Préférences de notification ============ --}}
        {{-- Espace participant : les préférences vivent dans l'onglet « Préférences ». --}}
        @unless ($isAccount)
        <div class="ukc-card">
            <h3>{{ __('Préférences de notification') }}</h3>
            <p style="color:var(--ukc-muted);font-size:.9rem;margin:4px 0 4px;">{{ __('Activez ou désactivez chaque type d’alerte (in-app & e-mail).') }}</p>
            <form method="POST" action="{{ route('notifications.prefs') }}">
                @csrf
                @foreach ($kinds as $kind => $def)
                    @php($on = ($prefs[$kind]['inapp'] ?? false) || ($prefs[$kind]['email'] ?? false))
                    <div class="switch-row" x-data="{ on: {{ $on ? 'true' : 'false' }} }">
                        <div class="meta">
                            <strong>{{ $def['label'] }}</strong>
                            <span>{{ $kindDesc[$kind] ?? '' }}</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" x-model="on">
                            <span class="slider"></span>
                        </label>
                        <input type="hidden" name="prefs[{{ $kind }}][inapp]" :value="on ? '1' : '0'">
                        <input type="hidden" name="prefs[{{ $kind }}][email]" :value="on ? '1' : '0'">
                    </div>
                @endforeach
                <div style="margin-top:16px;">
                    <button type="submit" class="ukc-btn ukc-btn--blue">{{ __('Enregistrer') }}</button>
                </div>
            </form>
        </div>
        @endunless
    </div>
</x-dynamic-component>
