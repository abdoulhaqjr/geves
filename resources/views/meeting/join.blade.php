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
            @if ($liveKit)
                {{-- Lecteur LiveKit inline (SDK livekit-client), aux couleurs UK. --}}
                <style>
                    .lk-stage{flex:1;min-height:0;display:flex;flex-direction:column;background:#0f1a33;}
                    .lk-grid{flex:1;min-height:0;display:grid;gap:10px;padding:12px;overflow:auto;align-content:center;
                             grid-template-columns:repeat(auto-fit,minmax(min(100%,300px),1fr));}
                    .lk-tile{position:relative;background:#16223f;border-radius:14px;overflow:hidden;aspect-ratio:16/9;
                             display:flex;align-items:center;justify-content:center;}
                    .lk-tile video{width:100%;height:100%;object-fit:cover;background:#0f1a33;}
                    .lk-ph{width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,var(--ukc-blue),var(--ukc-green));
                           color:#fff;font-weight:800;font-size:1.5rem;display:flex;align-items:center;justify-content:center;}
                    .lk-name{position:absolute;left:8px;bottom:8px;background:rgba(0,0,0,.55);color:#fff;
                             font-size:.75rem;font-weight:600;padding:3px 9px;border-radius:8px;}
                    .lk-bar{display:flex;align-items:center;justify-content:center;gap:12px;padding:12px;
                            background:#0b142a;border-top:1px solid rgba(255,255,255,.08);}
                    .lk-btn{width:48px;height:48px;border-radius:50%;border:none;cursor:pointer;color:#fff;
                            background:var(--ukc-blue);display:inline-flex;align-items:center;justify-content:center;transition:.15s;}
                    .lk-btn:hover{filter:brightness(1.1);}
                    .lk-btn.lk-off{background:#374151;}
                    .lk-btn.lk-danger{background:var(--ukc-red,#dc2626);}
                    .lk-status{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:#c7d1e6;
                               background:rgba(0,0,0,.4);padding:10px 16px;border-radius:10px;font-size:.9rem;}
                </style>
                <div class="lk-stage" style="position:relative;">
                    <div id="lk-grid" class="lk-grid"></div>
                    <div id="lk-status" class="lk-status">Connexion à la salle…</div>
                    <div class="lk-bar">
                        @if ($liveKit['canPublish'])
                            <button type="button" id="lk-mic" class="lk-btn" title="Micro" aria-label="Micro"><x-ukc.icon name="mic" :size="20" /></button>
                            <button type="button" id="lk-cam" class="lk-btn" title="Caméra" aria-label="Caméra"><x-ukc.icon name="video" :size="20" /></button>
                            <button type="button" id="lk-screen" class="lk-btn" title="Partager l’écran" aria-label="Partager l’écran"><x-ukc.icon name="monitor" :size="20" /></button>
                        @endif
                        <button type="button" id="lk-leave" class="lk-btn lk-danger" title="Quitter" aria-label="Quitter"><x-ukc.icon name="logout" :size="20" /></button>
                    </div>
                </div>
                <script src="https://cdn.jsdelivr.net/npm/livekit-client@2/dist/livekit-client.umd.min.js"></script>
                <script>
                    (async function () {
                        const LK = window.LivekitClient;
                        const statusEl = document.getElementById('lk-status');
                        const grid = document.getElementById('lk-grid');
                        const setStatus = (t) => { if (statusEl) { statusEl.textContent = t || ''; statusEl.style.display = t ? 'block' : 'none'; } };
                        if (!LK) { setStatus('Impossible de charger le module visio.'); return; }

                        const url = @js($liveKit['url']);
                        const token = @js($liveKit['token']);
                        const canPublish = @js($liveKit['canPublish']);
                        const backUrl = @js(url()->previous() ?: route('home'));

                        const room = new LK.Room({ adaptiveStream: true, dynacast: true });
                        const tiles = {};

                        function makeTile(p) {
                            if (tiles[p.sid]) return tiles[p.sid];
                            const el = document.createElement('div'); el.className = 'lk-tile';
                            const ph = document.createElement('div'); ph.className = 'lk-ph';
                            ph.textContent = (p.name || p.identity || '?').slice(0, 1).toUpperCase();
                            const video = document.createElement('video'); video.autoplay = true; video.playsInline = true; video.style.display = 'none';
                            const name = document.createElement('span'); name.className = 'lk-name'; name.textContent = p.name || p.identity || 'Invité';
                            el.append(ph, video, name); grid.appendChild(el);
                            return (tiles[p.sid] = { el, video, ph });
                        }
                        function dropTile(p) { const t = tiles[p.sid]; if (t) { t.el.remove(); delete tiles[p.sid]; } }
                        function attach(track, p) {
                            const t = makeTile(p);
                            if (track.kind === 'video') { track.attach(t.video); t.video.style.display = 'block'; t.ph.style.display = 'none'; }
                            else if (track.kind === 'audio') { const a = track.attach(); a.style.display = 'none'; document.body.appendChild(a); }
                        }
                        function detach(track, p) {
                            if (track.kind === 'video') { const t = p && tiles[p.sid]; if (t) { track.detach(t.video); t.video.style.display = 'none'; t.ph.style.display = 'flex'; } }
                            else { track.detach().forEach((el) => el.remove()); }
                        }

                        room.on(LK.RoomEvent.TrackSubscribed, (track, pub, p) => attach(track, p))
                            .on(LK.RoomEvent.TrackUnsubscribed, (track, pub, p) => detach(track, p))
                            .on(LK.RoomEvent.ParticipantConnected, (p) => makeTile(p))
                            .on(LK.RoomEvent.ParticipantDisconnected, (p) => dropTile(p))
                            .on(LK.RoomEvent.LocalTrackPublished, (pub) => { if (pub.track) attach(pub.track, room.localParticipant); })
                            .on(LK.RoomEvent.LocalTrackUnpublished, (pub) => { if (pub.track) detach(pub.track, room.localParticipant); })
                            .on(LK.RoomEvent.Disconnected, () => { window.location = backUrl; });

                        try {
                            await room.connect(url, token);
                            setStatus('');
                            makeTile(room.localParticipant);
                            if (canPublish) { try { await room.localParticipant.enableCameraAndMicrophone(); } catch (e) { /* permissions refusées */ } }
                        } catch (e) {
                            setStatus('Connexion impossible : ' + ((e && e.message) || e)); return;
                        }

                        const bind = (id, get, set) => {
                            const btn = document.getElementById(id); if (!btn) return;
                            const sync = () => btn.classList.toggle('lk-off', !get());
                            btn.onclick = async () => { try { await set(!get()); } catch (e) {} sync(); };
                            sync();
                        };
                        bind('lk-mic', () => room.localParticipant.isMicrophoneEnabled, (v) => room.localParticipant.setMicrophoneEnabled(v));
                        bind('lk-cam', () => room.localParticipant.isCameraEnabled, (v) => room.localParticipant.setCameraEnabled(v));
                        bind('lk-screen', () => room.localParticipant.isScreenShareEnabled, (v) => room.localParticipant.setScreenShareEnabled(v));

                        const leave = document.getElementById('lk-leave');
                        if (leave) leave.onclick = async () => { try { await room.disconnect(); } catch (e) {} window.location = backUrl; };
                        window.addEventListener('beforeunload', () => { try { room.disconnect(); } catch (e) {} });
                    })();
                </script>
            @elseif ($embed)
                {{-- Jitsi via l'IFrame API officielle (JitsiMeetExternalAPI). --}}
                <div id="jitsi-container" style="flex:1;width:100%;height:100%;"></div>
                <script src="https://{{ $embed['domain'] }}/external_api.js"></script>
                <script>
                    (function () {
                        if (typeof JitsiMeetExternalAPI === 'undefined') { return; }
                        const api = new JitsiMeetExternalAPI(@js($embed['domain']), {
                            roomName: @js($embed['roomName']),
                            parentNode: document.getElementById('jitsi-container'),
                            width: '100%',
                            height: '100%',
                            jwt: @js($embed['jwt']),
                            userInfo: { displayName: @js($embed['userName']) },
                            configOverwrite: { prejoinPageEnabled: true },
                        });
                        api.addListener('readyToClose', function () {
                            window.location = @js(url()->previous() ?: route('home'));
                        });
                    })();
                </script>
            @elseif ($externalUrl)
                {{-- Lien externe non embarquable (Zoom/Meet…) → nouvel onglet --}}
                <div style="flex:1;display:flex;align-items:center;justify-content:center;padding:24px;">
                    <div class="ukc-card" style="max-width:460px;text-align:center;">
                        <span class="ico" style="display:inline-flex;color:var(--ukc-blue);margin-bottom:8px;">
                            <x-ukc.icon name="video" :size="34" />
                        </span>
                        <h3 style="margin-bottom:6px;">Rejoindre la visio</h3>
                        <p style="color:var(--ukc-muted);">La salle s’ouvre dans un nouvel onglet.</p>
                        <a href="{{ $externalUrl }}" target="_blank" rel="noopener" class="ukc-btn ukc-btn--primary" style="margin-top:14px;">
                            <x-ukc.icon name="external" :size="16" /> Ouvrir la salle
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
