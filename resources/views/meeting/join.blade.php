<x-layouts.app :title="'Visio — ' . $session->label">
    @php($st = $session->meetingStatusEnum())
    <div style="display:flex;flex-direction:column;height:100vh;">
        {{-- Barre --}}
        <header style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;
                       padding:10px clamp(12px,3vw,24px);background:#fff;border-bottom:1px solid var(--ukc-border);">
            <div style="min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;">
                    @if ($session->isLive())
                        <span class="ukc-pill ukc-pill--err" style="display:inline-flex;align-items:center;gap:5px;">
                            <span style="width:8px;height:8px;border-radius:50%;background:currentColor;"></span> EN DIRECT
                        </span>
                    @endif
                    <strong style="font-size:1rem;">{{ $session->label }}</strong>
                </div>
                <div style="font-size:.8rem;color:var(--ukc-muted);">
                    {{ $session->edition?->title }}
                    @if ($role->canJoin()) · Vous rejoignez en tant que
                        <strong>{{ ['moderator' => 'modérateur', 'presenter' => 'présentateur', 'attendee' => 'participant'][$role->value] ?? '' }}</strong>
                    @endif
                </div>
            </div>
            <a href="{{ url()->previous() }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                <x-ukc.icon name="close" :size="16" /> Quitter
            </a>
        </header>

        {{-- Corps --}}
        <main style="flex:1;min-height:0;display:flex;">
            @if ($joinUrl && $embeddable)
                <iframe src="{{ $joinUrl }}" title="Visio de la session"
                        allow="camera; microphone; fullscreen; display-capture; autoplay; clipboard-write"
                        style="flex:1;width:100%;height:100%;border:0;"></iframe>
            @elseif ($joinUrl)
                {{-- Lien externe non embarquable (Zoom/Meet…) → nouvel onglet --}}
                <div style="flex:1;display:flex;align-items:center;justify-content:center;padding:24px;">
                    <div class="ukc-card" style="max-width:460px;text-align:center;">
                        <span class="ico" style="display:inline-flex;color:var(--ukc-blue);margin-bottom:8px;">
                            <x-ukc.icon name="video" :size="34" />
                        </span>
                        <h3 style="margin-bottom:6px;">La visio a lieu sur un service externe</h3>
                        <p style="color:var(--ukc-muted);">Cliquez pour rejoindre la réunion dans un nouvel onglet.</p>
                        <a href="{{ $joinUrl }}" target="_blank" rel="noopener" class="ukc-btn ukc-btn--primary" style="margin-top:14px;">
                            <x-ukc.icon name="external" :size="16" /> Rejoindre la réunion
                        </a>
                    </div>
                </div>
            @elseif ($session->isLive() && $role->canJoin())
                {{-- En direct, autorisé, mais salle non configurée --}}
                <div style="flex:1;display:flex;align-items:center;justify-content:center;padding:24px;">
                    <div class="ukc-card" style="max-width:460px;text-align:center;">
                        <h3 style="margin-bottom:6px;">Salle non configurée</h3>
                        <p style="color:var(--ukc-muted);">Le lien de la réunion n’a pas encore été renseigné par l’organisation.</p>
                    </div>
                </div>
            @else
                <div style="flex:1;display:flex;align-items:center;justify-content:center;padding:24px;">
                    <div class="ukc-card" style="max-width:460px;text-align:center;">
                        <span class="ico" style="display:inline-flex;color:var(--ukc-blue);margin-bottom:8px;">
                            <x-ukc.icon name="video" :size="34" />
                        </span>
                        @if (! $session->isLive())
                            <h3 style="margin-bottom:6px;">La visio n’est pas en cours</h3>
                            <p style="color:var(--ukc-muted);">
                                {{ $st->label() }}. Revenez à l’heure de la session
                                @if ($session->starts_at) ({{ $session->starts_at->translatedFormat('d F, H:i') }}) @endif.
                            </p>
                            @if ($session->hasRecording())
                                <a href="{{ $session->recording_url }}" target="_blank" class="ukc-btn ukc-btn--primary" style="margin-top:14px;">
                                    <x-ukc.icon name="video" :size="16" /> Voir le replay
                                </a>
                            @endif
                        @elseif ($isGuest)
                            <h3 style="margin-bottom:6px;">Connexion requise</h3>
                            <p style="color:var(--ukc-muted);">Cette visio est réservée aux participants inscrits.</p>
                            <a href="{{ route('account.login') }}" class="ukc-btn ukc-btn--primary" style="margin-top:14px;">
                                <x-ukc.icon name="login" :size="16" /> Se connecter
                            </a>
                        @else
                            <h3 style="margin-bottom:6px;">Accès réservé</h3>
                            <p style="color:var(--ukc-muted);">
                                Cette visio est réservée aux participants inscrits de cette édition.
                            </p>
                        @endif
                    </div>
                </div>
            @endif
        </main>
    </div>
</x-layouts.app>
