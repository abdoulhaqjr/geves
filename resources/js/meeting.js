// Lecteur de visio LiveKit intégré (SDK livekit-client, bundlé par Vite).
// Chargé à la demande par app.js quand la page « Rejoindre » est affichée.
// Lit sa configuration depuis <script id="lk-config" type="application/json">.
import { Room, RoomEvent } from 'livekit-client';

export async function startFromConfig() {
    const cfgEl = document.getElementById('lk-config');
    const grid = document.getElementById('lk-grid');
    const statusEl = document.getElementById('lk-status');
    if (!cfgEl || !grid) return;

    const cfg = JSON.parse(cfgEl.textContent);
    const setStatus = (t) => {
        if (statusEl) {
            statusEl.textContent = t || '';
            statusEl.style.display = t ? 'block' : 'none';
        }
    };

    const room = new Room({ adaptiveStream: true, dynacast: true });
    const tiles = {};

    const makeTile = (p) => {
        if (tiles[p.sid]) return tiles[p.sid];
        const el = document.createElement('div');
        el.className = 'lk-tile';
        const ph = document.createElement('div');
        ph.className = 'lk-ph';
        ph.textContent = (p.name || p.identity || '?').slice(0, 1).toUpperCase();
        const video = document.createElement('video');
        video.autoplay = true;
        video.playsInline = true;
        video.style.display = 'none';
        const name = document.createElement('span');
        name.className = 'lk-name';
        name.textContent = p.name || p.identity || 'Invité';
        el.append(ph, video, name);
        grid.appendChild(el);
        tiles[p.sid] = { el, video, ph };
        return tiles[p.sid];
    };

    const dropTile = (p) => {
        const t = tiles[p.sid];
        if (t) {
            t.el.remove();
            delete tiles[p.sid];
        }
    };

    const attach = (track, p) => {
        const t = makeTile(p);
        if (track.kind === 'video') {
            track.attach(t.video);
            t.video.style.display = 'block';
            t.ph.style.display = 'none';
        } else if (track.kind === 'audio') {
            const a = track.attach();
            a.style.display = 'none';
            document.body.appendChild(a);
        }
    };

    const detach = (track, p) => {
        if (track.kind === 'video') {
            const t = p && tiles[p.sid];
            if (t) {
                track.detach(t.video);
                t.video.style.display = 'none';
                t.ph.style.display = 'flex';
            }
        } else {
            track.detach().forEach((el) => el.remove());
        }
    };

    room
        .on(RoomEvent.TrackSubscribed, (track, pub, p) => attach(track, p))
        .on(RoomEvent.TrackUnsubscribed, (track, pub, p) => detach(track, p))
        .on(RoomEvent.ParticipantConnected, (p) => makeTile(p))
        .on(RoomEvent.ParticipantDisconnected, (p) => dropTile(p))
        .on(RoomEvent.LocalTrackPublished, (pub) => { if (pub.track) attach(pub.track, room.localParticipant); })
        .on(RoomEvent.LocalTrackUnpublished, (pub) => { if (pub.track) detach(pub.track, room.localParticipant); })
        .on(RoomEvent.Disconnected, () => { window.location = cfg.backUrl; });

    try {
        await room.connect(cfg.url, cfg.token);
        setStatus('');
        makeTile(room.localParticipant);
        if (cfg.canPublish) {
            try { await room.localParticipant.enableCameraAndMicrophone(); } catch (e) { /* permissions refusées */ }
        }
    } catch (e) {
        setStatus('Connexion impossible : ' + ((e && e.message) || e));
        return;
    }

    const bind = (id, get, set) => {
        const btn = document.getElementById(id);
        if (!btn) return;
        const sync = () => btn.classList.toggle('lk-off', !get());
        btn.onclick = async () => {
            try { await set(!get()); } catch (e) { /* ignore */ }
            sync();
        };
        sync();
    };
    bind('lk-mic', () => room.localParticipant.isMicrophoneEnabled, (v) => room.localParticipant.setMicrophoneEnabled(v));
    bind('lk-cam', () => room.localParticipant.isCameraEnabled, (v) => room.localParticipant.setCameraEnabled(v));
    bind('lk-screen', () => room.localParticipant.isScreenShareEnabled, (v) => room.localParticipant.setScreenShareEnabled(v));

    const leave = document.getElementById('lk-leave');
    if (leave) {
        leave.onclick = async () => {
            try { await room.disconnect(); } catch (e) { /* ignore */ }
            window.location = cfg.backUrl;
        };
    }
    window.addEventListener('beforeunload', () => { try { room.disconnect(); } catch (e) { /* ignore */ } });
}
