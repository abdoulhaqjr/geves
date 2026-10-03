// Salle de visioconférence LiveKit intégrée à GESCO (SDK bundlé par Vite).
// Portée de la maquette DRI, adaptée : identité/rôle/jeton fournis par le serveur
// (aucun secret côté client), communications réelles, enregistrement via Egress.
// Chargée à la demande par app.js quand la page « Rejoindre » est affichée.
import {
    Room, RoomEvent, Track, VideoPresets,
    createLocalAudioTrack, createLocalVideoTrack,
} from 'livekit-client';

// Objet compat pour réutiliser la logique de la maquette (LK.RoomEvent, LK.Track…).
const LK = { Room, RoomEvent, Track, VideoPresets, createLocalAudioTrack, createLocalVideoTrack };

export async function startFromConfig() {
    const cfgEl = document.getElementById('lk-config');
    const rootEl = document.getElementById('visio-room');
    if (!cfgEl || !rootEl) return;

    const cfg = JSON.parse(cfgEl.textContent);
    const roomName = cfg.roomName || 'salle';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const state = {
        name: cfg.userName || 'Invité',
        role: cfg.role || 'audience',
        mic: false, cam: false, screen: false, hand: false,
        speakerLayout: true, connected: false, speakerOn: true, volume: 1, recording: false,
    };
    let room = null;
    let people = [];

    const scheduledComms = (cfg.communications || []).map((c) => ({
        order: c.order, name: c.name, title: c.title, accountId: c.accountId,
        identity: '', online: false, isFloor: false, done: false,
    }));

    const $ = (id) => document.getElementById(id);
    const canPublish = () => state.role === 'presenter' || state.role === 'moderator';

    function toast(msg) {
        const t = $('toast'); if (!t) return;
        t.textContent = msg; t.classList.add('show');
        clearTimeout(window.__vTm);
        window.__vTm = setTimeout(() => { t.classList.remove('show'); t.textContent = ''; }, 2600);
    }
    // La classe porte l'affichage (le bandeau est un inline-flex avec sa pastille
    // tournante) : un `display:block` en dur écraserait sa mise en page.
    function setStatus(txt) { const s = $('lkStatus'); if (s) { s.textContent = txt || ''; s.classList.toggle('on', !!txt); } }
    function setConnPill(mode, label) { const el = $('connPill'); if (el) { el.className = 'pill ' + mode; el.textContent = label; } }
    function initials(n) { return (n || '?').split(/\s+/).filter(Boolean).map((p) => p[0]).join('').slice(0, 2).toUpperCase() || '?'; }
    function roleLabel(r) { return r === 'presenter' ? 'Communicateur' : r === 'moderator' ? 'Modérateur' : 'Auditeur'; }

    // ---- Permissions média ----
    const mediaPerm = { cam: false, mic: false };
    function showPerm(msg) { const b = $('permBanner'), t = $('permBannerText'); if (t) t.textContent = msg; if (b) b.classList.add('on'); }
    function hidePerm() { const b = $('permBanner'); if (b) b.classList.remove('on'); }
    async function ensureMedia(needCam, needMic) {
        if ((!needCam || mediaPerm.cam) && (!needMic || mediaPerm.mic)) return true;
        // Contexte sécurisé requis : hors HTTPS (et hors localhost) les navigateurs
        // désactivent l'accès caméra/micro - le cadenas « Autoriser » n'y change rien.
        if (!window.isSecureContext || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showPerm('La visioconférence nécessite une connexion sécurisée (HTTPS). Ouvrez le site en https:// (ex. « herd secure »), ou via localhost, puis réessayez.');
            return false;
        }
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: needCam && !mediaPerm.cam, audio: needMic && !mediaPerm.mic });
            stream.getTracks().forEach((tr) => tr.stop());
            if (needCam) mediaPerm.cam = true;
            if (needMic) mediaPerm.mic = true;
            hidePerm();
            return true;
        } catch (e) {
            const name = (e && e.name) || '';
            let msg;
            if (name === 'NotAllowedError' || name === 'SecurityError') {
                msg = 'Accès caméra / micro refusé. Ouvrez le cadenas de la barre d’adresse → Autoriser, puis réessayez.';
            } else if (name === 'NotFoundError' || name === 'OverconstrainedError') {
                msg = 'Aucune caméra / micro détecté. Branchez un périphérique, puis réessayez.';
            } else if (name === 'NotReadableError' || name === 'AbortError') {
                msg = 'Caméra / micro déjà utilisé par une autre application. Fermez-la, puis réessayez.';
            } else {
                msg = 'Impossible d’accéder à la caméra / au micro' + (name ? ' (' + name + ')' : '') + '. Vérifiez les autorisations du navigateur, puis réessayez.';
            }
            showPerm(msg);
            return false;
        }
    }
    $('permDismiss')?.addEventListener('click', hidePerm);
    $('permRetry')?.addEventListener('click', async () => { hidePerm(); await ensureMedia(true, true); });

    // ---- Média (attach/detach) ----
    function attachTrack(track, container) {
        if (!track || !container) return;
        const el = track.attach(); el.className = 'media'; el.playsInline = true; el.autoplay = true;
        const local = container.id === 'tileLocal' || container.classList.contains('pip-bubble');
        if (local) el.muted = true;
        else if (el.tagName === 'AUDIO' || el.tagName === 'VIDEO') el.muted = !state.speakerOn;
        if (container.classList.contains('pip-bubble')) {
            el.removeAttribute('width'); el.removeAttribute('height');
            el.style.cssText = 'position:absolute;top:50%;left:50%;width:100%;height:100%;transform:translate(-50%,-50%);object-fit:cover;border-radius:50%;';
        }
        container.appendChild(el);
        if (track.kind === LK.Track.Kind.Video || el.tagName === 'VIDEO') container.classList.add('has-video');
    }
    function clearMedia(container) {
        if (!container) return;
        container.querySelectorAll('video, audio').forEach((el) => { try { el.srcObject = null; } catch (e) {} el.remove(); });
        if (!container.querySelector('video')) container.classList.remove('has-video');
    }

    // ---- Data channel ----
    async function sendData(msg, destinationIdentities) {
        if (!room || !room.localParticipant) return;
        try {
            const data = new TextEncoder().encode(JSON.stringify(msg));
            const opts = { reliable: true };
            if (destinationIdentities && destinationIdentities.length) opts.destinationIdentities = destinationIdentities;
            await room.localParticipant.publishData(data, opts);
        } catch (e) { /* ignore */ }
    }

    // ---- Bulle PiP (caméra du présentateur pendant un partage d'écran) ----
    let pipEl = null, pipVisible = true, pipSize = 120;
    function ensurePip() {
        if (pipEl) return pipEl;
        const host = $('tileMain'); if (!host) return null;
        pipEl = document.createElement('div');
        pipEl.className = 'pip-bubble'; pipEl.id = 'screenPip';
        pipEl.innerHTML = '<div class="pip-avatar">' + initials(state.name) + '</div><div class="pip-size-hint"></div><div class="pip-resize"></div>';
        host.appendChild(pipEl);
        makeDraggable(pipEl, host); makeResizable(pipEl);
        return pipEl;
    }
    function destroyPip(broadcast) {
        if (!pipEl) return;
        clearMedia(pipEl); pipEl.remove(); pipEl = null; pipVisible = false;
        if (broadcast !== false && canPublish()) sendData({ type: 'pip_visibility', visible: false });
    }
    function setPipCamera(track) {
        if (!track) { destroyPip(false); return; }
        if (pipVisible === false && !canPublish()) return;
        pipVisible = true;
        const b = ensurePip(); if (!b) return;
        clearMedia(b); attachTrack(track, b); b.classList.add('has-video');
        if (canPublish()) sendData({ type: 'pip_visibility', visible: true });
    }
    function makeResizable(el) {
        const h = el.querySelector('.pip-resize'); if (!h) return;
        let r = false, sx = 0, ss = 120;
        h.addEventListener('pointerdown', (e) => { if (!canPublish()) return; e.stopPropagation(); e.preventDefault(); r = true; el.classList.add('resizing'); sx = e.clientX; ss = el.offsetWidth; h.setPointerCapture(e.pointerId); });
        h.addEventListener('pointermove', (e) => { if (!r) return; pipSize = Math.round(Math.max(72, Math.min(240, ss + (e.clientX - sx)))); el.style.width = el.style.height = pipSize + 'px'; const hint = el.querySelector('.pip-size-hint'); if (hint) hint.textContent = pipSize + 'px'; });
        h.addEventListener('pointerup', () => { r = false; el.classList.remove('resizing'); });
    }
    function makeDraggable(el, host) {
        let ox = 0, oy = 0, drag = false;
        el.addEventListener('pointerdown', (e) => { if (!canPublish()) return; drag = true; el.classList.add('dragging'); const dz = $('pipDropzone'); if (dz) dz.classList.add('visible'); const rc = el.getBoundingClientRect(); ox = e.clientX - rc.left; oy = e.clientY - rc.top; el.setPointerCapture(e.pointerId); });
        el.addEventListener('pointermove', (e) => {
            if (!drag) return; const hr = host.getBoundingClientRect();
            let x = e.clientX - hr.left - ox, y = e.clientY - hr.top - oy;
            x = Math.max(4, Math.min(hr.width - el.offsetWidth - 4, x)); y = Math.max(4, Math.min(hr.height - el.offsetHeight - 4, y));
            el.style.left = x + 'px'; el.style.top = y + 'px'; el.style.right = el.style.bottom = 'auto';
            const dz = $('pipDropzone'); if (dz) dz.classList.toggle('active', y < 44);
        });
        el.addEventListener('pointerup', () => {
            if (!drag) return; drag = false; el.classList.remove('dragging');
            const close = (parseInt(el.style.top, 10) || 999) < 48;
            const dz = $('pipDropzone'); if (dz) dz.classList.remove('visible', 'active');
            if (close) { destroyPip(true); toast('Caméra du présentateur masquée'); }
        });
    }

    // ---- Scène (présentateur / écran) ----
    function showPresenterInfo(name, meta) {
        const main = $('tileMain'); clearMedia(main); main.classList.remove('has-video', 'has-screen');
        $('mainAvatar').style.display = ''; $('mainAvatar').textContent = initials(name);
        $('mainName').textContent = name || 'Présentateur'; $('mainRole').textContent = 'Présentateur';
        const info = $('mainInfo');
        if (info) { if (meta) { info.style.display = 'block'; $('mainInfoName').textContent = name || '-'; $('mainInfoMeta').textContent = meta; } else info.style.display = 'none'; }
        main.classList.add('is-presenter'); destroyPip(false);
    }
    function showScreenOnPresenter(track, label) {
        const main = $('tileMain'); if (!main || !track) return;
        clearMedia(main); const info = $('mainInfo'); if (info) info.style.display = 'none';
        attachTrack(track, main); $('mainName').textContent = label || 'Présentateur'; $('mainRole').textContent = 'Partage d’écran';
        main.classList.add('is-presenter', 'has-video', 'has-screen'); updateLayoutClasses();
    }
    function focusPresenter(participant, track) {
        const main = $('tileMain'); if (!main || !track) return;
        clearMedia(main); const info = $('mainInfo'); if (info) info.style.display = 'none';
        main.classList.remove('has-screen'); attachTrack(track, main);
        $('mainName').textContent = participant.name || participant.identity;
        $('mainAvatar').textContent = initials(participant.name || participant.identity);
        $('mainRole').textContent = 'Présentateur'; main.classList.add('is-presenter', 'has-video');
    }
    function findPresenterParticipant() {
        const pe = people.find((x) => x.isFloor || x.role === 'presenter'); if (!pe || !room) return null;
        if (pe.id === 'local') return room.localParticipant;
        let f = null; room.remoteParticipants.forEach((p) => { if (p.identity === pe.id) f = p; }); return f;
    }
    function showCurrentPresenterInStage() {
        const p = findPresenterParticipant();
        if (!p) { const pe = people.find((x) => x.isFloor || x.role === 'presenter'); showPresenterInfo(pe ? pe.name : 'Présentateur', 'En attente de la caméra ou du partage d’écran'); return; }
        const scr = p.getTrackPublication && p.getTrackPublication(LK.Track.Source.ScreenShare);
        if (scr && scr.track) { showScreenOnPresenter(scr.track, (p.name || p.identity) + ' (écran)'); const cam = p.getTrackPublication(LK.Track.Source.Camera); if (cam && cam.track) setPipCamera(cam.track); else destroyPip(false); return; }
        const cam = p.getTrackPublication && p.getTrackPublication(LK.Track.Source.Camera);
        if (cam && cam.track) { focusPresenter({ name: p.name || p.identity, identity: p.identity }, cam.track); destroyPip(false); }
        else { const c = scheduledComms.find((x) => x.identity === p.identity); showPresenterInfo(p.name || p.identity, c ? c.title : 'Caméra désactivée'); }
    }
    function restoreStage(participant) {
        if (state.role === 'moderator') { showCurrentPresenterInStage(); return; }
        const cam = participant && participant.getTrackPublication && participant.getTrackPublication(LK.Track.Source.Camera);
        if (cam && cam.track) { focusPresenter({ name: participant.name || participant.identity }, cam.track); destroyPip(false); }
        else showCurrentPresenterInStage();
    }

    // ---- Participants ----
    function ensureRemoteTile(p) {
        const id = 'tile-' + p.identity; let el = $(id); if (el) return el;
        el = document.createElement('div'); el.className = 'tile'; el.id = id;
        el.innerHTML = '<div class="avatar">' + initials(p.name || p.identity) + '</div><div class="name">' + (p.name || p.identity) + '</div>';
        (state.speakerLayout ? $('filmstrip') : $('stageGrid')).appendChild(el);
        updateFilmstripCount(); return el;
    }
    function updateFilmstripCount() { const el = $('filmstripCount'); if (el) el.textContent = String($('filmstrip').querySelectorAll('.tile').length); }
    function refreshPeople() {
        const isMod = state.role === 'moderator';
        $('peopleList').innerHTML = people.map((p) => {
            let actions = '';
            if (isMod && p.id !== 'local') {
                actions = '<div class="mod-actions">' +
                    '<button type="button" data-act="' + (p.micOn ? 'mic_off' : 'mic') + '" data-id="' + p.id + '">' + (p.micOn ? 'Couper micro' : 'Autoriser micro') + '</button>' +
                    (p.hand ? '<button type="button" data-act="hand" data-id="' + p.id + '">Baisser main</button>' : '') + '</div>';
            }
            return '<div class="person"><div class="av">' + initials(p.name) + '</div><div class="meta"><strong>' + p.name + '</strong><span>' + roleLabel(p.role) +
                (p.hand ? ' · ✋ main' : '') + (p.screen ? ' · écran' : '') + '</span>' + actions + '</div></div>';
        }).join('');
        $('peopleList').querySelectorAll('.mod-actions button').forEach((b) => b.addEventListener('click', () => handleModAction(b.getAttribute('data-act'), b.getAttribute('data-id'))));
    }
    function matchComm(c) {
        const pe = people.find((p) => p.name && c.name && p.name.toLowerCase().indexOf(c.name.split(' ').pop().toLowerCase()) >= 0);
        if (pe) { c.identity = pe.id; c.online = true; c.isFloor = pe.role === 'presenter' || !!pe.isFloor; }
        else if (c.identity) { c.online = people.some((p) => p.id === c.identity); if (!c.online) c.isFloor = false; }
        else c.online = false;
    }
    function refreshComms() {
        const list = $('commList'); if (!list) return;
        scheduledComms.forEach(matchComm);
        const isMod = state.role === 'moderator';
        list.innerHTML = scheduledComms.map((c) => {
            const st = c.done ? '<span class="st on">✓ Effectuée</span>' : c.isFloor ? '<span class="st live">● En présentation</span>' : c.online ? '<span class="st on">En ligne</span>' : '<span class="st off">Hors ligne</span>';
            let acts = '';
            if (isMod) {
                acts = '<div class="acts">';
                if (c.online && c.identity) {
                    acts += !c.isFloor ? '<button type="button" class="primary" data-act="give_floor" data-id="' + c.identity + '">Donner la présentation</button>' : '<button type="button" data-act="demote" data-id="' + c.identity + '">Retirer</button>';
                    acts += '<button type="button" data-act="mic" data-id="' + c.identity + '">Autoriser micro</button>';
                }
                acts += c.done ? '<button type="button" disabled style="opacity:.6;">✓ Effectuée</button>' : '<button type="button" data-act="mark_done" data-order="' + c.order + '" data-id="' + (c.identity || ('ord-' + c.order)) + '">Marquer effectuée</button>';
                acts += '</div>';
            }
            return '<div class="comm-item' + (c.isFloor ? ' speaking' : '') + '"><div class="row"><div><strong>' + c.order + '. ' + c.name + '</strong><div style="font-size:12px;color:var(--vmuted);margin-top:2px;">' + c.title + '</div></div>' + st + '</div>' + acts + '</div>';
        }).join('');
        list.querySelectorAll('.acts button').forEach((b) => b.addEventListener('click', () => {
            const act = b.getAttribute('data-act');
            if (act === 'mark_done') { const ord = b.getAttribute('data-order'); scheduledComms.forEach((c) => { if (String(c.order) === String(ord)) c.done = true; }); refreshComms(); sendData({ type: 'comm_done', order: ord }); toast('Communication marquée effectuée'); return; }
            handleModAction(act, b.getAttribute('data-id'));
        }));
    }
    async function handleModAction(act, targetId) {
        if (state.role !== 'moderator') return;
        const p = people.find((x) => x.id === targetId);
        if (act === 'mic') { await sendData({ type: 'mod_unmute', from: state.name }, [targetId]); if (p) { p.micOn = true; } refreshPeople(); refreshComms(); toast('Micro autorisé'); }
        else if (act === 'mic_off') { await sendData({ type: 'mod_mute', from: state.name }, [targetId]); if (p) p.micOn = false; refreshPeople(); toast('Micro coupé'); }
        else if (act === 'hand') { await sendData({ type: 'mod_hand_down', from: state.name }, [targetId]); if (p) p.hand = false; refreshPeople(); }
        else if (act === 'demote') { await sendData({ type: 'mod_role', role: 'audience', from: state.name }, [targetId]); if (p) { p.role = 'audience'; p.isFloor = false; } refreshPeople(); refreshComms(); showCurrentPresenterInStage(); toast('Présentation retirée'); }
        else if (act === 'give_floor') {
            people.forEach((x) => { x.isFloor = x.id === targetId; if (x.id !== targetId && x.role === 'presenter') x.role = 'audience'; });
            const others = people.filter((x) => x.id !== targetId && x.id !== 'local').map((x) => x.id);
            if (others.length) await sendData({ type: 'mod_role', role: 'audience', from: state.name }, others);
            await sendData({ type: 'mod_role', role: 'presenter', from: state.name }, [targetId]);
            if (p) p.role = 'presenter'; refreshPeople(); refreshComms(); showCurrentPresenterInStage(); toast('Présentation accordée');
        }
    }

    // ---- Chat ----
    function addChat(who, text, me) {
        const log = $('chatLog'); if (!log) return;
        const d = document.createElement('div'); d.className = 'chat-msg' + (me ? ' me' : '');
        d.innerHTML = '<div class="who">' + who + '</div>' + text.replace(/[<>]/g, (c) => (c === '<' ? '&lt;' : '&gt;'));
        log.appendChild(d); log.scrollTop = log.scrollHeight;
    }
    async function sendChat() {
        const input = $('chatInput'); const text = (input.value || '').trim(); if (!text) return;
        addChat(state.name, text, true); input.value = '';
        await sendData({ type: 'chat', text });
    }

    // ---- Toolbar ----
    function syncToolbar() {
        const pub = canPublish();
        $('btnMic').disabled = !state.connected;
        $('btnCam').disabled = !state.connected || !pub;
        $('btnScreen').disabled = !state.connected || !pub;
        $('btnBg').disabled = !state.connected || !pub;
        $('btnHand').disabled = !state.connected;
        $('btnMic').classList.toggle('muted', !state.mic); $('btnMic').classList.toggle('active', state.mic);
        $('btnCam').classList.toggle('muted', !state.cam); $('btnCam').classList.toggle('active', state.cam);
        $('btnScreen').classList.toggle('active', state.screen);
        $('btnHand').classList.toggle('on', state.hand);
        $('btnRec').classList.toggle('active', state.recording);
        const sp = $('btnSpeaker'); sp.classList.toggle('muted', !state.speakerOn); sp.classList.toggle('active', state.speakerOn);
    }
    function updateModUi() {
        const tab = $('tabComms'); if (tab) tab.style.display = state.role === 'moderator' ? '' : 'none';
        const rec = $('btnRec'); if (rec) rec.style.display = (state.role === 'moderator' && cfg.canRecord) ? '' : 'none';
    }
    function updateLayoutClasses() {
        const grid = $('stageGrid'); if (!grid) return;
        grid.classList.toggle('speaker', !!state.speakerLayout);
        const main = $('tileMain'); const hasScreen = !!(main && main.classList.contains('has-screen'));
        grid.classList.toggle('has-screen-share', hasScreen && !state.speakerLayout);
    }
    function syncGridTiles() {
        const grid = $('stageGrid'), strip = $('filmstrip'), wrap = $('filmstripWrap');
        if (state.speakerLayout) { [].slice.call(grid.querySelectorAll('.tile:not(.main)')).forEach((t) => strip.appendChild(t)); if (wrap) wrap.style.display = ''; }
        else { [].slice.call(strip.querySelectorAll('.tile')).forEach((t) => grid.appendChild(t)); if (wrap) wrap.style.display = 'none'; }
        updateLayoutClasses(); updateFilmstripCount();
    }

    // ---- Partage d'écran ----
    async function startScreenShare(surface) {
        if (!canPublish() || !room) { toast('Partage réservé au présentateur / modérateur'); return; }
        try {
            await sendData({ type: 'screen_takeover', by: state.name, identity: room.localParticipant.identity });
            const opts = { audio: true, video: { displaySurface: surface || 'window' }, contentHint: 'detail', resolution: { width: 1920, height: 1080, frameRate: 15 } };
            try { await room.localParticipant.setScreenShareEnabled(true, opts); }
            catch (e1) { await room.localParticipant.setScreenShareEnabled(true, { audio: true }); }
            state.screen = true;
            toast('Partage d’écran actif');
        } catch (e) { toast('Partage annulé'); state.screen = false; destroyPip(false); }
        const me = people.find((p) => p.id === 'local'); if (me) me.screen = state.screen;
        refreshPeople(); syncToolbar();
        $('shareMenu')?.classList.remove('open');
        const sb = $('shareStopBtn'); if (sb) sb.style.display = state.screen ? '' : 'none';
    }
    async function stopScreenShare() {
        state.screen = false;
        if (room) { try { await room.localParticipant.setScreenShareEnabled(false); } catch (e) {} }
        destroyPip(true);
        const me = people.find((p) => p.id === 'local'); if (me) me.screen = false;
        showCurrentPresenterInStage(); refreshPeople(); syncToolbar();
        const sb = $('shareStopBtn'); if (sb) sb.style.display = 'none';
        toast('Partage arrêté');
    }
    function onLocalScreenTrack(track) {
        showScreenOnPresenter(track, state.name + ' (écran)');
        const cam = room && room.localParticipant.getTrackPublication(LK.Track.Source.Camera);
        setPipCamera(cam && cam.track ? cam.track : null);
    }

    // ---- Arrière-plan / flou (import dynamique de @livekit/track-processors) ----
    const blurState = { mode: 'none', processor: null, loading: false };
    async function loadProcessors() {
        if (window.__lkProc) return window.__lkProc;
        try { window.__lkProc = await import('@livekit/track-processors'); return window.__lkProc; }
        catch (e) { return null; }
    }
    async function applyBackground(mode) {
        if (blurState.loading || !room) return;
        blurState.loading = true; const btn = $('btnBg'); if (btn) btn.disabled = true;
        try {
            const pub = room.localParticipant.getTrackPublication(LK.Track.Source.Camera);
            if (mode !== 'none') { if (!state.cam || !pub || !pub.track) { toast('Activez d’abord la caméra'); mode = 'none'; } }
            if (pub && pub.track && typeof pub.track.stopProcessor === 'function') { try { await pub.track.stopProcessor(); } catch (e) {} }
            document.querySelectorAll('#bgMenu .bg-opt').forEach((el) => el.classList.toggle('active', el.getAttribute('data-bg') === mode));
            if (btn) btn.classList.toggle('active', mode !== 'none');
            if (mode === 'none') { blurState.mode = 'none'; toast('Arrière-plan : aucun'); return; }
            const mod = await loadProcessors();
            if (!mod || !pub || !pub.track || typeof pub.track.setProcessor !== 'function') { toast('Arrière-plan non disponible sur ce navigateur'); mode = 'none'; if (btn) btn.classList.remove('active'); return; }
            if (mode === 'blur') {
                if (!mod.BackgroundBlur) { toast('Flou non disponible sur ce navigateur'); mode = 'none'; if (btn) btn.classList.remove('active'); return; }
                await pub.track.setProcessor(mod.BackgroundBlur(8));
                blurState.mode = 'blur'; toast('Arrière-plan : flou');
            } else {
                // « mode » est une URL d'image (arrière-plan admin ou image perso).
                if (mod.VirtualBackground) {
                    await pub.track.setProcessor(mod.VirtualBackground(mode));
                    blurState.mode = 'image'; toast('Arrière-plan : image');
                } else if (mod.BackgroundBlur) {
                    await pub.track.setProcessor(mod.BackgroundBlur(8));
                    toast('Image non supportée par ce navigateur - flou appliqué');
                } else {
                    toast('Arrière-plan image non disponible'); mode = 'none'; if (btn) btn.classList.remove('active');
                }
            }
        } catch (e) { toast('Impossible d’appliquer l’arrière-plan'); }
        finally { blurState.loading = false; if (btn) btn.disabled = false; }
    }

    // ---- Présence tenue DANS la salle (modérateur / rapporteur) ----
    // ⚠️ Faire l'aller-retour vers la page de présence obligeait à quitter la
    // séance au moment précis où l'on regarde les gens. L'onglet n'apparaît que
    // si le serveur a donné les adresses (cfg.presence) : c'est lui qui décide
    // qui a le droit de pointer, pas le navigateur.
    var presenceRows = [];
    var presenceTimer = null;

    function presenceLabel(st) {
        return st === 'present' ? 'Présent' : st === 'absent' ? 'Absent' : st === 'excused' ? 'Excusé' : '';
    }

    function renderPresence(data) {
        var list = $('presenceList'), counts = $('presenceCounts');
        if (!list) return;

        var c = data.counts || {};
        counts.innerHTML = '<span>Présents <b>' + (c.present || 0) + '</b></span>'
            + '<span>Absents <b>' + (c.absent || 0) + '</b></span>'
            + '<span>Excusés <b>' + (c.excused || 0) + '</b></span>'
            + '<span>Attendus <b>' + (c.expected || 0) + '</b></span>';

        if (!presenceRows.length) {
            list.innerHTML = '<div class="pr-empty">Aucune communication programmée dans cette séance : il n’y a personne à pointer.</div>';
            return;
        }

        list.innerHTML = presenceRows.map(function (r) {
            // ⚠️ « En salle » n'est affiché que si LiveKit a su répondre : on ne
            // laisse pas croire que personne n'est là quand on ne sait pas.
            var salle = (data.roomKnown && r.inRoom) ? ' · <span class="in-room">● En salle</span>' : '';
            var auto = r.auto ? ' · entré en visio' : '';
            var acts = ['present', 'absent', 'excused'].map(function (st) {
                return '<button type="button" data-id="' + r.id + '" data-st="' + st + '"'
                    + (r.status === st ? ' class="on"' : '') + '>' + presenceLabel(st) + '</button>';
            }).join('');

            return '<div class="pr-item"><div class="nm">' + r.name + '</div>'
                + '<div class="meta">' + r.role + salle + auto + '</div>'
                + (data.canWrite ? '<div class="pr-acts">' + acts + '</div>' : '')
                + '</div>';
        }).join('');

        list.querySelectorAll('.pr-acts button').forEach(function (b) {
            b.addEventListener('click', function () {
                var id = b.getAttribute('data-id');
                var st = b.getAttribute('data-st');
                var row = presenceRows.filter(function (r) { return String(r.id) === String(id); })[0];
                // Recliquer le même verdict le retire : c'est le moyen de défaire
                // une erreur sans quitter la salle.
                markPresence(id, (row && row.status === st) ? '' : st);
            });
        });
    }

    async function loadPresence() {
        if (!cfg.presence) return;
        try {
            const res = await fetch(cfg.presence.list, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!res.ok) return;
            const data = await res.json();
            presenceRows = data.rows || [];
            renderPresence(data);
        } catch (e) { /* salle d'abord : une présence injoignable ne casse pas la visio */ }
    }

    async function markPresence(id, status) {
        if (!cfg.presence) return;
        try {
            const res = await fetch(cfg.presence.mark, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', Accept: 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ participant_id: id, status: status }),
            });
            if (!res.ok) { toast('Pointage refusé'); return; }
            toast(status ? 'Présence enregistrée' : 'Pointage retiré');
            await loadPresence();
        } catch (e) { toast('Pointage indisponible'); }
    }

    // ---- Enregistrement (Egress serveur) ----
    async function toggleRecording() {
        if (state.role !== 'moderator' || !cfg.egress) return;
        const start = !state.recording;
        const url = start ? cfg.egress.start : cfg.egress.stop;
        try {
            const res = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, credentials: 'same-origin' });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.ok) { toast(data.message || 'Enregistrement impossible'); return; }
            state.recording = start;
            $('recBadge').classList.toggle('on', start);
            sendData({ type: 'recording', on: start, by: state.name });
            toast(start ? 'Enregistrement démarré' : 'Enregistrement arrêté');
        } catch (e) { toast('Enregistrement indisponible'); }
        syncToolbar();
    }

    // ---- Connexion ----
    async function connect() {
        setStatus('Connexion à la salle…'); setConnPill('warn', 'Connexion…');
        room = new LK.Room({
            adaptiveStream: true, dynacast: true,
            videoCaptureDefaults: { resolution: { width: 960, height: 540, frameRate: 24 }, facingMode: 'user' },
            audioCaptureDefaults: { echoCancellation: true, noiseSuppression: true, autoGainControl: true },
            publishDefaults: { dtx: true, red: true, videoSimulcastLayers: LK.VideoPresets ? [LK.VideoPresets.h180, LK.VideoPresets.h360] : undefined, videoCodec: 'vp8' },
        });

        room
            .on(LK.RoomEvent.LocalTrackPublished, (pub) => {
                if (pub.source === LK.Track.Source.ScreenShare && pub.track) { state.screen = true; onLocalScreenTrack(pub.track); syncToolbar(); }
                if (pub.source === LK.Track.Source.Camera && pub.track) {
                    const lt = $('tileLocal'); clearMedia(lt); attachTrack(pub.track, lt);
                    if (blurState.mode === 'blur') applyBackground('blur');
                    if (canPublish() && state.screen) setPipCamera(pub.track);
                    else if (canPublish() && !state.screen && state.speakerLayout) focusPresenter({ name: state.name, identity: 'local' }, pub.track);
                }
            })
            .on(LK.RoomEvent.LocalTrackUnpublished, (pub) => {
                if (pub.source === LK.Track.Source.ScreenShare) { state.screen = false; destroyPip(true); syncToolbar(); }
                if (pub.source === LK.Track.Source.Camera) { const lt = $('tileLocal'); if (lt) { clearMedia(lt); lt.classList.remove('has-video'); } if (state.screen) destroyPip(true); }
            })
            .on(LK.RoomEvent.TrackSubscribed, (track, pub, participant) => {
                const tile = ensureRemoteTile(participant);
                const pe = people.find((x) => x.id === participant.identity);
                const isPres = pe && (pe.role === 'presenter' || pe.isFloor);
                if (track.kind === LK.Track.Kind.Audio) { attachTrack(track, tile); return; }
                if (pub.source === LK.Track.Source.ScreenShare) {
                    showScreenOnPresenter(track, (participant.name || participant.identity) + ' (écran)');
                    if (pe) { pe.screen = true; refreshPeople(); }
                    const cam = participant.getTrackPublication(LK.Track.Source.Camera);
                    setPipCamera(cam && cam.track ? cam.track : null);
                } else {
                    attachTrack(track, tile); tile.classList.add('has-video');
                    const main = $('tileMain'); const screenUp = main && main.classList.contains('has-screen');
                    if (isPres && state.speakerLayout && !screenUp && !state.screen) focusPresenter(participant, track);
                    else if (isPres && screenUp) setPipCamera(track);
                }
            })
            .on(LK.RoomEvent.TrackUnsubscribed, (track, pub, participant) => {
                try { track.detach(); } catch (e) {}
                const tile = $('tile-' + (participant ? participant.identity : ''));
                const pe = participant && people.find((x) => x.id === participant.identity);
                if (pub.source === LK.Track.Source.Camera) {
                    if (tile) { clearMedia(tile); tile.classList.remove('has-video'); }
                    const main = $('tileMain'); const screenUp = main && main.classList.contains('has-screen');
                    if (screenUp && pe && (pe.role === 'presenter' || pe.isFloor || pe.screen)) destroyPip(false);
                    else if (!screenUp && pe && (pe.role === 'presenter' || pe.isFloor)) showPresenterInfo(participant.name || participant.identity, 'Caméra désactivée');
                } else if (pub.source === LK.Track.Source.ScreenShare) { if (pe) pe.screen = false; restoreStage(participant); refreshPeople(); }
            })
            .on(LK.RoomEvent.ParticipantConnected, (p) => {
                let role = 'audience'; try { if (p.metadata) { const md = JSON.parse(p.metadata); if (md.role) role = md.role; } } catch (e) {}
                people.push({ name: p.name || p.identity, role, hand: false, screen: false, id: p.identity });
                refreshPeople(); refreshComms(); ensureRemoteTile(p); toast((p.name || p.identity) + ' a rejoint');
            })
            .on(LK.RoomEvent.ParticipantDisconnected, (p) => {
                people = people.filter((x) => x.id !== p.identity); refreshPeople(); refreshComms();
                const tile = $('tile-' + p.identity); if (tile) tile.remove(); updateFilmstripCount();
            })
            .on(LK.RoomEvent.Disconnected, () => { state.connected = false; setConnPill('err', 'Déconnecté'); syncToolbar(); })
            .on(LK.RoomEvent.Reconnecting, () => setConnPill('warn', 'Reconnexion…'))
            .on(LK.RoomEvent.Reconnected, () => setConnPill('ok', 'Connecté'))
            .on(LK.RoomEvent.DataReceived, (payload, participant) => onData(payload, participant));

        try {
            await room.connect(cfg.url, cfg.token);
            state.connected = true; setStatus(''); setConnPill('ok', 'Connecté');
            room.remoteParticipants.forEach((p) => {
                let role = 'audience'; try { if (p.metadata) { const md = JSON.parse(p.metadata); if (md.role) role = md.role; } } catch (e) {}
                if (!people.some((x) => x.id === p.identity)) people.push({ name: p.name || p.identity, role, hand: false, screen: false, id: p.identity });
                ensureRemoteTile(p);
                p.trackPublications.forEach((pub) => {
                    if (!pub.track) return;
                    if (pub.source === LK.Track.Source.ScreenShare) { const pe = people.find((x) => x.id === p.identity); if (pe) { pe.screen = true; pe.isFloor = true; } showScreenOnPresenter(pub.track, (p.name || p.identity) + ' (écran)'); const cam = p.getTrackPublication(LK.Track.Source.Camera); setPipCamera(cam && cam.track ? cam.track : null); }
                    else attachTrack(pub.track, ensureRemoteTile(p));
                });
            });
            const main = $('tileMain');
            if (main && !main.classList.contains('has-screen') && state.speakerLayout) showCurrentPresenterInStage();
            refreshPeople(); refreshComms();
            if (canPublish()) {
                try { await room.localParticipant.setCameraEnabled(true); await room.localParticipant.setMicrophoneEnabled(true); state.cam = true; state.mic = true; mediaPerm.cam = true; mediaPerm.mic = true; }
                catch (e) { toast('Autorisez caméra / micro'); }
            }
            syncToolbar();
        } catch (e) {
            setStatus('Connexion impossible : ' + ((e && e.message) || e)); setConnPill('err', 'Échec'); room = null;
        }
    }

    function onData(payload, participant) {
        let msg; try { msg = JSON.parse(new TextDecoder().decode(payload)); } catch (e) { return; }
        const fromId = participant && participant.identity;
        if (msg.type === 'chat') addChat((participant && (participant.name || participant.identity)) || 'Participant', msg.text);
        else if (msg.type === 'hand') { const pe = people.find((x) => x.id === fromId); if (pe) { pe.hand = !!msg.up; refreshPeople(); } }
        else if (msg.type === 'mod_unmute') { toast((msg.from || 'Modérateur') + ' vous autorise le micro'); if (room) room.localParticipant.setMicrophoneEnabled(true).then(() => { state.mic = true; syncToolbar(); }).catch(() => {}); }
        else if (msg.type === 'mod_mute') { if (room) room.localParticipant.setMicrophoneEnabled(false).then(() => { state.mic = false; syncToolbar(); toast('Le modérateur a coupé votre micro'); }).catch(() => {}); }
        else if (msg.type === 'mod_hand_down') { state.hand = false; const me = people.find((x) => x.id === 'local'); if (me) me.hand = false; refreshPeople(); syncToolbar(); }
        else if (msg.type === 'screen_takeover') { const my = room && room.localParticipant.identity; if (msg.identity && msg.identity !== my && state.screen) { room.localParticipant.setScreenShareEnabled(false).catch(() => {}); state.screen = false; destroyPip(false); syncToolbar(); toast((msg.by || 'Un participant') + ' a pris le partage'); } }
        else if (msg.type === 'recording') { state.recording = !!msg.on; $('recBadge').classList.toggle('on', state.recording); syncToolbar(); if (msg.on) toast('Enregistrement en cours'); }
        else if (msg.type === 'comm_done') { scheduledComms.forEach((c) => { if (String(c.order) === String(msg.order)) c.done = true; }); refreshComms(); }
        else if (msg.type === 'pip_visibility') { if (msg.visible === false) { pipVisible = false; destroyPip(false); } else pipVisible = true; }
        else if (msg.type === 'mod_role') {
            const nr = msg.role === 'presenter' ? 'presenter' : 'audience'; state.role = nr;
            const me = people.find((x) => x.id === 'local'); if (me) me.role = nr;
            $('localRole').textContent = roleLabel(nr); rootEl.setAttribute('data-role', nr);
            updateModUi(); refreshPeople(); syncToolbar();
            toast(nr === 'presenter' ? 'Vous êtes nommé communicateur' : 'Rôle communicateur retiré');
            if (nr === 'audience' && room) { room.localParticipant.setCameraEnabled(false).catch(() => {}); room.localParticipant.setScreenShareEnabled(false).catch(() => {}); state.cam = false; state.screen = false; destroyPip(); syncToolbar(); }
        }
    }

    // ---- Sortie de la salle ----
    async function leave() { if (room) { try { await room.disconnect(); } catch (e) {} } window.location = cfg.backUrl; }

    // ============ Bindings ============
    $('btnMic').addEventListener('click', async () => {
        const me = people.find((x) => x.id === 'local');
        if (state.role === 'audience' && !(me && me.micGranted)) { toast('Levez la main - le modérateur doit autoriser votre micro'); return; }
        if (!state.mic) { if (!await ensureMedia(false, true)) return; }
        state.mic = !state.mic;
        if (room) { try { await room.localParticipant.setMicrophoneEnabled(state.mic); } catch (e) { state.mic = !state.mic; toast('Micro indisponible'); } }
        if (me) me.micOn = state.mic; refreshPeople(); syncToolbar();
    });
    $('btnCam').addEventListener('click', async () => {
        if (!canPublish()) return;
        if (!state.cam) { if (!await ensureMedia(true, false)) return; }
        state.cam = !state.cam;
        if (room) {
            try {
                await room.localParticipant.setCameraEnabled(state.cam);
                if (!state.cam && !state.screen && state.speakerLayout) showPresenterInfo(state.name, 'Caméra désactivée');
            } catch (e) { state.cam = !state.cam; toast('Caméra indisponible'); }
        }
        syncToolbar();
    });
    $('btnScreen').addEventListener('click', () => {
        if (!canPublish()) { toast('Partage réservé au présentateur / modérateur'); return; }
        $('bgMenu')?.classList.remove('open');
        if (state.screen) { const sb = $('shareStopBtn'); if (sb) sb.style.display = ''; }
        $('shareMenu')?.classList.toggle('open');
    });
    document.querySelectorAll('#shareMenu button[data-share]').forEach((b) => b.addEventListener('click', async () => {
        const t = b.getAttribute('data-share');
        if (t === 'stop') { await stopScreenShare(); $('shareMenu').classList.remove('open'); return; }
        await startScreenShare(t);
    }));
    $('btnHand').addEventListener('click', async () => {
        state.hand = !state.hand; const me = people.find((x) => x.id === 'local'); if (me) me.hand = state.hand;
        refreshPeople(); syncToolbar(); toast(state.hand ? 'Main levée' : 'Main baissée');
        await sendData({ type: 'hand', up: state.hand });
    });
    $('btnLayout').addEventListener('click', () => {
        state.speakerLayout = !state.speakerLayout;
        const wrap = $('filmstripWrap'); if (wrap) wrap.classList.toggle('open', !state.speakerLayout);
        syncGridTiles(); if (state.speakerLayout) showCurrentPresenterInStage();
        toast(state.speakerLayout ? 'Vue présentateur' : 'Vue grille');
    });
    $('filmstripToggle').addEventListener('click', () => $('filmstripWrap').classList.toggle('open'));
    $('btnFs').addEventListener('click', () => { const sm = $('stageMain'); if (!document.fullscreenElement) sm.requestFullscreen?.(); else document.exitFullscreen?.(); });
    document.addEventListener('fullscreenchange', () => $('btnFs').classList.toggle('active', !!document.fullscreenElement));
    $('btnBg').addEventListener('click', (e) => { e.stopPropagation(); $('shareMenu')?.classList.remove('open'); $('bgMenu').classList.toggle('open'); });
    document.querySelectorAll('#bgMenu .bg-opt:not(.bg-upload)').forEach((o) => o.addEventListener('click', () => { applyBackground(o.getAttribute('data-bg')); $('bgMenu').classList.remove('open'); }));
    // Arrière-plan personnel : image chargée par l'utilisateur (URL blob, locale).
    $('bgUpload')?.addEventListener('change', (e) => {
        const file = e.target.files && e.target.files[0];
        if (!file) return;
        const url = URL.createObjectURL(file);
        const opt = e.target.closest('.bg-upload');
        if (opt) { opt.style.backgroundImage = 'url(' + url + ')'; opt.style.backgroundSize = 'cover'; opt.style.backgroundPosition = 'center'; opt.querySelector('span')?.remove(); }
        applyBackground(url);
        $('bgMenu').classList.remove('open');
        e.target.value = '';
    });
    $('btnRec').addEventListener('click', toggleRecording);
    $('btnSendChat').addEventListener('click', sendChat);
    $('chatInput').addEventListener('keydown', (e) => { if (e.key === 'Enter') sendChat(); });
    document.querySelectorAll('#visio-room .side-tabs button').forEach((b) => b.addEventListener('click', () => {
        document.querySelectorAll('#visio-room .side-tabs button').forEach((x) => x.classList.remove('active')); b.classList.add('active');
        const id = b.getAttribute('data-side');
        $('panelChat').classList.toggle('active', id === 'chat'); $('panelPeople').classList.toggle('active', id === 'people');
        const pc = $('panelComms'); if (pc) pc.classList.toggle('active', id === 'comms'); if (id === 'comms') refreshComms();
        const pp = $('panelPresence'); if (pp) pp.classList.toggle('active', id === 'presence');
        if (id === 'presence') loadPresence();
    }));
    $('btnToggleSide').addEventListener('click', () => { const m = $('mainLayout'); m.classList.toggle('side-closed'); $('sidePanel').classList.toggle('open'); });
    // Volume / son
    $('btnSpeaker').addEventListener('click', (e) => { e.stopPropagation(); const p = $('volPanel'); if (p.classList.contains('open')) { state.speakerOn = !state.speakerOn; applyVolume(); } p.classList.toggle('open'); });
    $('volRange').addEventListener('input', (e) => { state.volume = Math.max(0, Math.min(1, e.target.value / 100)); state.speakerOn = state.volume > 0; applyVolume(); });
    function applyVolume() {
        const vol = state.speakerOn ? state.volume : 0;
        document.querySelectorAll('#visio-room video, #visio-room audio').forEach((el) => { if (el.closest('#tileLocal') || el.closest('.pip-bubble')) return; el.muted = vol === 0; try { el.volume = vol; } catch (e) {} });
        $('volVal').textContent = Math.round(state.volume * 100) + '%'; syncToolbar();
    }
    $('btnLeave').addEventListener('click', leave);
    $('btnLeaveTop').addEventListener('click', (e) => { e.preventDefault(); leave(); });
    document.addEventListener('click', (e) => {
        const bg = $('bgMenu'), sm = $('shareMenu'), vp = $('volPanel');
        if (bg && !bg.contains(e.target) && e.target !== $('btnBg') && !$('btnBg').contains(e.target)) bg.classList.remove('open');
        if (sm && !sm.contains(e.target) && !$('btnScreen').contains(e.target)) sm.classList.remove('open');
        if (vp && !vp.contains(e.target) && !$('btnSpeaker').contains(e.target)) vp.classList.remove('open');
    });
    window.addEventListener('beforeunload', () => { try { if (presenceTimer) clearInterval(presenceTimer); } catch (e) {} try { if (room) room.disconnect(); } catch (e) {} });

    // ============ Démarrage ============
    $('localName').textContent = state.name; $('localAvatar').textContent = initials(state.name); $('localRole').textContent = 'Vous';
    people = [{ name: state.name, role: state.role, hand: false, screen: false, id: 'local' }];
    updateModUi(); refreshPeople(); refreshComms();
    // L'onglet Présence n'existe que pour qui a le droit de pointer : c'est le
    // serveur qui le dit en donnant (ou non) les adresses.
    if (cfg.presence) {
        const tab = $('tabPresence'); if (tab) tab.style.display = '';
        const lien = $('presencePage'); if (lien) lien.href = cfg.presence.page;
        loadPresence();
        // Rafraîchissement discret : c'est ce qui fait apparaître « En salle »
        // quand quelqu'un arrive, sans que le modérateur ait à recharger.
        presenceTimer = setInterval(() => {
            if ($('panelPresence')?.classList.contains('active')) loadPresence();
        }, 20000);
    }
    showPresenterInfo(canPublish() ? state.name : 'Présentateur', 'Activez la caméra ou partagez votre écran');
    addChat('Système', 'Bienvenue dans la salle « ' + roomName + ' ».');
    await connect();
    syncToolbar();
}
