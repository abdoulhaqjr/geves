// Salle de visioconférence LiveKit intégrée à GESCO (SDK bundlé par Vite).
// Portée de la maquette DRI, adaptée : identité/rôle/jeton fournis par le serveur
// (aucun secret côté client), communications réelles, enregistrement via Egress.
// Chargée à la demande par app.js quand la page « Rejoindre » est affichée.
import {
    Room, RoomEvent, Track, VideoPresets,
    createLocalAudioTrack, createLocalVideoTrack,
} from 'livekit-client';

// Règles de la salle qui ne dépendent ni du DOM ni de LiveKit : elles vivent
// à part, et sont les seules du fichier à être couvertes par des tests
// (`meeting-pure.test.js`). Y déplacer une décision, c'est la rendre vérifiable.
import {
    cadreMedia, cadreTableau, celuiQuiPresente, droitDeTracer, enBasDuFil, etatChrono, grilleOptimale, heure, idSur,
    idsDesParleurs, indexDuDernierTraitDe, initials, lotsDeTraits, mainsOrdonnees, metadonnee, niveauReseau,
    ordreRecevable as ordreAutorise, presenceLabel, peutReagir, phraseConnectes, placeBulle,
    gestePointage, compteAConfirmer,
    compteEnAttente, entreeDirecte, pointTableau, profilVideo, questionsFiltrees, rangsDesMains,
    reactionRecevable, REACTIONS_MAX, roleLabel,
    teinteDuNom, texteCourt, traitAllege, traitRecu,
} from './meeting-pure.js';

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

    /**
     * Confirmation d'un geste qu'on ne peut pas défaire. Renvoie une promesse.
     *
     * ⚠️ Pas `window.confirm` : il gèle la page (la visio continue sans qu'on
     * puisse rien faire), sort visuellement de la salle, et n'est pas garanti
     * dans un contexte embarqué.
     */
    let repondModal = null;
    function confirme(titre, texte, libelleOui) {
        const m = $('vModal');
        if (!m) return Promise.resolve(true);
        $('vModalTitle').textContent = titre;
        $('vModalText').innerHTML = texte;
        $('vModalYes').textContent = libelleOui || 'Confirmer';
        m.classList.add('on');
        $('vModalYes').focus();

        return new Promise((resoud) => {
            repondModal = (ok) => { m.classList.remove('on'); repondModal = null; resoud(ok); };
        });
    }

    function toast(msg) {
        const t = $('toast'); if (!t) return;
        t.textContent = msg; t.classList.add('show');
        clearTimeout(window.__vTm);
        window.__vTm = setTimeout(() => { t.classList.remove('show'); t.textContent = ''; }, 2600);
    }
    // La classe porte l'affichage (le bandeau est un inline-flex avec sa pastille
    // tournante) : un `display:block` en dur écraserait sa mise en page.
    function setStatus(txt) { const s = $('lkStatus'); if (s) { s.textContent = txt || ''; s.classList.toggle('on', !!txt); } }
    // ⚠️ Le `title` n'est pas décoratif : sur téléphone, la pastille est réduite à
    // sa seule puce de couleur (cf. la feuille de style). Sans lui, l'état de la
    // connexion ne serait plus énonçable - ni au survol, ni pour un lecteur
    // d'écran.
    function setConnPill(mode, label) {
        const el = $('connPill');
        if (!el) return;
        el.className = 'pill ' + mode;
        el.textContent = label;
        el.title = label;
        el.setAttribute('aria-label', label);
    }

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
            showPerm(messageMedia(e));

            return false;
        }
    }

    /**
     * Ce qu'on peut dire d'un refus de caméra ou de micro - et surtout quoi faire
     * ensuite. Partagé par le bandeau de la salle et par l'écran d'entrée : un même
     * refus ne doit pas s'expliquer de deux façons selon l'endroit où il tombe.
     */
    function messageMedia(e) {
        const nom = (e && e.name) || '';
        if (nom === 'NotAllowedError' || nom === 'SecurityError') {
            return 'Accès caméra / micro refusé. Ouvrez le cadenas de la barre d’adresse → Autoriser, puis réessayez.';
        }
        if (nom === 'NotFoundError' || nom === 'OverconstrainedError') {
            return 'Aucune caméra / micro détecté. Branchez un périphérique, puis réessayez.';
        }
        if (nom === 'NotReadableError' || nom === 'AbortError') {
            return 'Caméra / micro déjà utilisé par une autre application. Fermez-la, puis réessayez.';
        }

        return 'Impossible d’accéder à la caméra / au micro' + (nom ? ' (' + nom + ')' : '')
            + '. Vérifiez les autorisations du navigateur, puis réessayez.';
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
            // ⚠️ Le miroir est posé ICI et non dans la feuille de style : la vignette
            // se centre par un `transform` écrit en style EN LIGNE, que rien
            // d'extérieur ne peut compléter sans l'écraser entièrement.
            const miroir = container.classList.contains('soi') ? ' scaleX(-1)' : '';
            el.style.cssText = 'position:absolute;top:50%;left:50%;width:100%;height:100%;'
                + 'transform:translate(-50%,-50%)' + miroir + ';object-fit:cover;border-radius:50%;';
        }
        container.appendChild(el);
        if (track.kind === LK.Track.Kind.Video || el.tagName === 'VIDEO') container.classList.add('has-video');
    }
    function clearMedia(container) {
        if (!container) return;
        container.querySelectorAll('video, audio').forEach((el) => { try { el.srcObject = null; } catch (e) {} el.remove(); });
        if (!container.querySelector('video')) container.classList.remove('has-video');
    }
    /**
     * Retire la seule IMAGE d'une tuile.
     *
     * ⚠️ `clearMedia` emporte aussi l'`<audio>`, qui est attaché à la MÊME tuile :
     * s'en servir pour une caméra qui s'éteint, c'est faire taire la personne
     * jusqu'à ce qu'elle rallume sa caméra.
     */
    function clearVideo(container) {
        if (!container) return;
        container.querySelectorAll('video').forEach((el) => { try { el.srcObject = null; } catch (e) {} el.remove(); });
        container.classList.remove('has-video');
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
        // Double flèche diagonale : le geste va dans les deux sens, et le curseur
        // de la poignée (`nwse-resize`) dit déjà exactement cela.
        const flecheRedim = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"'
            + ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            + '<polyline points="9 4 4 4 4 9"/><polyline points="15 20 20 20 20 15"/>'
            + '<line x1="4" y1="4" x2="10.5" y2="10.5"/><line x1="20" y1="20" x2="13.5" y2="13.5"/></svg>';

        pipEl.innerHTML = '<div class="pip-avatar"></div>'
            + '<div class="pip-size-hint"></div>'
            + '<div class="pip-resize" title="Glisser pour redimensionner · molette · double-clic">' + flecheRedim + '</div>';
        host.appendChild(pipEl);
        poseAvatar(pipEl.querySelector('.pip-avatar'), state.name);
        makeDraggable(pipEl, host); makeResizable(pipEl, host);
        return pipEl;
    }
    function destroyPip(broadcast) {
        if (!pipEl) return;
        clearMedia(pipEl); pipEl.remove(); pipEl = null; pipVisible = false;
        if (broadcast !== false && canPublish()) sendData({ type: 'pip_visibility', visible: false });
    }
    /** Vrai si cette image est celle de SA caméra, et non d'un présentateur distant. */
    function estMaCamera(track) {
        const pub = room && room.localParticipant
            && room.localParticipant.getTrackPublication(LK.Track.Source.Camera);

        return !!(pub && pub.track && pub.track === track);
    }
    function setPipCamera(track) {
        if (!track) { destroyPip(false); return; }
        if (pipVisible === false && !canPublish()) return;
        pipVisible = true;
        const b = ensurePip(); if (!b) return;
        // ⚠️ La bulle porte TANTÔT sa propre caméra (quand on partage son écran),
        // tantôt celle du présentateur distant. Seul son propre reflet se retourne,
        // et la classe se pose AVANT l'image : c'est elle qui décide du miroir.
        b.classList.toggle('soi', estMaCamera(track));
        clearMedia(b); attachTrack(track, b); b.classList.add('has-video');
        if (canPublish()) sendData({ type: 'pip_visibility', visible: true });
    }
    /** Au doigt : la poignée fait 40 px (cf. la feuille de style). */
    const AU_DOIGT = typeof matchMedia === 'function' && matchMedia('(pointer: coarse)').matches;

    /**
     * Tailles proposées par le double-clic, du plus discret au plus lisible.
     *
     * ⚠️ Le plancher est plus haut au doigt : avec une poignée de 40 px, une
     * vignette de 72 px serait à moitié recouverte par sa propre poignée.
     */
    const PIP_MIN = AU_DOIGT ? 110 : 72, PIP_MAX = 260;
    const PIP_SIZES = [84, 120, 168, 220].filter((t) => t >= PIP_MIN);

    /**
     * Redimensionnement de la vignette caméra.
     *
     * Trois gestes, parce qu'aucun ne s'impose à tout le monde : tirer la poignée,
     * la molette sur la vignette, ou un double-clic qui fait défiler des tailles.
     *
     * ⚠️ La taille suivait le seul déplacement HORIZONTAL du pointeur. Sur un
     * disque, c'est contre-intuitif : on tire vers l'extérieur, dans la direction
     * qu'on veut. C'est maintenant le BORD du disque qui suit le pointeur, à
     * l'écart près mesuré quand on l'attrape - pas de saut au premier contact, et
     * la vignette grandit d'exactement ce qu'on éloigne la main.
     */
    function makeResizable(el, host) {
        const hint = el.querySelector('.pip-size-hint');
        let masqueHint = null;

        function applique(px, montrer) {
            pipSize = Math.round(Math.max(PIP_MIN, Math.min(PIP_MAX, px)));
            el.style.width = el.style.height = pipSize + 'px';
            if (hint) hint.textContent = pipSize + ' px';
            retientTaille(pipSize);
            if (montrer) {
                el.classList.add('resizing');
                clearTimeout(masqueHint);
                masqueHint = setTimeout(() => el.classList.remove('resizing'), 700);
            }
        }

        const h = el.querySelector('.pip-resize');
        if (h) {
            let actif = false, ecart = 0, cx = 0, cy = 0;
            h.addEventListener('pointerdown', (e) => {
                if (!canPublish()) return;
                e.stopPropagation(); e.preventDefault();
                actif = true; el.classList.add('resizing');

                // ⚠️ Le centre est figé pour TOUT le geste. La vignette est ancrée
                // en bas à droite : en grandissant, son centre se déplaçait, la
                // mesure suivante partait de ce nouveau centre, et la taille
                // s'emballait jusqu'au maximum au moindre glissement.
                const r = el.getBoundingClientRect();
                cx = r.left + r.width / 2; cy = r.top + r.height / 2;

                // Écart entre le pointeur et le bord : la poignée est posée à
                // l'intérieur du disque, sans cet écart la vignette sauterait dès
                // le premier contact.
                ecart = Math.hypot(e.clientX - cx, e.clientY - cy) - r.width / 2;

                // On passe en position explicite pour pouvoir grandir AUTOUR du
                // centre, et non depuis le coin où elle était ancrée.
                const hr = host.getBoundingClientRect();
                el.style.left = (r.left - hr.left) + 'px'; el.style.top = (r.top - hr.top) + 'px';
                el.style.right = el.style.bottom = 'auto';

                h.setPointerCapture(e.pointerId);
            });
            h.addEventListener('pointermove', (e) => {
                if (!actif) return;
                applique(2 * (Math.hypot(e.clientX - cx, e.clientY - cy) - ecart));
                const hr = host.getBoundingClientRect();
                const t = el.offsetWidth;
                el.style.left = Math.max(4, Math.min(hr.width - t - 4, cx - hr.left - t / 2)) + 'px';
                el.style.top = Math.max(4, Math.min(hr.height - t - 4, cy - hr.top - t / 2)) + 'px';
            });
            // ⚠️ `pointercancel` aussi : sans lui, un geste interrompu (fenêtre qui
            // perd le focus) laissait la vignette en mode redimensionnement.
            ['pointerup', 'pointercancel'].forEach((ev) => h.addEventListener(ev, () => {
                actif = false; el.classList.remove('resizing');
            }));
            h.addEventListener('dblclick', (e) => e.stopPropagation());
        }

        // Molette : le geste naturel pour « plus gros / plus petit » sans viser.
        // Le pas est PROPORTIONNEL : à pas fixe, passer de la plus petite à la plus
        // grande taille demandait une dizaine de crans.
        el.addEventListener('wheel', (e) => {
            if (!canPublish()) return;
            e.preventDefault();
            const pas = Math.max(10, Math.round(el.offsetWidth * 0.1));
            applique(el.offsetWidth - Math.sign(e.deltaY) * pas, true);
        }, { passive: false });

        // Double-clic : fait défiler des tailles franches, pour qui ne veut pas
        // doser un geste continu.
        el.addEventListener('dblclick', (e) => {
            if (!canPublish()) return;
            e.preventDefault(); e.stopPropagation();
            const actuelle = el.offsetWidth;
            const suivante = PIP_SIZES.find((t) => t > actuelle + 4) ?? PIP_SIZES[0];
            applique(suivante, true);
        });

        applique(tailleRetenue(), false);
    }

    /**
     * Taille choisie, retenue d'une séance à l'autre. ⚠️ `localStorage` peut lever
     * (navigation privée, stockage bloqué) : la vignette doit s'afficher quand
     * même, à sa taille par défaut.
     */
    function retientTaille(px) {
        try { localStorage.setItem('ukc.pipSize', String(px)); } catch (e) { /* sans mémoire, tant pis */ }
    }

    function tailleRetenue() {
        try {
            const v = parseInt(localStorage.getItem('ukc.pipSize') || '', 10);
            if (v >= PIP_MIN && v <= PIP_MAX) return v;
        } catch (e) { /* idem */ }

        return pipSize;
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
        const main = $('tileMain'); clearMedia(main); main.classList.remove('has-video', 'has-screen', 'soi');
        $('mainAvatar').style.display = ''; poseAvatar($('mainAvatar'), name);
        $('mainName').textContent = name || 'Présentateur'; $('mainRole').textContent = 'Présentateur';
        const info = $('mainInfo');
        if (info) { if (meta) { info.style.display = 'block'; $('mainInfoName').textContent = name || '-'; $('mainInfoMeta').textContent = meta; } else info.style.display = 'none'; }
        main.classList.add('is-presenter'); destroyPip(false);
    }
    function showScreenOnPresenter(track, label) {
        const main = $('tileMain'); if (!main || !track) return;
        clearMedia(main); const info = $('mainInfo'); if (info) info.style.display = 'none';
        // Un écran partagé n'est pas un reflet : retourné, son texte serait illisible.
        main.classList.remove('soi');
        attachTrack(track, main); $('mainName').textContent = label || 'Présentateur'; $('mainRole').textContent = 'Partage d’écran';
        main.classList.add('is-presenter', 'has-video', 'has-screen'); updateLayoutClasses();
        // ⚠️ L'annotation se cale sur l'image : si elle change, le repère change.
        // Sans cela, les traits restaient calés sur la présentation PRÉCÉDENTE.
        suitLaScene(track);
    }
    function focusPresenter(participant, track) {
        const main = $('tileMain'); if (!main || !track) return;
        clearMedia(main); const info = $('mainInfo'); if (info) info.style.display = 'none';
        main.classList.remove('has-screen');
        // Sur la scène aussi, sa propre image est un reflet (cf. la feuille de style).
        main.classList.toggle('soi', participant.identity === 'local');
        attachTrack(track, main);
        $('mainName').textContent = participant.name || participant.identity;
        poseAvatar($('mainAvatar'), participant.name || participant.identity);
        $('mainRole').textContent = 'Présentateur'; main.classList.add('is-presenter', 'has-video');
        suitLaScene(track);
    }
    /**
     * Recale le cadre d'annotation sur ce que la scène vient d'afficher.
     *
     * ⚠️ `loadedmetadata` et pas seulement l'appel direct : au moment où la piste
     * est attachée, `videoWidth` vaut encore zéro. Recalculer tout de suite
     * donnerait un cadre vide, et l'annotation retomberait sur le 16/9 du tableau
     * alors qu'un diaporama 4/3 arrive une fraction de seconde plus tard.
     */
    function suitLaScene() {
        if (!board.ouvert || board.mode !== 'annote') return;
        const v = $('tileMain')?.querySelector('video');
        if (v) v.addEventListener('loadedmetadata', cadreDuTableau, { once: true });
        cadreDuTableau();
    }

    function findPresenterParticipant() {
        const pe = celuiQuiPresente(people); if (!pe || !room) return null;
        if (pe.id === 'local') return room.localParticipant;
        let f = null; room.remoteParticipants.forEach((p) => { if (p.identity === pe.id) f = p; }); return f;
    }
    /** Caméra réellement diffusée : abonnée **et** non coupée. */
    function cameraVive(p) {
        const pub = p && p.getTrackPublication && p.getTrackPublication(LK.Track.Source.Camera);
        if (!pub || !pub.track || pub.isMuted) return null;

        return pub.track;
    }

    /**
     * À défaut de présentateur désigné, quelqu'un qui diffuse vraiment.
     *
     * ⚠️ Annoncer « en attente de la caméra » pendant que deux caméras tournent
     * dans le bandeau, c'est la salle qui ment sur ce qu'elle a sous les yeux.
     * On montre d'abord un modérateur : c'est lui qui ouvre et anime la séance.
     */
    function premierDiffuseur() {
        if (!room) return null;
        const liste = [];
        room.remoteParticipants.forEach((p) => liste.push(p));
        const rang = (p) => { const pe = people.find((x) => x.id === p.identity); return pe && pe.role === 'moderator' ? 0 : 1; };
        liste.sort((a, b) => rang(a) - rang(b));

        return liste.find((p) => cameraVive(p)) || null;
    }

    function showCurrentPresenterInStage() {
        const p = findPresenterParticipant();
        if (!p) {
            const relais = premierDiffuseur();
            if (relais) { focusPresenter({ name: relais.name || relais.identity, identity: relais.identity }, cameraVive(relais)); destroyPip(false); return; }
            const pe = people.find((x) => x.isFloor || x.role === 'presenter');
            showPresenterInfo(pe ? pe.name : 'Présentateur', 'En attente de la caméra ou du partage d’écran');

            return;
        }
        const scr = p.getTrackPublication && p.getTrackPublication(LK.Track.Source.ScreenShare);
        if (scr && scr.track) { showScreenOnPresenter(scr.track, (p.name || p.identity) + ' (écran)'); const cam = p.getTrackPublication(LK.Track.Source.Camera); if (cam && cam.track) setPipCamera(cam.track); else destroyPip(false); return; }
        const cam = cameraVive(p);
        if (cam) { focusPresenter({ name: p.name || p.identity, identity: p.identity }, cam); destroyPip(false); return; }
        // ⚠️ Ne dire « caméra désactivée » que si elle l'est vraiment : une
        // publication non encore abonnée n'est pas une caméra éteinte, et la
        // scène le corrige d'elle-même dès que l'image arrive.
        const pub = p.getTrackPublication && p.getTrackPublication(LK.Track.Source.Camera);
        const c = scheduledComms.find((x) => x.identity === p.identity);
        showPresenterInfo(p.name || p.identity, c ? c.title : (pub && !pub.isMuted ? 'Connexion de la caméra…' : 'Caméra désactivée'));
    }
    /** Piste coupée / rallumée : la tuile, la scène et la liste suivent. */
    function onCameraToggle(pub, participant) {
        // Un micro qu'on coupe ne change rien à l'image, mais tout à la liste :
        // c'est là que le modérateur lit qui a la parole.
        if (pub && pub.source === LK.Track.Source.Microphone) { refreshPeople(); return; }
        if (!pub || pub.source !== LK.Track.Source.Camera || !participant) return;
        const tile = $('tile-' + participant.identity);
        if (tile) {
            clearVideo(tile);
            if (!pub.isMuted && pub.track) attachTrack(pub.track, tile);
        }
        const main = $('tileMain');
        if (main && !main.classList.contains('has-screen') && state.speakerLayout) showCurrentPresenterInStage();
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
        el.innerHTML = '<div class="avatar"></div>'
            + '<div class="name"><span class="nm"></span><button type="button" class="mic-dot" disabled></button></div>';
        // `textContent` et non une concaténation : un nom d'affichage vient de
        // l'utilisateur, et se retrouverait interprété comme du balisage.
        el.querySelector('.nm').textContent = p.name || p.identity;
        poseAvatar(el.querySelector('.avatar'), p.name || p.identity);
        el.querySelector('.mic-dot').addEventListener('click', (e) => { e.stopPropagation(); basculeMicro(p.identity); });
        (state.speakerLayout ? $('filmstrip') : $('stageGrid')).appendChild(el);
        // ⚠️ Le bandeau NE S'OUVRE PAS tout seul à l'arrivée de quelqu'un. Il le
        // faisait, pour qu'une scène vide ne cache pas trois personnes connectées -
        // mais il s'ouvrait alors par-dessus la séance à chaque arrivée, sans qu'on
        // l'ait demandé, et il fallait le refermer à la main. Le compteur de son
        // en-tête (« Participants (3) ») dit déjà qui est là ; l'ouvrir est un geste.
        updateFilmstripCount(); layoutGrid(); return el;
    }
    function updateFilmstripCount() { const el = $('filmstripCount'); if (el) el.textContent = String($('filmstrip').querySelectorAll('.tile').length); }
    /** Micro réellement ouvert : publié **et** non coupé. */
    function microActif(p) {
        const pub = p && p.getTrackPublication && p.getTrackPublication(LK.Track.Source.Microphone);

        return !!(pub && pub.track && !pub.isMuted);
    }

    /**
     * Aligne l'état des micros sur ce que la salle diffuse vraiment.
     *
     * ⚠️ `micOn` n'était écrit que par les actions du modérateur : il disait ce
     * qu'il avait DEMANDÉ, pas ce que la personne faisait. Quelqu'un qui se
     * coupait restait affiché « micro ouvert », et le bouton proposait de couper
     * un micro déjà coupé.
     */
    function syncMicStates() {
        people.forEach((pe) => {
            if (pe.id === 'local') { pe.micOn = !!state.mic; return; }
            if (!room) return;
            let trouve = null;
            room.remoteParticipants.forEach((p) => { if (p.identity === pe.id) trouve = p; });
            if (trouve) pe.micOn = microActif(trouve);
        });
    }

    /**
     * Mains levées, de la plus ancienne à la plus récente.
     *
     * ⚠️ Un compteur local et non l'horloge : les horloges de deux postes ne sont
     * pas comparables, alors que les messages arrivent dans l'ordre où ils ont été
     * émis. Le rang se recalcule à chaque affichage, donc une main baissée
     * renumérote les suivantes au lieu de laisser un trou.
     */
    let compteurMains = 0;
    function mainsLevees() {
        return mainsOrdonnees(people);
    }
    function leveLaMain(pe, levee) {
        if (!pe) return 0;
        pe.hand = !!levee;
        pe.handAt = levee ? ++compteurMains : 0;

        return levee ? mainsLevees().findIndex((x) => x === pe) + 1 : 0;
    }

    /** La main levée se voit sur la tuile et sur l'onglet, panneau fermé ou non. */
    function refreshHands() {
        const mains = mainsLevees();
        const compteur = $('handCount');
        if (compteur) { compteur.textContent = String(mains.length); compteur.classList.toggle('on', mains.length > 0); }
        document.querySelectorAll('#visio-room .tile').forEach((t) => {
            t.classList.remove('hand-up');
            const b = t.querySelector('.hand-badge'); if (b) b.remove();
        });
        mains.forEach((pe, i) => {
            const t = pe.id === 'local' ? $('tileLocal') : $('tile-' + pe.id);
            if (!t) return;
            t.classList.add('hand-up');
            const b = document.createElement('span');
            b.className = 'hand-badge';
            b.textContent = '✋ ' + (i + 1);
            t.appendChild(b);
        });
    }

    // Micro ouvert / coupé : les deux glyphes de la pastille des vignettes.
    const ICONE_MICRO = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm5-3c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/></svg>';
    const ICONE_MICRO_COUPE = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19 11h-1.7c0 .74-.16 1.43-.43 2.05l1.23 1.23c.56-.98.9-2.09.9-3.28zm-4.02.17c0-.06.02-.11.02-.17V5c0-1.66-1.34-3-3-3S9 3.34 9 5v.18l5.98 5.99zM4.27 3L3 4.27l6.01 6.01V11c0 1.66 1.33 3 2.99 3 .22 0 .44-.03.65-.08l1.66 1.66c-.71.33-1.5.52-2.31.52-2.76 0-5.3-2.1-5.3-5.1H5c0 3.41 2.72 6.23 6 6.72V21h2v-3.28c.91-.13 1.77-.45 2.54-.9L19.73 21 21 19.73 4.27 3z"/></svg>';

    /**
     * Micro basculé depuis une vignette.
     *
     * Chacun pilote le sien (on repasse par le bouton de la barre, qui porte déjà
     * les autorisations navigateur), le modérateur pilote ceux des autres.
     */
    function basculeMicro(id) {
        if (id === 'local') { $('btnMic').click(); return; }
        if (state.role !== 'moderator') return;
        const pe = people.find((x) => x.id === id);
        handleModAction(pe && pe.micOn ? 'mic_off' : 'mic', id);
    }

    /** Aligne la pastille de chaque vignette sur l'état réel du micro. */
    function majMicroTuiles() {
        const isMod = state.role === 'moderator';
        people.forEach((pe) => {
            const t = pe.id === 'local' ? $('tileLocal') : $('tile-' + pe.id);
            const b = t && t.querySelector('.mic-dot');
            if (!b) return;
            const ouvert = !!pe.micOn;
            b.innerHTML = ouvert ? ICONE_MICRO : ICONE_MICRO_COUPE;
            b.classList.toggle('off', !ouvert);
            b.disabled = !(isMod || pe.id === 'local');
            const etat = ouvert ? 'Micro ouvert' : 'Micro coupé';
            b.title = b.disabled ? etat : etat + ' - cliquer pour ' + (ouvert ? 'couper' : 'activer');
            b.setAttribute('aria-label', pe.name + ' : ' + b.title);
        });
    }

    /**
     * Barres de réseau : DEUX dessins, et non un seul qu'on recolore.
     *
     * ⚠️ À 13 px, la couleur seule ne sépare pas « instable » de « perdu », et
     * l'ambre des barres faibles est exactement celui de la main levée posée juste
     * à côté. Le dessin porte donc le niveau lui-même : une barre allumée sur
     * trois, ou le trait qui barre. C'est la règle déjà tenue pour le micro - deux
     * glyphes, pas une teinte - et la seule qui vaille pour qui distingue mal les
     * couleurs.
     */
    const ICONES_RESEAU = {
        faible: '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">'
            + '<rect x="3" y="14" width="4" height="7" rx="1"/>'
            + '<rect x="10" y="9" width="4" height="12" rx="1" opacity=".3"/>'
            + '<rect x="17" y="4" width="4" height="17" rx="1" opacity=".3"/></svg>',
        perdu: '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">'
            + '<rect x="3" y="14" width="4" height="7" rx="1" opacity=".3"/>'
            + '<rect x="10" y="9" width="4" height="12" rx="1" opacity=".3"/>'
            + '<rect x="17" y="4" width="4" height="17" rx="1" opacity=".3"/>'
            + '<path d="M4 20.5 L20 4.5" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>',
    };

    /**
     * Qui a la parole, sur les tuiles comme dans le panneau.
     *
     * ⚠️ L'événement arrive plusieurs fois par seconde : cette fonction ne
     * RECONSTRUIT rien, elle déplace une classe. Rebâtir la liste à chaque
     * changement de parole, c'est perdre le défilement du panneau sous les yeux de
     * celui qui le lit.
     */
    function majParleurs(speakers) {
        const idLocal = room && room.localParticipant ? room.localParticipant.identity : null;
        const ids = idsDesParleurs(speakers, idLocal);
        document.querySelectorAll('#visio-room .tile').forEach((t) => t.classList.remove('parle'));
        ids.forEach((id) => {
            const t = id === 'local' ? $('tileLocal') : $('tile-' + id);
            if (t) t.classList.add('parle');
        });
        people.forEach((pe) => { pe.parle = ids.has(pe.id); });
        document.querySelectorAll('#visio-room .person[data-id]').forEach((l) => {
            l.classList.toggle('parle', ids.has(l.dataset.id));
        });
    }

    /** Pose (ou retire) l'état du réseau là où l'œil cherche déjà le nom et le micro. */
    function poseReseau(tile, qualite) {
        const zone = tile && tile.querySelector('.name');
        if (!zone) return;
        const vieux = zone.querySelector('.net');
        if (vieux) vieux.remove();
        const n = niveauReseau(qualite);
        // Avant la pastille de rôle, qui ferme la ligne (`insertBefore(x, null)`
        // revient à l'ajouter en bout quand il n'y en a pas).
        if (n) zone.insertBefore(icone(ICONES_RESEAU[n.classe], 'net ' + n.classe, n.intitule), zone.querySelector('.role-chip'));
    }

    /**
     * Réseau d'un participant.
     *
     * ⚠️ Pour SOI, l'information remonte aussi dans la pastille du haut : c'est le
     * seul réseau sur lequel on puisse agir (se rapprocher, couper sa caméra), et
     * c'est le seul endroit qu'on regarde quand la salle se dégrade.
     */
    function majReseau(participant, qualite) {
        const idLocal = room && room.localParticipant ? room.localParticipant.identity : null;
        const id = participant && participant.identity === idLocal ? 'local' : (participant && participant.identity);
        if (!id) return;
        const pe = people.find((x) => x.id === id);
        const avant = niveauReseau(pe && pe.reseau);
        if (pe) pe.reseau = String(qualite || '');
        poseReseau(id === 'local' ? $('tileLocal') : $('tile-' + id), qualite);
        // ⚠️ Pas pendant une reprise : la salle annoncerait « Connecté » à la
        // seconde où elle est en train de se rattraper.
        if (id === 'local' && state.connected && !state.reprise) {
            const n = niveauReseau(qualite);
            setConnPill(n ? 'warn' : 'ok', n ? n.intitule : 'Connecté');
        }
        // ⚠️ La liste ne se refait que si ce qui s'y AFFICHE change. L'événement
        // tombe pour chaque participant, régulièrement et dès l'entrée : refaire
        // quarante lignes pour passer d'« excellent » à « bon », deux états qui ne
        // se montrent ni l'un ni l'autre, serait du travail pour rien.
        const apres = niveauReseau(qualite);
        if ((avant && avant.classe) !== (apres && apres.classe)) refreshPeople();
    }

    /**
     * Fabrique un élément dont le texte RESTE du texte.
     *
     * ⚠️ Les listes de la salle (participants, communicateurs, présence) étaient
     * construites par concaténation puis posées en `innerHTML`, avec dedans des
     * noms et des titres venus de la base. Un inscrit qui écrit
     * `<img src=x onerror=…>` dans son nom - rien ne l'en empêche, le profil
     * n'accepte qu'une longueur maximale - faisait alors exécuter son code chez
     * TOUS les participants de la salle, et dans le panneau du modérateur.
     * Aucune liste de la salle ne se construit plus en collant du texte dans du
     * balisage.
     */
    function elt(balise, classe, texte) {
        const n = document.createElement(balise);
        if (classe) n.className = classe;
        if (texte !== undefined && texte !== null) n.textContent = String(texte);

        return n;
    }

    /**
     * Petit porteur d'icône.
     *
     * ⚠️ `innerHTML` ici ne contredit pas la règle des listes : ce qu'on y pose est
     * une CONSTANTE du code (un glyphe), jamais une donnée venue de la base ou
     * d'un participant. Le texte, lui, passe par `title`/`aria-label`.
     */
    function icone(glyphe, classe, intitule) {
        const n = document.createElement('span');
        n.className = classe || '';
        n.innerHTML = glyphe;
        if (intitule) { n.title = intitule; n.setAttribute('aria-label', intitule); }

        return n;
    }

    /**
     * Pastille d'une personne : ses initiales, et SA couleur.
     *
     * ⚠️ Un seul endroit pour les trois pastilles (vignette, liste, bulle) : une
     * teinte posée à deux endroits sur trois donnerait à la même personne deux
     * couleurs dans la même salle.
     */
    function poseAvatar(el, nom) {
        if (!el) return;
        el.textContent = initials(nom);
        el.style.setProperty('--teinte', String(teinteDuNom(nom)));
    }

    /**
     * Qui préside, sur la vignette.
     *
     * ⚠️ Seuls le modérateur et le communicateur sont nommés : « Auditeur » répété
     * sur quarante vignettes n'apprend rien et mange la place du nom. La pastille
     * se REFAIT à chaque passage, car un rôle se donne et se reprend en séance.
     */
    function majRolesTuiles() {
        people.forEach((pe) => {
            const t = pe.id === 'local' ? $('tileLocal') : $('tile-' + pe.id);
            const zone = t && t.querySelector('.name');
            if (!zone) return;
            const vieux = zone.querySelector('.role-chip');
            if (vieux) vieux.remove();
            if (pe.role !== 'moderator' && pe.role !== 'presenter') return;
            const chip = elt('span', 'role-chip ' + (pe.role === 'moderator' ? 'mod' : 'pres'), roleLabel(pe.role));
            zone.appendChild(chip);
        });
    }

    /**
     * Le nombre de personnes dans la salle, dans la barre du haut.
     *
     * ⚠️ `people` fait foi : la liste suit les arrivées et les départs, là où le
     * compte des vignettes dépend de la disposition affichée.
     */
    function majCompteConnectes() {
        const el = $('compteConnectes');
        if (!el) return;
        const phrase = phraseConnectes(people.length);
        const nb = $('compteConnectesN');
        if (nb) nb.textContent = String(people.length);
        el.title = phrase;
        el.setAttribute('aria-label', phrase);
    }

    function refreshPeople() {
        syncMicStates();
        majCompteConnectes();
        const rangs = rangsDesMains(people);
        const isMod = state.role === 'moderator';
        const liste = $('peopleList');
        liste.textContent = '';

        people.forEach((p) => {
            const rang = rangs.get(p.id);
            const ligne = elt('div', 'person' + (rang ? ' hand-up' : '') + (p.parle ? ' parle' : ''));
            // L'identifiant reste sur la ligne : le liseré de parole change plusieurs
            // fois par seconde, et reconstruire quarante lignes pour déplacer une
            // couleur ferait perdre le défilement du panneau (cf. majParleurs).
            ligne.dataset.id = p.id;
            const pastille = elt('div', 'av');
            poseAvatar(pastille, p.name);
            ligne.appendChild(pastille);

            const meta = elt('div', 'meta');
            meta.appendChild(elt('strong', '', p.name));

            // ⚠️ Plus de « · » entre les états : la ligne les range par un écart
            // (cf. la feuille de style). Quatre séparateurs, c'était une quarantaine
            // de pixels pris au nom, et la ligne passait sur deux étages.
            const etat = elt('span');
            etat.appendChild(elt('span', '', roleLabel(p.role)));
            if (rang) etat.appendChild(elt('span', 'rank', '✋ ' + rang));
            etat.appendChild(icone(
                p.micOn ? ICONE_MICRO : ICONE_MICRO_COUPE,
                'mic ' + (p.micOn ? 'on' : 'off'),
                p.micOn ? 'Micro ouvert' : 'Micro coupé',
            ));
            const res = niveauReseau(p.reseau);
            if (res) etat.appendChild(icone(ICONES_RESEAU[res.classe], 'net ' + res.classe, res.intitule));
            if (p.screen) etat.appendChild(elt('span', '', 'écran'));
            meta.appendChild(etat);
            ligne.appendChild(meta);

            if (isMod && p.id !== 'local') {
                const actions = elt('div', 'mod-actions');
                const micro = elt('button', '', p.micOn ? 'Couper micro' : 'Autoriser micro');
                micro.type = 'button';
                micro.addEventListener('click', () => handleModAction(p.micOn ? 'mic_off' : 'mic', p.id));
                actions.appendChild(micro);
                if (p.hand) {
                    const main = elt('button', '', 'Baisser main');
                    main.type = 'button';
                    main.addEventListener('click', () => handleModAction('hand', p.id));
                    actions.appendChild(main);
                }
                ligne.appendChild(actions);
            }

            liste.appendChild(ligne);
        });

        refreshHands(); majMicroTuiles(); majRolesTuiles();
        // ⚠️ C'est la liste des personnes qui dit qui présente : arrivée, départ,
        // parole donnée, rôle retiré - tout passe par ici. Rappeler la règle du
        // crayon à un seul de ces endroits en oubliait toujours un.
        majDroitDeTracer();
    }
    /**
     * Rattache une communication programmée à la personne présente.
     *
     * ⚠️ Par IDENTIFIANT de compte d'abord. Le rattachement se faisait par
     * sous-chaîne du nom de famille : deux homonymes dans la salle, et le
     * modérateur donnait la parole à la mauvaise personne. Le nom ne subsiste
     * qu'en filet, pour les co-auteurs sans compte qu'aucun identifiant ne
     * désigne.
     */
    function matchComm(c) {
        const parId = c.accountId
            ? people.find((p) => p.accountId && Number(p.accountId) === Number(c.accountId))
            : null;
        const pe = parId
            || people.find((p) => p.name && c.name && p.name.toLowerCase().indexOf(c.name.split(' ').pop().toLowerCase()) >= 0);
        if (pe) { c.identity = pe.id; c.online = true; c.isFloor = pe.role === 'presenter' || !!pe.isFloor; }
        else if (c.identity) { c.online = people.some((p) => p.id === c.identity); if (!c.online) c.isFloor = false; }
        else c.online = false;
    }
    function refreshComms() {
        const list = $('commList');
        if (!list) return;
        scheduledComms.forEach(matchComm);
        const isMod = state.role === 'moderator';
        list.textContent = '';

        // ⚠️ Un panneau noir et nu se lit comme une panne : l'onglet s'ouvre pour
        // tout modérateur, y compris sur une séance dont le programme n'a pas
        // encore été saisi.
        if (!scheduledComms.length) {
            list.appendChild(elt('div', 'v-vide',
                'Aucune communication n’est programmée dans cette séance : il n’y a pas d’ordre de passage à tenir.'));

            return;
        }

        scheduledComms.forEach((c) => {
            const item = elt('div', 'comm-item' + (c.isFloor ? ' speaking' : ''));

            const ligne = elt('div', 'row');
            const bloc = elt('div');
            bloc.appendChild(elt('strong', '', c.order + '. ' + c.name));
            const titre = elt('div', '', c.title);
            titre.style.cssText = 'font-size:12px;color:var(--vmuted);margin-top:2px;';
            bloc.appendChild(titre);
            ligne.appendChild(bloc);

            const statut = c.done
                ? elt('span', 'st on', '✓ Effectuée')
                : c.isFloor ? elt('span', 'st live', '● En présentation')
                    : elt('span', 'st ' + (c.online ? 'on' : 'off'), c.online ? 'En ligne' : 'Hors ligne');
            ligne.appendChild(statut);
            item.appendChild(ligne);

            if (isMod) {
                const acts = elt('div', 'acts');
                const bouton = (libelle, classe, action) => {
                    const b = elt('button', classe || '', libelle);
                    b.type = 'button';
                    b.addEventListener('click', action);

                    return b;
                };

                if (c.online && c.identity) {
                    acts.appendChild(c.isFloor
                        ? bouton('Retirer', '', () => handleModAction('demote', c.identity))
                        : bouton('Donner la présentation', 'primary', () => handleModAction('give_floor', c.identity)));
                    acts.appendChild(bouton('Autoriser micro', '', () => handleModAction('mic', c.identity)));
                }

                if (c.done) {
                    const fait = elt('button', '', '✓ Effectuée');
                    fait.type = 'button'; fait.disabled = true; fait.style.opacity = '.6';
                    acts.appendChild(fait);
                } else {
                    acts.appendChild(bouton('Marquer effectuée', '', () => marqueEffectuee(c.order)));
                }
                item.appendChild(acts);
            }

            list.appendChild(item);
        });
    }

    /** ⚠️ Geste SANS RETOUR : la marque part à toute la salle et ne se retire pas. */
    async function marqueEffectuee(ordre) {
        const c = scheduledComms.find((x) => String(x.order) === String(ordre));
        const ok = await confirme(
            'Marquer la communication effectuée',
            'La communication <strong>' + ((c && c.order) || ordre) + '. ' + texteEchappe((c && c.name) || '') + '</strong> sera signalée comme passée pour toute la salle. Cette marque ne se retire pas.',
            'Marquer effectuée',
        );
        if (!ok) return;
        scheduledComms.forEach((x) => { if (String(x.order) === String(ordre)) x.done = true; });
        refreshComms();
        sendData({ type: 'comm_done', order: ordre });
        // La communication est close : le compte n'a plus d'objet.
        if (String(chrono.ordre) === String(ordre)) { arreteChrono(); diffuseFinChrono(); }
        toast('Communication marquée effectuée');
    }

    /**
     * Le seul endroit qui compose encore du balisage (la boîte de confirmation met
     * un nom en gras) : le texte inséré y est donc échappé explicitement.
     */
    function texteEchappe(t) {
        const n = document.createElement('span');
        n.textContent = String(t ?? '');

        return n.innerHTML;
    }
    async function handleModAction(act, targetId) {
        if (state.role !== 'moderator') return;
        const p = people.find((x) => x.id === targetId);
        if (act === 'mic') { await sendData({ type: 'mod_unmute', from: state.name }, [targetId]); if (p) { p.micOn = true; } refreshPeople(); refreshComms(); toast('Micro autorisé'); }
        else if (act === 'mic_off') { await sendData({ type: 'mod_mute', from: state.name }, [targetId]); if (p) p.micOn = false; refreshPeople(); toast('Micro coupé'); }
        else if (act === 'hand') { await sendData({ type: 'mod_hand_down', from: state.name }, [targetId]); leveLaMain(p, false); refreshPeople(); }
        else if (act === 'demote') { await sendData({ type: 'mod_role', role: 'audience', from: state.name }, [targetId]); if (p) { p.role = 'audience'; p.isFloor = false; } arreteChrono(); diffuseFinChrono(); refreshPeople(); refreshComms(); showCurrentPresenterInStage(); toast('Présentation retirée'); }
        else if (act === 'give_floor') {
            people.forEach((x) => { x.isFloor = x.id === targetId; if (x.id !== targetId && x.role === 'presenter') x.role = 'audience'; });
            const others = people.filter((x) => x.id !== targetId && x.id !== 'local').map((x) => x.id);
            if (others.length) await sendData({ type: 'mod_role', role: 'audience', from: state.name }, others);
            await sendData({ type: 'mod_role', role: 'presenter', from: state.name }, [targetId]);
            if (p) p.role = 'presenter'; refreshPeople(); refreshComms(); showCurrentPresenterInStage();
            // Le temps de parole part avec la parole elle-même, pas au premier mot.
            demarreChrono(ordreDe(targetId));
            toast('Présentation accordée');
        }
    }

    // ---- Chat ----
    // ⚠️ Tout est construit en `textContent` : un message, un nom d'affichage et
    // une citation viennent d'autres participants. L'ancienne version collait le
    // texte dans `innerHTML` après avoir remplacé `<` et `>` - une liste noire,
    // donc fausse par construction (`onerror=` dans un attribut, entités…).
    let reponseA = null;

    function montreMessage(id) {
        const el = idSur(id) && $('msg-' + idSur(id));
        if (!el) return;
        el.scrollIntoView({ block: 'nearest' });
        el.classList.add('cible');
        setTimeout(() => el.classList.remove('cible'), 1300);
    }

    function preparerReponse(id, qui, texte) {
        reponseA = { id: idSur(id), who: texteCourt(qui, 40), text: texteCourt(texte, 120) };
        $('chatReplyWho').textContent = 'Réponse à ' + reponseA.who;
        $('chatReplyText').textContent = reponseA.text;
        $('chatReply').classList.add('on');
        $('chatInput').focus();
    }

    function annuleReponse() {
        reponseA = null;
        $('chatReply')?.classList.remove('on');
    }

    // ---- Messages non lus ----
    // ⚠️ Le tiroir est fermé par défaut sous 900px, et le panneau s'ouvre sur un
    // autre onglet : un message arrivait sans que rien ne le signale.
    let nonLus = 0;

    /** Vrai quand le fil est réellement sous les yeux. */
    function filVisible() {
        const panneau = $('panelChat');
        if (!panneau || !panneau.classList.contains('active')) return false;

        return !ETROIT.matches || $('sidePanel').classList.contains('open');
    }

    function majNonLus() {
        const badge = $('chatUnread');
        if (badge) {
            badge.textContent = String(nonLus);
            badge.classList.toggle('on', nonLus > 0);
        }
        $('btnToggleSide')?.classList.toggle('a-lire', nonLus > 0);
    }

    function filLu() {
        if (!nonLus) return;
        nonLus = 0;
        majNonLus();
    }

    // ---- Fil remonté ----
    // ⚠️ Deux comptes distincts, et il faut s'y tenir : `nonLus` compte ce qui est
    // arrivé quand le fil n'était PAS sous les yeux (pastille sur l'onglet) ;
    // celui-ci, ce qui est arrivé alors qu'il l'était, mais qu'on lisait plus haut.
    let aLireEnBas = 0;

    function descendLeFil() {
        const log = $('chatLog');
        if (!log) return;
        log.scrollTop = log.scrollHeight;
        if (aLireEnBas) { aLireEnBas = 0; majALireEnBas(); }
    }

    function majALireEnBas() {
        const b = $('chatVersBas');
        if (!b) return;
        b.textContent = aLireEnBas > 1
            ? '↓ ' + aLireEnBas + ' nouveaux messages'
            : '↓ 1 nouveau message';
        b.classList.toggle('on', aLireEnBas > 0);
    }

    function addChat(who, text, me, meta) {
        const log = $('chatLog');
        if (!log) return;
        const id = idSur(meta && meta.id);
        const d = document.createElement('div');
        d.className = 'chat-msg' + (me ? ' me' : '');
        if (id) d.id = 'msg-' + id;

        if (id) d.dataset.uid = id;

        const qui = document.createElement('div');
        qui.className = 'who'; qui.textContent = who;
        if (meta && meta.at) {
            const h = document.createElement('span');
            h.className = 'at'; h.textContent = heure(meta.at);
            qui.appendChild(h);
        }
        d.appendChild(qui);

        // La citation voyage AVEC le message (auteur + extrait) : un participant
        // arrivé après l'original doit quand même comprendre la réponse.
        const cite = meta && meta.replyTo;
        if (cite) {
            const q = document.createElement('button');
            q.type = 'button'; q.className = 'quote';
            const qw = document.createElement('span');
            qw.className = 'q-who'; qw.textContent = texteCourt(cite.who, 40) || 'Message';
            const qt = document.createElement('span');
            qt.className = 'q-text'; qt.textContent = texteCourt(cite.text, 120);
            q.appendChild(qw); q.appendChild(qt);
            q.addEventListener('click', () => montreMessage(cite.id));
            d.appendChild(q);
        }

        const txt = document.createElement('div');
        txt.className = 'txt'; txt.textContent = text;
        d.appendChild(txt);

        // Pas d'actions sur les messages système : on ne répond pas à la salle.
        if (id) {
            const actions = document.createElement('div');
            actions.className = 'acts-msg';
            const bRep = document.createElement('button');
            bRep.type = 'button'; bRep.textContent = 'Répondre';
            bRep.addEventListener('click', () => preparerReponse(id, who, text));
            const bReac = document.createElement('button');
            bReac.type = 'button'; bReac.textContent = 'Réagir';
            bReac.addEventListener('click', (e) => { e.stopPropagation(); ouvreChoixReaction(d, id); });
            actions.appendChild(bRep); actions.appendChild(bReac);
            if (state.role === 'moderator' && cfg.chat && cfg.chat.delete) {
                const bSup = document.createElement('button');
                bSup.type = 'button'; bSup.textContent = 'Retirer';
                bSup.addEventListener('click', () => supprimeMessage(id, text));
                actions.appendChild(bSup);
            }
            d.appendChild(actions);
            const deja = (meta && meta.reactions) || {};
            reactionsParMessage.set(id, deja);
            majReactions(d, deja);
        }

        // ⚠️ Mesuré AVANT d'ajouter : une fois le message en place, le fil n'est
        // plus « en bas » et la mesure dirait toujours non. Le fil se recadrait
        // jusqu'ici de FORCE : on remontait lire ce qui s'était dit, un message
        // arrivait, et la lecture était arrachée vers le bas.
        const colle = enBasDuFil(log.scrollHeight, log.scrollTop, log.clientHeight);
        log.appendChild(d);
        // Les siens suivent toujours : on vient de les écrire.
        if (colle || me) descendLeFil();
        else if (id && !(meta && meta.rejoue)) { aLireEnBas++; majALireEnBas(); }

        // Les siens, les messages système et le fil rejoué ne sont pas « non lus ».
        if (id && !me && !(meta && meta.rejoue) && !filVisible()) {
            nonLus++;
            majNonLus();
        }
    }

    // ---- Réactions ----
    // ⚠️ L'état est tenu PAR MESSAGE et non relu du serveur à chaque geste : une
    // réaction doit répondre au doigt. Le serveur a le dernier mot et renvoie le
    // décompte vrai, qui écrase l'optimisme.
    const reactionsParMessage = new Map();

    /** Emojis autorisés, tels que le serveur les connaît. */
    function emojisAutorises() {
        return (cfg.chat && cfg.chat.emojis) || [];
    }

    function majReactions(el, etat) {
        let zone = el.querySelector('.reacts');
        const posees = Object.keys(etat || {}).filter((e) => (etat[e].count || 0) > 0);
        if (!posees.length) { if (zone) zone.remove(); return; }
        if (!zone) { zone = document.createElement('div'); zone.className = 'reacts'; el.appendChild(zone); }
        zone.textContent = '';
        posees.forEach((emoji) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'chip' + (etat[emoji].mine ? ' mine' : '');
            b.textContent = emoji + ' ' + etat[emoji].count;
            b.title = etat[emoji].mine ? 'Retirer ma réaction' : 'Réagir ' + emoji;
            b.addEventListener('click', () => basculeReaction(el.dataset.uid, emoji));
            zone.appendChild(b);
        });
    }

    function ouvreChoixReaction(el, uid) {
        document.querySelectorAll('#visio-room .picker').forEach((p) => p.remove());
        const p = document.createElement('div');
        p.className = 'picker';
        emojisAutorises().forEach((emoji) => {
            const b = document.createElement('button');
            b.type = 'button'; b.textContent = emoji; b.title = 'Réagir ' + emoji;
            b.addEventListener('click', (e) => { e.stopPropagation(); p.remove(); basculeReaction(uid, emoji); });
            p.appendChild(b);
        });
        el.appendChild(p);
        // ⚠️ MESURÉ, et non déduit du rang du message : ce qui décide est le
        // DÉFILEMENT du fil, pas la place du message dedans. Posé au-dessus par
        // défaut - jamais sous le doigt - il ne bascule que dans le cas où le
        // cadre du fil le rognerait.
        const fil = $('chatLog');
        if (fil && p.getBoundingClientRect().top < fil.getBoundingClientRect().top) p.classList.add('bas');
    }

    async function basculeReaction(uid, emoji) {
        const el = uid && $('msg-' + uid);
        if (!el || emojisAutorises().indexOf(emoji) < 0) return;

        const etat = Object.assign({}, reactionsParMessage.get(uid) || {});
        const avant = etat[emoji] || { count: 0, mine: false };
        const mienne = !avant.mine;
        // ⚠️ Une seule réaction par personne : choisir un autre emoji RETIRE le
        // sien. Sans ce retrait ici, l'affichage montrerait deux réactions d'une
        // même personne jusqu'à la réponse du serveur.
        const ancien = mienne ? Object.keys(etat).find((e) => e !== emoji && etat[e].mine) : null;
        if (ancien) etat[ancien] = { count: Math.max(0, (etat[ancien].count || 1) - 1), mine: false };
        etat[emoji] = { count: Math.max(0, (avant.count || 0) + (mienne ? 1 : -1)), mine: mienne };
        reactionsParMessage.set(uid, etat);
        majReactions(el, etat);
        sendData({ type: 'reaction', uid, emoji, on: mienne, replaced: ancien || null });

        if (!cfg.chat) return;
        try {
            const res = await fetch(cfg.chat.react.replace('UID', encodeURIComponent(uid)), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', Accept: 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ emoji }),
            });
            if (!res.ok) return;
            const data = await res.json();
            reactionsParMessage.set(uid, data.reactions || {});
            majReactions(el, data.reactions || {});
        } catch (e) { /* la salle d'abord : le décompte se rattrapera au prochain passage */ }
    }

    /**
     * Réaction de quelqu'un d'autre : on ajuste le décompte, jamais le sien
     * (`mine` reste ce qu'il était - c'est propre à chaque poste).
     */
    function reactionDistante(uid, emoji, active, remplace) {
        const id = idSur(uid);
        const el = id && $('msg-' + id);
        if (!el || emojisAutorises().indexOf(emoji) < 0) return;
        const etat = Object.assign({}, reactionsParMessage.get(id) || {});
        const avant = etat[emoji] || { count: 0, mine: false };
        etat[emoji] = { count: Math.max(0, (avant.count || 0) + (active ? 1 : -1)), mine: avant.mine };
        // La personne avait déjà réagi autrement : sa réaction précédente part.
        if (remplace && emojisAutorises().indexOf(remplace) >= 0 && etat[remplace]) {
            etat[remplace] = { count: Math.max(0, (etat[remplace].count || 1) - 1), mine: etat[remplace].mine };
        }
        reactionsParMessage.set(id, etat);
        majReactions(el, etat);
    }

    // ---- Fil archivé ----
    /**
     * Rejoue le fil à l'arrivée.
     *
     * ⚠️ Sans cela, qui rejoint en cours de séance - ou recharge sa page - trouve
     * une salle muette : le canal de données ne porte que l'instantané, et les
     * questions posées avant lui n'existent plus nulle part.
     */
    async function chargeHistorique() {
        if (!cfg.chat) return;
        try {
            const res = await fetch(cfg.chat.list, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!res.ok) return;
            const data = await res.json();
            const messages = data.messages || [];
            messages.forEach((m) => addChat(m.who, m.body, !!m.mine, {
                id: m.uid, replyTo: m.replyTo, at: m.at, reactions: m.reactions, rejoue: true,
            }));
            // Rien avant soi : le message d'ouverture suffit, un repère
            // d'arrivée collé juste dessous ne dirait rien de plus.
            if (messages.length) {
                const sep = document.createElement('div');
                sep.className = 'chat-sep'; sep.textContent = 'Vous avez rejoint la salle';
                $('chatLog').appendChild(sep);
            }
            // Un fil rejoué s'ouvre sur ce qui vient de se dire, pas sur son début.
            descendLeFil();
        } catch (e) { /* la salle d'abord : un fil injoignable ne doit pas retarder l'entrée */ }
    }

    /** Archive le message qu'on vient d'envoyer (l'instantané est déjà parti). */
    async function persisteMessage(uid, texte, cite) {
        if (!cfg.chat) return;
        try {
            await fetch(cfg.chat.post, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', Accept: 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    uid,
                    body: texte,
                    reply_uid: (cite && cite.id) || null,
                    reply_author: (cite && cite.who) || null,
                    reply_excerpt: (cite && cite.text) || null,
                }),
            });
        } catch (e) { /* le message est parti dans la salle ; l'archive peut manquer un tour */ }
    }

    /**
     * Retire un message du fil, pour tout le monde.
     *
     * ⚠️ Le fil est conservé : un message déplacé resterait là pour toutes les
     * prochaines arrivées. Le serveur revérifie le rang - le bouton n'est qu'une
     * commodité.
     */
    async function supprimeMessage(uid, texte) {
        const ok = await confirme(
            'Retirer ce message du fil',
            'Le message <strong>' + texteEchappe(texteCourt(texte, 80)) + '</strong> disparaîtra pour tout le monde, y compris pour qui rejoindra plus tard. Cette suppression ne se défait pas.',
            'Retirer',
        );
        if (!ok || !cfg.chat || !cfg.chat.delete) return;

        try {
            const res = await fetch(cfg.chat.delete.replace('UID', encodeURIComponent(uid)), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!res.ok) { toast('Suppression refusée'); return; }
            retireMessage(uid);
            sendData({ type: 'chat_removed', uid });
            toast('Message retiré');
        } catch (e) { toast('Suppression indisponible'); }
    }

    function retireMessage(uid) {
        const id = idSur(uid);
        const el = id && $('msg-' + id);
        if (el) el.remove();
        reactionsParMessage.delete(id);
    }

    async function sendChat() {
        const input = $('chatInput');
        const text = (input.value || '').trim();
        if (!text) return;
        // `alpha_num` côté serveur : l'identifiant sert de clé, pas de texte.
        const id = Math.random().toString(36).replace(/[^a-z0-9]/g, '').slice(0, 8) || 'm' + Date.now();
        const cite = reponseA;
        addChat(state.name, text, true, { id, replyTo: cite, at: Date.now() });
        input.value = ''; annuleReponse();
        await sendData({ type: 'chat', text, id, replyTo: cite });
        persisteMessage(id, text, cite);
    }

    // ---- Réactions de salle ----
    /**
     * Un emoji qui monte sur l'écran de présentation, chez tout le monde, avec le
     * nom de qui l'envoie.
     *
     * ⚠️ Ce n'est PAS la réaction du fil (`basculeReaction`) : celle-là se pose sur
     * un message, se compte, et le serveur la conserve. Celle-ci est un
     * applaudissement - elle passe, personne ne la compte, rien ne l'enregistre.
     * C'est voulu : la salle n'a pas à garder trace de qui a applaudi quoi.
     */
    const reac = { dernier: 0, rang: 0, parQui: new Map() };

    function poseBulleReaction(emoji) {
        const couche = $('reacLayer');
        if (!couche) return;
        // ⚠️ Le plafond n'est pas un goût d'affichage : sans lui, une rafale empile
        // des centaines de nœuds animés sur la scène et la page se fige. La plus
        // ancienne cède la place.
        while (couche.children.length >= REACTIONS_MAX) couche.children[0].remove();

        // Colonne ET rangée viennent de la même règle : l'escalier vertical ne
        // suffit pas seul, deux bulles distantes de quatre rangs y retombent.
        const place = placeBulle(reac.rang++);
        const b = document.createElement('div');
        b.className = 'reac-bulle';
        b.style.left = place.gauche + '%';
        b.style.bottom = place.bas + 'px';
        // ⚠️ `textContent` : ce qui arrive vient d'un autre poste. Et il n'y a que
        // l'emoji - c'est la CARTE qui dit de qui il vient, pas la bulle. Un nom
        // accroché à chaque bulle encombrait la scène pour redire ce que la carte
        // montre déjà, à l'endroit où l'œil cherche la personne.
        b.textContent = emoji;
        couche.appendChild(b);

        // ⚠️ Un minuteur, et non `animationend` : quand l'animation ne se joue pas
        // (onglet en arrière-plan, feuille de style non chargée), l'événement
        // n'arrive jamais et la bulle resterait sur la scène indéfiniment.
        setTimeout(() => b.remove(), 4200);
    }

    /**
     * Pose l'emoji sur la carte de qui réagit : c'est ce qui relie la bulle qui
     * monte à une personne.
     *
     * ⚠️ Sur SA carte, pas sur la scène : la grande tuile montre qui PARLE, et y
     * poser la réaction de quelqu'un d'autre désignerait la mauvaise personne.
     */
    function marqueLaCarte(id, emoji) {
        const t = id === 'local' ? $('tileLocal') : $('tile-' + id);
        if (!t) return;
        // ⚠️ Deux réactions coup sur coup : sans annuler le minuteur de la
        // première, il retirerait la marque de la SECONDE, encore toute fraîche.
        clearTimeout(Number(t.dataset.reacTm) || 0);
        t.querySelector('.reac-pop')?.remove();

        const b = document.createElement('span');
        b.className = 'reac-pop';
        b.textContent = emoji;
        t.appendChild(b);
        // La classe recule le nom : sans elle, l'emoji se poserait sur la fin d'un
        // nom long, dans le même coin.
        t.classList.add('a-reagi');
        t.dataset.reacTm = String(setTimeout(() => {
            b.remove(); t.classList.remove('a-reagi'); delete t.dataset.reacTm;
        }, 4200));
    }

    function envoieReaction(emoji) {
        if (!state.connected || !reactionRecevable(emoji, emojisAutorises())) return;
        const n = Date.now();
        if (!peutReagir(reac.dernier, n)) return;
        reac.dernier = n;
        // Affichée tout de suite chez soi : attendre l'aller-retour du serveur
        // donnerait l'impression que le bouton n'a pas pris.
        poseBulleReaction(emoji);
        marqueLaCarte('local', emoji);
        sendData({ type: 'reac_salle', emoji });
    }

    function reactionDeSalle(emoji, id) {
        // ⚠️ Le canal de données est public : sans ce filtre, n'importe quel
        // connecté afficherait en grand, chez tout le monde, le texte de son choix.
        if (!reactionRecevable(emoji, emojisAutorises())) return;
        // ⚠️ Et l'anti-rafale vaut AUSSI ici, par expéditeur : un client modifié ne
        // se prive pas d'un verrou qui ne vivrait que dans sa propre page.
        const n = Date.now();
        const cle = id || '?';
        if (!peutReagir(reac.parQui.get(cle) || 0, n)) return;
        reac.parQui.set(cle, n);
        poseBulleReaction(emoji);
        marqueLaCarte(id, emoji);
    }

    function construitMenuReactions() {
        const m = $('reacMenu');
        if (!m || m.dataset.pret) return m;
        emojisAutorises().forEach((emoji) => {
            const b = document.createElement('button');
            b.type = 'button'; b.textContent = emoji;
            // Le libellé, pas seulement le dessin : un emoji seul n'est pas énonçable.
            b.title = 'Réagir ' + emoji; b.setAttribute('aria-label', 'Réagir ' + emoji);
            b.addEventListener('click', (ev) => { ev.stopPropagation(); fermeMenuReactions(); envoieReaction(emoji); });
            m.appendChild(b);
        });
        m.dataset.pret = '1';

        return m;
    }

    function fermeMenuReactions() {
        $('reacMenu')?.classList.remove('open');
        $('btnReac')?.setAttribute('aria-expanded', 'false');
    }

    // ---- Tableau blanc ----
    /**
     * Une surface de dessin partagée, qui recouvre la scène chez tout le monde.
     *
     * ⚠️ Tout passe par le canal de données : **rien n'est envoyé au serveur**. Le
     * tableau vit le temps de la séance, et on l'emporte en image. Le jour où il
     * devra rejoindre le compte rendu, c'est un point d'entrée à ajouter - pas ce
     * module à refaire.
     *
     * ⚠️ Seul qui anime (modérateur ou présentateur) peut ouvrir, tracer, effacer
     * et refermer : le tableau cache les visages de toute la salle. Le RÉCEPTEUR
     * le vérifie (`ORDRES_RESERVES`), parce que le canal est public.
     */
    const BOARD_CRAYONS = ['#111827', '#1d4ed8', '#dc2626', '#15803d', '#d97706'];
    const BOARD_GOMME = '#ffffff';
    // Le blanc n'est pas dans la palette, mais le récepteur doit l'accepter :
    // c'est la gomme.
    const BOARD_PERMIS = BOARD_CRAYONS.concat([BOARD_GOMME]);
    /** Traits gardés : au-delà, le tableau devient trop lourd à redessiner. */
    const BOARD_TRAITS_MAX = 3000;

    const board = {
        ouvert: false, mode: 'blanc', nom: '', parQui: null, traits: [], enCours: null,
        couleur: BOARD_CRAYONS[0], taille: 4, gomme: false,
        suite: 0, envoyes: 0, tm: 0, trace: false, demandes: new Map(), defaits: [],
    };

    /**
     * Le crayon suit la PAROLE, pas le rôle.
     *
     * ⚠️ Une séance compte plusieurs communicateurs, tous `presenter` pendant
     * toute la séance : avec `canPublish()`, ils pouvaient tous écrire sur le
     * diaporama de celui qui parle. Le crayon va à qui présente - ou qui partage
     * son écran - en ce moment. Le modérateur le garde : il conduit la séance, et
     * sans lui le tableau blanc serait inutilisable tant que personne ne présente.
     */
    function peutTracer() {
        // ⚠️ La MÊME règle que la scène (`celuiQuiPresente`), et pas une seconde
        // écrite à côté : elles ont divergé, et un communicateur affiché
        // « Présentateur » en grand trouvait le bouton Tableau grisé.
        const qui = celuiQuiPresente(people);

        return droitDeTracer(state.role, state.screen, !!(qui && qui.id === 'local'));
    }

    /**
     * Rend la barre et le bouton cohérents avec qui tient le crayon.
     *
     * ⚠️ La parole change EN COURS de séance : sans ce rappel, celui à qui on
     * vient de la donner gardait une barre grisée, et celui qui vient de la rendre
     * continuait d'écrire sur la présentation du suivant.
     */
    function majDroitDeTracer() {
        const z = $('board');
        if (z && board.ouvert) z.classList.toggle('lecture', !peutTracer());
        majTitreTableau();
        syncToolbar();
    }
    function monIdentite() {
        return (room && room.localParticipant && room.localParticipant.identity) || 'local';
    }

    /** Pose le cadre de dessin et accorde le canevas à sa taille réelle. */
    /** L'image actuellement sur la scène, si elle est exploitable. */
    function mediaDeLaScene() {
        const v = $('tileMain')?.querySelector('video');

        return v && v.videoWidth && v.videoHeight ? v : null;
    }

    function cadreDuTableau() {
        const z = $('board');
        const f = $('boardFrame');
        const c = $('boardCanvas');
        if (!z || !f || !c || !board.ouvert) return;
        const r = z.getBoundingClientRect();
        if (board.mode === 'annote') {
            // ⚠️ Le repère est celui de l'IMAGE, pas du tableau : un diaporama 4/3
            // annoté dans un cadre 16/9 verrait toutes ses flèches décalées. Et il se
            // calcule à l'identique partout, puisqu'il ne dépend que de la taille
            // intrinsèque du média - la même pour tous - et de la boîte locale.
            const v = mediaDeLaScene();
            const sur = v ? $('tileMain').getBoundingClientRect() : null;
            const cm = v ? cadreMedia(sur.width, sur.height, v.videoWidth, v.videoHeight) : null;
            if (cm && cm.w) {
                // Le cadre est posé par rapport à la SCÈNE, et la couche la recouvre
                // exactement : les deux rectangles partagent donc leur origine.
                f.style.left = (sur.left - r.left + cm.x) + 'px';
                f.style.top = (sur.top - r.top + cm.y) + 'px';
                f.style.width = cm.w + 'px'; f.style.height = cm.h + 'px';
                accordeLaToile(c, cm.w, cm.h);
                poseLaBarre(r.height - (sur.top - r.top + cm.y + cm.h));

                return;
            }
            // Rien d'exploitable sur la scène : on retombe sur le cadre franc du
            // tableau plutôt que d'annoter un repère inventé.
        }
        // ⚠️ Le titre et la barre d'outils ne FLOTTENT pas au-dessus du tableau :
        // leur place est retirée avant le calcul. Mesuré au harnais - posés en
        // surimpression, ils recouvraient le bas de la zone de dessin, et on ne
        // pouvait pas écrire là où on venait de montrer quelque chose. La barre
        // est mesurée, pas estimée : elle passe sur deux lignes quand la salle
        // est étroite, et elle est absente pour qui regarde sans tracer.
        const haut = 12 + ($('boardWho')?.offsetHeight || 0);
        const bas = 12 + ($('boardTools')?.offsetHeight || 0);
        const cadre = cadreTableau(r.width - 24, Math.max(80, r.height - haut - bas - 12));
        f.style.left = (cadre.x + 12) + 'px'; f.style.top = (cadre.y + haut) + 'px';
        f.style.width = cadre.w + 'px'; f.style.height = cadre.h + 'px';
        accordeLaToile(c, cadre.w, cadre.h);
        poseLaBarre(null);
    }

    /**
     * Place la barre d'outils sous l'image quand il y a la place, par-dessus sinon.
     *
     * ⚠️ En mode tableau, la place de la barre est RETIRÉE du cadre : elle ne
     * recouvre jamais rien. En annotation c'est impossible - le cadre doit coller
     * à l'image, qu'on ne peut pas rétrécir sans la désaligner de ce que voient
     * les autres. Alors on se sert de la bande laissée par le format : un
     * diaporama 4/3 dans une scène large n'en laisse pas en bas, un 16/9 dans une
     * scène haute en laisse. Quand il n'y en a pas, la barre s'efface jusqu'à ce
     * qu'on la vise - mieux vaut une barre discrète qu'une barre qui cache le bas
     * du document qu'on est en train de commenter.
     */
    function poseLaBarre(bande) {
        const o = $('boardTools');
        if (!o) return;
        if (bande === null) { o.style.bottom = ''; o.classList.remove('flottante'); return; }
        const h = o.offsetHeight || 0;
        const tient = bande >= h + 16;
        o.classList.toggle('flottante', !tient);
        o.style.bottom = (tient ? Math.round((bande - h) / 2) : 10) + 'px';
    }

    /**
     * ⚠️ `width`/`height` du canevas, et non sa taille CSS : un canevas étiré par
     * le style rend un trait flou. Et on multiplie par la densité de l'écran,
     * sinon le tableau est pâteux sur un portable récent - c'est précisément
     * l'endroit où on écrit une formule.
     */
    function accordeLaToile(c, largeur, hauteur) {
        const densite = Math.min(2, window.devicePixelRatio || 1);
        c.width = Math.max(1, Math.round(largeur * densite));
        c.height = Math.max(1, Math.round(hauteur * densite));
        redessineTableau();
    }

    function traceLe(g, t, W, H) {
        const pts = t && t.pts;
        if (!pts || !pts.length) return;
        g.strokeStyle = t.c; g.fillStyle = t.c;
        g.lineCap = 'round'; g.lineJoin = 'round';
        // ⚠️ L'épaisseur se rapporte à la LARGEUR DU CADRE, pas au pixel : sinon un
        // trait fin sur un vidéoprojecteur devient un gros trait sur un portable,
        // et le tableau n'est plus le même document pour tout le monde.
        g.lineWidth = Math.max(1, (t.e / 1000) * W);
        if (pts.length === 1) {
            // ⚠️ Un point posé sans glisser ne trace RIEN avec `lineTo` : le segment
            // est de longueur nulle. Il faut un disque.
            g.beginPath(); g.arc(pts[0].x * W, pts[0].y * H, g.lineWidth / 2, 0, Math.PI * 2); g.fill();

            return;
        }
        g.beginPath();
        g.moveTo(pts[0].x * W, pts[0].y * H);
        for (let i = 1; i < pts.length; i += 1) g.lineTo(pts[i].x * W, pts[i].y * H);
        g.stroke();
    }

    /**
     * Ne trace QUE le dernier segment d'un trait en cours.
     *
     * ⚠️ Redessiner tout le tableau à chaque point paraît inoffensif sur un
     * tableau vide. À deux cents traits, chaque mouvement du crayon en redessine
     * deux cents, soixante fois par seconde : la salle entière se met à ramer
     * pendant qu'on écrit. Le redessin complet reste pour ce qui le demande
     * vraiment - redimensionner, effacer, recevoir un trait entier.
     */
    function traceDernierSegment(t) {
        const c = $('boardCanvas');
        if (!c || !c.getContext || !t || !t.pts || t.pts.length < 2) return;
        const g = c.getContext('2d');
        const a = t.pts[t.pts.length - 2];
        const b = t.pts[t.pts.length - 1];
        g.strokeStyle = t.c; g.lineCap = 'round'; g.lineJoin = 'round';
        g.lineWidth = Math.max(1, (t.e / 1000) * c.width);
        g.beginPath();
        g.moveTo(a.x * c.width, a.y * c.height);
        g.lineTo(b.x * c.width, b.y * c.height);
        g.stroke();
    }

    function redessineTableau() {
        const c = $('boardCanvas');
        if (!c || !c.getContext) return;
        const g = c.getContext('2d');
        g.setTransform(1, 0, 0, 1, 0, 0);
        // ⚠️ En annotation, la toile reste TRANSPARENTE : la remplir de blanc
        // masquerait la présentation qu'on vient justement d'annoter. Revers
        // assumé : la gomme blanche y laisse une trace blanche au lieu d'effacer -
        // « Annuler » est l'outil qui défait, et il est à côté.
        if (board.mode === 'annote') g.clearRect(0, 0, c.width, c.height);
        else { g.fillStyle = BOARD_GOMME; g.fillRect(0, 0, c.width, c.height); }
        board.traits.forEach((t) => traceLe(g, t, c.width, c.height));
        if (board.enCours) traceLe(g, board.enCours, c.width, c.height);
    }

    function poseTrait(t) {
        board.traits.push(t);
        // ⚠️ Plafond : chaque trait est redessiné à chaque image du geste en cours.
        // Sans lui, une séance entière de schémas finit par figer la salle.
        if (board.traits.length > BOARD_TRAITS_MAX) board.traits.splice(0, board.traits.length - BOARD_TRAITS_MAX);
    }

    /** Point d'écran → repère du tableau, par le cadre RÉEL du canevas. */
    function pointDuGeste(ev) {
        const r = $('boardCanvas').getBoundingClientRect();

        return pointTableau(ev.clientX, ev.clientY, { x: r.left, y: r.top, w: r.width, h: r.height });
    }

    function pousseTrait(fin) {
        const t = board.enCours;
        if (!t) return;
        const nouveaux = t.pts.slice(board.envoyes);
        board.envoyes = t.pts.length;
        if (!nouveaux.length && !fin) return;
        // ⚠️ Le dernier message porte le trait ENTIER (`net`), pas seulement la
        // fin : il a été allégé à la levée du crayon, et sans ce remplacement les
        // autres postes garderaient la version brute. Un tableau est un document :
        // il doit être identique partout, au point près.
        sendData(fin
            ? { type: 'board_pts', id: t.id, c: t.c, e: t.e, pts: t.pts, net: true, fin: true }
            : { type: 'board_pts', id: t.id, c: t.c, e: t.e, pts: nouveaux });
    }

    function debutTrait(ev) {
        if (!board.ouvert || !peutTracer() || board.trace) return;
        ev.preventDefault();
        try { $('boardCanvas').setPointerCapture(ev.pointerId); } catch (e) { /* ignore */ }
        board.trace = true; board.suite += 1; board.envoyes = 0;
        // ⚠️ Dessiner après avoir annulé abandonne la branche reprise : la rendre
        // ensuite la poserait par-dessus un dessin qui a continué sans elle.
        board.defaits = [];
        board.enCours = {
            id: monIdentite() + '-' + board.suite,
            c: board.gomme ? BOARD_GOMME : board.couleur,
            // La gomme est un crayon blanc plus large : sur un tableau qui est
            // blanc, c'est exactement une gomme - et le dessin reste redessinable.
            e: board.gomme ? Math.min(60, board.taille * 3) : board.taille,
            pts: [pointDuGeste(ev)],
        };
        redessineTableau();
        pousseTrait(false);
    }

    function suiteTrait(ev) {
        if (!board.trace || !board.enCours) return;
        ev.preventDefault();
        const p = pointDuGeste(ev);
        const d = board.enCours.pts[board.enCours.pts.length - 1];
        // ⚠️ Le filtre se pose à la SAISIE : `pointermove` tire des points à moins
        // d'un pixel l'un de l'autre, et un seul trait brut dépasse la taille d'un
        // message du canal.
        if (Math.abs(p.x - d.x) < 0.002 && Math.abs(p.y - d.y) < 0.002) return;
        board.enCours.pts.push(p);
        traceDernierSegment(board.enCours);
        if (!board.tm) board.tm = setTimeout(() => { board.tm = 0; pousseTrait(false); }, 110);
    }

    function finTrait() {
        if (!board.trace) return;
        board.trace = false;
        const t = board.enCours;
        if (!t) return;
        if (board.tm) { clearTimeout(board.tm); board.tm = 0; }
        t.pts = traitAllege(t.pts);
        pousseTrait(true);
        poseTrait(t);
        board.enCours = null; board.envoyes = 0;
        redessineTableau();
        majBoutonsHistorique();
    }

    function recoitTrait(msg) {
        const t = traitRecu(msg, BOARD_PERMIS);
        if (!t) return;
        const connu = board.traits.find((x) => x.id === t.id);
        if (!connu) { poseTrait({ id: t.id, c: t.c, e: t.e, pts: t.pts }); redessineTableau(); return; }
        // Le trait entier remplace ce qu'on avait : c'est la version allégée, celle
        // qui fait foi chez tout le monde.
        if (t.net) { connu.pts = t.pts; redessineTableau(); return; }
        // Quelques points de plus sur un trait en cours : même raison qu'en local,
        // on ne redessine pas deux cents traits pour trois segments.
        t.pts.forEach((p) => { connu.pts.push(p); traceDernierSegment(connu); });
    }

    /**
     * Change de mode, et le dit à la salle.
     *
     * ⚠️ Les traits SONT CONSERVÉS d'un mode à l'autre : ils sont enregistrés en
     * coordonnées du cadre (0..1), pas en pixels. Passer du tableau à
     * l'annotation les replace donc sur l'image - ce qui est rarement ce qu'on
     * veut, mais toujours mieux que de les perdre sans prévenir. « Effacer » reste
     * le geste qui efface, et il se confirme.
     */
    function poseModeTableau(mode, diffuse) {
        board.mode = mode === 'annote' ? 'annote' : 'blanc';
        $('board')?.classList.toggle('annote', board.mode === 'annote');
        // ⚠️ Sur la SALLE et non sur le tableau : c'est la vidéo de la scène qu'il
        // faut passer en `contain`, et elle n'est pas dans la couche de dessin.
        $('visio-room')?.classList.toggle('annote', board.mode === 'annote' && board.ouvert);
        document.querySelectorAll('#boardMode button').forEach((b) => {
            b.classList.toggle('on', b.dataset.mode === board.mode);
        });
        majTitreTableau();
        const img = $('bdSave');
        if (img) img.title = board.mode === 'annote'
            ? 'Enregistrer la présentation annotée'
            : 'Enregistrer le tableau en image';
        cadreDuTableau();
        if (diffuse) sendData({ type: 'board_open', mode: board.mode });
    }

    function majTitreTableau() {
        const el = $('boardWho');
        if (!el) return;
        const quoi = board.mode === 'annote' ? 'Annotation' : 'Tableau blanc';
        el.textContent = peutTracer() || !board.nom ? quoi : quoi + ' - ' + texteCourt(board.nom, 28);
    }

    /**
     * Reprend son dernier trait, et le garde de côté pour pouvoir le rendre.
     *
     * ⚠️ On ne défait QUE les siens (`indexDuDernierTraitDe`) : deux personnes
     * annotent la même présentation, et « Annuler » ne doit pas effacer par
     * surprise la flèche du voisin.
     */
    function annuleMonTrait() {
        const i = indexDuDernierTraitDe(board.traits, monIdentite());
        if (i < 0) return;
        board.defaits.push(board.traits.splice(i, 1)[0]);
        redessineTableau();
        majBoutonsHistorique();
        sendData({ type: 'board_undo' });
    }

    /**
     * Rend le trait repris.
     *
     * ⚠️ Il repart ENTIER (`net`), comme à la levée du crayon : les autres postes
     * l'avaient retiré, il ne suffit pas de leur envoyer la suite.
     */
    function refaisMonTrait() {
        const t = board.defaits.pop();
        if (!t) return;
        poseTrait(t);
        redessineTableau();
        majBoutonsHistorique();
        sendData({ type: 'board_pts', id: t.id, c: t.c, e: t.e, pts: t.pts, net: true, fin: true });
    }

    function majBoutonsHistorique() {
        const u = $('bdUndo');
        const r = $('bdRedo');
        if (u) u.disabled = indexDuDernierTraitDe(board.traits, monIdentite()) < 0;
        if (r) r.disabled = !board.defaits.length;
    }

    function majOutilsTableau() {
        document.querySelectorAll('#boardPalette .bd-c').forEach((b) => {
            b.classList.toggle('on', !board.gomme && b.dataset.couleur === board.couleur);
        });
        $('bdGomme')?.classList.toggle('on', board.gomme);
    }

    /**
     * Ouvre le tableau au nom de `parQui`.
     *
     * ⚠️ **Un tableau appartient à la communication de qui l'a ouvert.** Quand
     * quelqu'un d'autre l'ouvre, on repart d'une surface VIERGE : le schéma du
     * communicateur précédent n'a rien à faire sous l'exposé du suivant. Rouvrir
     * le sien, en revanche, le retrouve - on referme parfois par mégarde, et
     * perdre son tableau pour un clic serait rude.
     *
     * ⚠️ C'est pourquoi `fermeTableau` **ne remet pas** `parQui` à zéro : sans
     * propriétaire mémorisé, le rouvrir reviendrait à l'ouvrir pour la première
     * fois, et l'effacerait.
     */
    function ouvreTableau(parQui, nom, mode, diffuse) {
        if (board.parQui !== parQui) {
            const avait = board.traits.length;
            board.traits = []; board.enCours = null; board.defaits = [];
            // Dit d'où vient la page blanche : sans cela, celui qui regardait un
            // schéma le voit disparaître sans explication.
            if (avait) toast('Nouveau tableau');
        }
        board.parQui = parQui;
        board.ouvert = true;
        board.nom = nom || '';
        const z = $('board');
        z.classList.add('on');
        // ⚠️ La lecture seule n'est pas qu'un affichage : les écouteurs de dessin
        // vérifient aussi `peutTracer()`. Masquer la barre sans cela laisserait
        // tracer à qui ouvre la console.
        z.classList.toggle('lecture', !peutTracer());
        $('btnBoard')?.classList.add('on');
        majOutilsTableau();
        poseModeTableau(mode, false);
        majBoutonsHistorique();
        if (diffuse) sendData({ type: 'board_open', mode: board.mode });
    }

    function fermeTableau(diffuse) {
        board.ouvert = false; board.trace = false; board.enCours = null;
        $('board')?.classList.remove('on');
        $('btnBoard')?.classList.remove('on');
        // La scène retrouve son cadrage : `contain` imposé par l'annotation
        // laisserait des bandes noires autour des visages.
        $('visio-room')?.classList.remove('annote');
        // ⚠️ Les traits SURVIVENT à la fermeture, et le propriétaire AUSSI :
        // rouvrir le sien doit retrouver le schéma. C'est l'ouverture par
        // quelqu'un d'autre qui fait table rase, pas la fermeture.
        if (diffuse) sendData({ type: 'board_close' });
    }

    /**
     * Répond à qui demande le tableau en arrivant.
     *
     * ⚠️ C'est l'ARRIVANT qui demande, et non le propriétaire qui devine. Envoyé
     * dès l'annonce d'arrivée, l'instantané peut partir avant que l'arrivant
     * n'écoute : il regarderait alors une scène vide pendant que les autres
     * commentent un schéma. Celui qui demande, lui, est prêt par construction -
     * et cela rattrape aussi les reconnexions, qui ne sont pas des arrivées.
     *
     * ⚠️ Seul celui qui a OUVERT le tableau répond : à trois présentateurs, trois
     * instantanés partiraient pour la même personne. Revers assumé : si celui-ci
     * a quitté la salle, un arrivant ne reçoit plus le tableau - ceux qui sont
     * déjà là le gardent.
     */
    function repondAuTableau(a) {
        if (!board.ouvert || board.parQui !== 'local' || !a) return;
        // ⚠️ Le canal est public : une demande répétée ferait repartir tout le
        // tableau à chaque fois. Même garde-fou que pour les réactions.
        const n = Date.now();
        if (!peutReagir(board.demandes.get(a) || 0, n, 3000)) return;
        board.demandes.set(a, n);

        const cible = [a];
        sendData({ type: 'board_open', mode: board.mode }, cible);
        lotsDeTraits(board.traits, 400).forEach((lot) => {
            sendData({ type: 'board_state', traits: lot }, cible);
        });
    }

    /**
     * Enregistre en image, pour le compte rendu.
     *
     * ⚠️ En annotation, exporter la seule couche de dessin rendrait des traits
     * flottant sur du vide - c'est la PRÉSENTATION ANNOTÉE qu'on veut emporter.
     * On compose donc l'image de la scène, puis les traits par-dessus. Le montage
     * est exact parce que les deux partagent le même repère : le cadre du média.
     *
     * ⚠️ La vidéo est exportée à sa taille INTRINSÈQUE et non à celle du canevas :
     * sur un petit écran, le canevas fait quelques centaines de pixels, et la
     * capture d'un diaporama y serait illisible. Les traits, eux, sont en
     * coordonnées relatives : ils se remettent à l'échelle sans rien perdre.
     *
     * ⚠️ Une piste WebRTC ne « salit » pas le canevas (ce n'est pas une image
     * d'une autre origine) : `toBlob` reste autorisé. Un échec éventuel est
     * rattrapé plutôt que laissé filer sans rien dire.
     */
    function enregistreTableau() {
        const toile = $('boardCanvas');
        if (!toile || !toile.toBlob) return;
        const video = board.mode === 'annote' ? mediaDeLaScene() : null;

        let source = toile;
        if (video) {
            const montage = document.createElement('canvas');
            montage.width = video.videoWidth;
            montage.height = video.videoHeight;
            const g = montage.getContext('2d');
            try {
                g.drawImage(video, 0, 0, montage.width, montage.height);
                g.drawImage(toile, 0, 0, montage.width, montage.height);
                source = montage;
            } catch (e) {
                toast('La présentation n’a pas pu être capturée : seules les annotations sont enregistrées.');
            }
        }

        const nom = (board.mode === 'annote' ? 'annotation-' : 'tableau-')
            + new Date().toISOString().slice(0, 16).replace(/[:T]/g, '-') + '.png';
        try {
            source.toBlob((blob) => {
                if (!blob) { toast('Enregistrement impossible'); return; }
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url; a.download = nom;
                document.body.appendChild(a); a.click(); a.remove();
                // ⚠️ Sans cela, l'image reste en mémoire pour toute la séance - et on
                // en enregistre plusieurs.
                setTimeout(() => URL.revokeObjectURL(url), 1000);
            }, 'image/png');
        } catch (e) { toast('Enregistrement impossible'); }
    }

    // ---- Toolbar ----
    function syncToolbar() {
        const pub = canPublish();
        $('btnMic').disabled = !state.connected;
        $('btnCam').disabled = !state.connected || !pub;
        $('btnScreen').disabled = !state.connected || !pub;
        $('btnBg').disabled = !state.connected || !pub;
        $('btnHand').disabled = !state.connected;
        // Réagir ne demande ni micro ni caméra : c'est le seul geste que
        // l'assistance peut faire, et il lui reste ouvert.
        $('btnReac').disabled = !state.connected;
        // ⚠️ Même règle que le crayon : qui ne présente pas n'ouvre pas le
        // tableau par-dessus la présentation de celui qui parle.
        $('btnBoard').disabled = !state.connected || !peutTracer();
        $('btnMic').classList.toggle('muted', !state.mic); $('btnMic').classList.toggle('active', state.mic);
        $('btnCam').classList.toggle('muted', !state.cam); $('btnCam').classList.toggle('active', state.cam);
        $('btnScreen').classList.toggle('active', state.screen);
        $('btnHand').classList.toggle('on', state.hand);
        $('btnRec').classList.toggle('active', state.recording);
        const sp = $('btnSpeaker'); sp.classList.toggle('muted', !state.speakerOn); sp.classList.toggle('active', state.speakerOn);
    }
    function updateModUi() {
        const tab = $('tabComms'); if (tab) tab.style.display = state.role === 'moderator' ? '' : 'none';
        // Emporter le fil pour le compte rendu : c'est au modérateur de le faire.
        const outils = $('chatTools');
        if (outils) outils.classList.toggle('on', state.role === 'moderator' && !!(cfg.chat && cfg.chat.export));
        const lien = $('chatExport');
        if (lien && cfg.chat && cfg.chat.export) lien.href = cfg.chat.export;
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
        updateLayoutClasses(); updateFilmstripCount(); layoutGrid();
    }

    /**
     * Choisit le nombre de COLONNES de la mosaïque : celui qui donne les plus
     * grandes tuiles sans trop s'éloigner du 16/9.
     *
     * ⚠️ Laissé à `auto-fit`, le CSS alignait quatre personnes sur une seule
     * rangée : des tuiles hautes et étroites, où une image 16/9 cadrée en
     * `cover` était rognée jusqu'au nez. C'est la place disponible qui doit
     * décider de la grille, pas une largeur minimale écrite à l'avance.
     */
    function layoutGrid() {
        const grid = $('stageGrid');
        if (!grid || state.speakerLayout) return;

        const tiles = [].slice.call(grid.children)
            .filter((t) => t.classList.contains('tile') && getComputedStyle(t).display !== 'none');
        const n = tiles.length;
        if (!n) return;

        const { cols, rows } = grilleOptimale(n, grid.clientWidth, grid.clientHeight);
        grid.style.setProperty('--cols', cols);
        grid.style.setProperty('--rows', rows);
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

    function renderPresence(data) {
        const list = $('presenceList'), counts = $('presenceCounts');
        if (!list) return;

        const c = data.counts || {};
        counts.textContent = '';
        [['Présents', c.present], ['Absents', c.absent], ['Excusés', c.excused], ['Attendus', c.expected]]
            .forEach(([libelle, valeur]) => {
                const s = elt('span', '', libelle + ' ');
                s.appendChild(elt('b', '', valeur || 0));
                counts.appendChild(s);
            });
        // ⚠️ Les pointages de la visio comptent DÉJÀ dans « Présents » : rien n'est
        // perdu si le modérateur ne confirme jamais. Ce compteur dit seulement ce
        // qu'il n'a pas encore regardé lui-même, et disparaît quand il n'y a plus
        // rien à regarder.
        const aVoir = compteAConfirmer(presenceRows);
        if (aVoir) {
            const s = elt('span', 'pr-avoir', 'À confirmer ');
            s.appendChild(elt('b', '', aVoir));
            counts.appendChild(s);
        }

        list.textContent = '';
        if (!presenceRows.length) {
            list.appendChild(elt('div', 'pr-empty', 'Aucune communication programmée dans cette séance : il n’y a personne à pointer.'));

            return;
        }

        presenceRows.forEach((r) => {
            const item = elt('div', 'pr-item');
            item.appendChild(elt('div', 'nm', r.name));

            const meta = elt('div', 'meta');
            meta.appendChild(document.createTextNode(r.role));
            // ⚠️ « En salle » n'est affiché que si LiveKit a su répondre : on ne
            // laisse pas croire que personne n'est là quand on ne sait pas.
            if (data.roomKnown && r.inRoom) {
                meta.appendChild(document.createTextNode(' · '));
                meta.appendChild(elt('span', 'in-room', '● En salle'));
            }
            if (r.auto && r.status) {
                meta.appendChild(document.createTextNode(' · '));
                meta.appendChild(elt('span', 'pr-avoir', 'entré en visio - à confirmer'));
            }
            item.appendChild(meta);

            if (data.canWrite) {
                const acts = elt('div', 'pr-acts');
                ['present', 'absent', 'excused'].forEach((st) => {
                    const pose = r.status === st;
                    // ⚠️ `data-st` porte la COULEUR du verdict (vert / rouge / or) :
                    // sans lui, les règles `.on[data-st=…]` ne s'accrochaient à rien et
                    // le bouton actif ne se distinguait pas des deux autres. L'état
                    // était enregistré sans jamais se voir.
                    const b = elt('button', pose ? (r.auto ? 'on auto' : 'on') : '', presenceLabel(st));
                    b.type = 'button';
                    b.dataset.st = st;
                    // Mi-chemin assumé : ni « posé », ni « rien ». C'est exactement ce
                    // qu'est un pointage que la machine a déduit.
                    b.setAttribute('aria-pressed', pose ? (r.auto ? 'mixed' : 'true') : 'false');
                    if (pose && r.auto) b.title = 'Confirmer la présence relevée par la visio';
                    b.addEventListener('click', () => {
                        const g = gestePointage(r.status, r.auto, st);
                        markPresence(r.id, g.statut, g.geste);
                    });
                    acts.appendChild(b);
                });
                item.appendChild(acts);
            }

            list.appendChild(item);
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

    async function markPresence(id, status, geste) {
        if (!cfg.presence) return;
        try {
            const res = await fetch(cfg.presence.mark, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', Accept: 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ participant_id: id, status: status }),
            });
            if (!res.ok) { toast('Pointage refusé'); return; }
            toast(geste === 'confirme' ? 'Présence confirmée'
                : status ? 'Présence enregistrée' : 'Pointage retiré');
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
        // Qualité décidée par l'édition (onglet Visioconférence de sa fiche).
        const video = profilVideo(cfg.quality);
        room = new LK.Room({
            adaptiveStream: true, dynacast: true,
            // Le périphérique retenu à l'écran d'entrée, s'il y en a eu un : sinon
            // LiveKit reprend celui que le navigateur juge par défaut, qui n'est pas
            // forcément celui qu'on vient de vérifier.
            videoCaptureDefaults: {
                resolution: { width: video.largeur, height: video.hauteur, frameRate: video.images },
                facingMode: 'user',
                deviceId: entree.camId || undefined,
            },
            audioCaptureDefaults: {
                echoCancellation: true, noiseSuppression: true, autoGainControl: true,
                deviceId: entree.micId || undefined,
            },
            publishDefaults: {
                dtx: true, red: true,
                // ⚠️ Ce sont les couches BASSES, publiées à côté de la capture : elles
                // servent les petites vignettes et les connexions faibles. Les garder
                // est ce qui rend une couche haute abordable.
                videoSimulcastLayers: LK.VideoPresets ? [LK.VideoPresets.h180, LK.VideoPresets.h360] : undefined,
                // ⚠️ Un débit explicite : laissé au SDK, il se calait sur l'ancienne
                // définition et une capture plus fine serait sortie en bouillie.
                videoEncoding: { maxBitrate: video.debit, maxFramerate: video.images },
                // ⚠️ Sans cette préférence, l'encodeur s'obstine sur un seul critère
                // quand la liaison faiblit : il tient la définition et hache le
                // mouvement, ou l'inverse. `balanced` le laisse céder sur les deux -
                // une image qui se dégrade vaut mieux qu'une salle qu'on perd.
                degradationPreference: 'balanced',
                videoCodec: 'vp8',
            },
        });

        room
            .on(LK.RoomEvent.LocalTrackPublished, (pub) => {
                if (pub.source === LK.Track.Source.ScreenShare && pub.track) { state.screen = true; onLocalScreenTrack(pub.track); majDroitDeTracer(); }
                if (pub.source === LK.Track.Source.Camera && pub.track) {
                    const lt = $('tileLocal'); clearMedia(lt); attachTrack(pub.track, lt);
                    if (blurState.mode === 'blur') applyBackground('blur');
                    if (canPublish() && state.screen) setPipCamera(pub.track);
                    else if (canPublish() && !state.screen && state.speakerLayout) focusPresenter({ name: state.name, identity: 'local' }, pub.track);
                }
            })
            .on(LK.RoomEvent.LocalTrackUnpublished, (pub) => {
                if (pub.source === LK.Track.Source.ScreenShare) { state.screen = false; destroyPip(true); majDroitDeTracer(); }
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
                    // ⚠️ `clearVideo` et non `clearMedia` : le son de la personne est
                    // attaché à la MÊME tuile, et partait avec l'image.
                    if (tile) clearVideo(tile);
                    const main = $('tileMain'); const screenUp = main && main.classList.contains('has-screen');
                    if (screenUp && pe && (pe.role === 'presenter' || pe.isFloor || pe.screen)) destroyPip(false);
                    else if (!screenUp && pe && (pe.role === 'presenter' || pe.isFloor)) showPresenterInfo(participant.name || participant.identity, 'Caméra désactivée');
                } else if (pub.source === LK.Track.Source.ScreenShare) { if (pe) pe.screen = false; restoreStage(participant); refreshPeople(); }
            })
            // ⚠️ Couper sa caméra n'est PAS se désabonner : sans ces deux
            // événements, la tuile gardait une image gelée (ou un rectangle noir
            // par-dessus l'avatar) et la scène ne se mettait jamais à jour -
            // elle continuait d'annoncer une caméra qui venait de s'éteindre, ou
            // l'inverse.
            .on(LK.RoomEvent.TrackPublished, () => refreshPeople())
            .on(LK.RoomEvent.TrackUnpublished, () => refreshPeople())
            .on(LK.RoomEvent.TrackMuted, (pub, participant) => onCameraToggle(pub, participant))
            .on(LK.RoomEvent.TrackUnmuted, (pub, participant) => onCameraToggle(pub, participant))
            // ⚠️ Ces deux événements n'étaient pas écoutés. Sans le premier, rien ne
            // dit qui parle - en mosaïque, on coupe quelqu'un sans le savoir. Sans le
            // second, une image qui se figeait ne disait pas si c'était l'autre qui
            // décrochait ou soi.
            .on(LK.RoomEvent.ActiveSpeakersChanged, (speakers) => majParleurs(speakers))
            .on(LK.RoomEvent.ConnectionQualityChanged, (qualite, participant) => majReseau(participant, qualite))
            .on(LK.RoomEvent.ParticipantConnected, (p) => {
                const annonce = metadonnee(p);
                people.push({ name: p.name || p.identity, role: annonce.role, accountId: annonce.accountId, hand: false, screen: false, id: p.identity });
                refreshPeople(); refreshComms(); ensureRemoteTile(p); toast((p.name || p.identity) + ' a rejoint');
                // Sans cela, l'arrivant attend jusqu'à cinq secondes avant de savoir
                // depuis combien de temps parle la personne qu'il voit.
                if (state.role === 'moderator' && chrono.ordre !== null) diffuseChrono();
            })
            .on(LK.RoomEvent.ParticipantDisconnected, (p) => {
                people = people.filter((x) => x.id !== p.identity); refreshPeople(); refreshComms();
                const tile = $('tile-' + p.identity); if (tile) tile.remove(); updateFilmstripCount(); layoutGrid();
                reac.parQui.delete(p.identity);
                board.demandes.delete(p.identity);
            })
            // ⚠️ `Disconnected` signifie que le SDK a RENONCÉ (ses propres essais
            // ont échoué). Sans porte de sortie, il ne restait qu'à recharger la
            // page - en pleine séance, et en perdant le fil affiché.
            .on(LK.RoomEvent.Disconnected, () => {
                state.connected = false;
                // Un liseré vert oublié affirmerait que quelqu'un parle dans une salle
                // dont on vient d'être éjecté.
                majParleurs([]);
                // Et un chronomètre figé affirmerait qu'on tient encore le temps d'une
                // séance qu'on ne suit plus.
                arreteChrono();
                setConnPill('err', 'Déconnecté');
                montreReprise(true, 'La connexion à la salle a été perdue.');
                syncToolbar();
            })
            .on(LK.RoomEvent.Reconnecting, () => { state.reprise = true; setConnPill('warn', 'Reconnexion…'); })
            .on(LK.RoomEvent.Reconnected, () => { state.reprise = false; setConnPill('ok', 'Connecté'); montreReprise(false); })
            .on(LK.RoomEvent.DataReceived, (payload, participant) => onData(payload, participant));

        try {
            await room.connect(cfg.url, cfg.token);
            state.connected = true; setStatus(''); setConnPill('ok', 'Connecté');
            adopteParticipantsPresents();
            const main = $('tileMain');
            if (main && !main.classList.contains('has-screen') && state.speakerLayout) showCurrentPresenterInStage();
            refreshPeople(); refreshComms();
            // ⚠️ La salle ouvrait ici caméra ET micro d'office, sans rien demander ni
            // montrer. C'est le même résultat par défaut, à ceci près qu'il a été vu
            // et qu'il était révocable AVANT d'entrer (cf. ecranDEntree).
            await appliqueChoixDEntree();
            syncToolbar();
        } catch (e) {
            setStatus('Connexion impossible : ' + ((e && e.message) || e)); setConnPill('err', 'Échec'); room = null;
        }
    }

    /**
     * Ce que le serveur annonce d'un participant : son rôle et son compte.
     *
     * ⚠️ Seule source digne de confiance pour ces deux informations - elle est
     * signée dans le jeton, et le participant n'a pas le droit de la modifier
     * (`canUpdateOwnMetadata` n'est jamais accordé).
     */
    /**
     * Rôle ANNONCÉ par le serveur pour ce participant, lu dans la métadonnée de
     * son jeton.
     *
     * ⚠️ Surtout pas `people[].role` : c'est précisément ce que les messages
     * `mod_role` modifient. On authentifierait un ordre avec ce que l'ordre
     * précédent a écrit. La métadonnée, elle, est signée dans le jeton et le
     * participant n'a pas le droit de la changer (`canUpdateOwnMetadata` n'est
     * jamais accordé).
     */
    function roleAnnonce(participant) {
        return metadonnee(participant).role;
    }

    /** Même règle, à partir de l'émetteur plutôt que de son rang. */
    function ordreRecevable(type, participant) {
        return ordreAutorise(type, roleAnnonce(participant));
    }

    /**
     * Prend la salle telle qu'elle est : qui s'y trouve déjà, et ce que chacun
     * diffuse.
     *
     * ⚠️ Indispensable APRÈS UNE RECONNEXION autant qu'à l'entrée. Les événements
     * d'arrivée ne rejouent pas pour les personnes déjà présentes : sans cette
     * reprise d'état, la salle revenait vide de tout le monde sauf de soi - ce que
     * le premier essai de reconnexion a montré.
     */
    function adopteParticipantsPresents() {
        if (!room) return;
        room.remoteParticipants.forEach((p) => {
            const annonce = metadonnee(p);
            if (!people.some((x) => x.id === p.identity)) {
                people.push({ name: p.name || p.identity, role: annonce.role, accountId: annonce.accountId, hand: false, screen: false, id: p.identity });
            }
            ensureRemoteTile(p);
            p.trackPublications.forEach((pub) => {
                if (!pub.track) return;
                if (pub.source === LK.Track.Source.ScreenShare) {
                    const pe = people.find((x) => x.id === p.identity);
                    if (pe) { pe.screen = true; pe.isFloor = true; }
                    showScreenOnPresenter(pub.track, (p.name || p.identity) + ' (écran)');
                    const cam = p.getTrackPublication(LK.Track.Source.Camera);
                    setPipCamera(cam && cam.track ? cam.track : null);
                } else {
                    attachTrack(pub.track, ensureRemoteTile(p));
                }
            });
        });
        // ⚠️ Ici et pas ailleurs : c'est le seul endroit qui passe à l'entrée ET
        // après une reconnexion. Un tableau ouvert pendant qu'on était coupé doit
        // revenir, sinon la salle commente un schéma qu'on ne voit plus.
        if (room.remoteParticipants.size) sendData({ type: 'board_ask' });
    }

    // ---- Reprise après coupure ----
    /**
     * Demande un jeton frais au serveur.
     *
     * ⚠️ Se reconnecter avec le jeton d'origine échouerait précisément dans le cas
     * qui compte : une séance longue, où il a expiré (2 h par défaut). Le serveur
     * recalcule les droits au passage - un rôle retiré entre-temps ne revient pas
     * par la porte du renouvellement.
     */
    async function nouveauJeton() {
        if (!cfg.tokenUrl) return null;
        try {
            const res = await fetch(cfg.tokenUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!res.ok) return null;
            const data = await res.json();

            return data.token || null;
        } catch (e) {
            return null;
        }
    }

    function montreReprise(montrer, message) {
        const bar = $('reconnectBar');
        if (!bar) return;
        if (message) $('reconnectText').textContent = message;
        bar.classList.toggle('on', !!montrer);
    }

    /** Rejoint la salle après une coupure, sans recharger la page. */
    async function reconnecte() {
        if (!room) return;
        const b = $('btnReconnect');
        if (b) { b.disabled = true; b.textContent = 'Reconnexion…'; }
        setStatus('Reconnexion à la salle…');

        try {
            // ⚠️ On repart propre : les vignettes d'avant la coupure ne
            // correspondent plus à personne tant que les événements n'ont pas
            // rejoué, et on afficherait des gens qui ne sont plus là.
            document.querySelectorAll('#visio-room .tile[id^="tile-"]').forEach((t) => t.remove());
            people = people.filter((x) => x.id === 'local');

            await room.connect(cfg.url, (await nouveauJeton()) || cfg.token);
            state.connected = true;
            setConnPill('ok', 'Connecté');
            montreReprise(false);

            // Les pistes locales ne repartent pas toutes seules : on rétablit ce
            // qui était ouvert avant la coupure.
            if (canPublish()) {
                try {
                    if (state.cam) await room.localParticipant.setCameraEnabled(true);
                    if (state.mic) await room.localParticipant.setMicrophoneEnabled(true);
                } catch (e) { /* le navigateur redemandera l'autorisation */ }
            }
            adopteParticipantsPresents();
            if (state.speakerLayout) showCurrentPresenterInStage();
            updateFilmstripCount(); refreshPeople(); refreshComms(); layoutGrid();
            toast('Reconnecté à la salle');
        } catch (e) {
            setConnPill('err', 'Déconnecté');
            montreReprise(true, 'La reconnexion a échoué. Réessayez, ou rechargez la page.');
        }

        setStatus('');
        if (b) { b.disabled = false; b.textContent = 'Reconnecter'; }
        syncToolbar();
    }

    function onData(payload, participant) {
        let msg; try { msg = JSON.parse(new TextDecoder().decode(payload)); } catch (e) { return; }
        // Un ordre forgé est ignoré en silence : le signaler apprendrait surtout
        // à celui qui essaie que la salle le voit.
        if (!ordreRecevable(msg.type, participant)) return;
        const fromId = participant && participant.identity;
        if (msg.type === 'chat') addChat((participant && (participant.name || participant.identity)) || 'Participant', msg.text, false, { id: msg.id, replyTo: msg.replyTo, at: Date.now() });
        else if (msg.type === 'reaction') reactionDistante(msg.uid, msg.emoji, !!msg.on, msg.replaced || null);
        else if (msg.type === 'reac_salle') reactionDeSalle(msg.emoji, fromId);
        else if (msg.type === 'board_ask') repondAuTableau(fromId);
        else if (msg.type === 'board_open') ouvreTableau(fromId, (participant && participant.name) || '', msg.mode, false);
        else if (msg.type === 'board_close') fermeTableau(false);
        else if (msg.type === 'board_pts') recoitTrait(msg);
        else if (msg.type === 'board_state') (Array.isArray(msg.traits) ? msg.traits : []).forEach((t) => recoitTrait(Object.assign({ net: true }, t)));
        else if (msg.type === 'board_clear') {
            // ⚠️ La pile des traits repris part avec : rendre un trait sur un tableau
            // que quelqu'un vient de vider ferait réapparaître un fantôme.
            board.traits = []; board.enCours = null; board.defaits = [];
            redessineTableau(); majBoutonsHistorique();
        }
        else if (msg.type === 'board_undo') {
            // Chaque trait porte l'identité de son auteur : on ne défait que le sien.
            const i = indexDuDernierTraitDe(board.traits, fromId);
            if (i >= 0) { board.traits.splice(i, 1); redessineTableau(); }
        }
        else if (msg.type === 'chat_removed') retireMessage(msg.uid);
        else if (msg.type === 'hand') {
            const pe = people.find((x) => x.id === fromId);
            if (pe) {
                const rang = leveLaMain(pe, !!msg.up);
                refreshPeople();
                // Le panneau peut être fermé, ou sur un autre onglet : on le dit.
                if (msg.up) toast(pe.name + ' lève la main (n° ' + rang + ')');
            }
        }
        else if (msg.type === 'mod_unmute') { const me = people.find((x) => x.id === 'local'); if (me) me.micGranted = true; ouvreMonMicro((msg.from || 'Modérateur') + ' vous autorise le micro'); }
        else if (msg.type === 'mod_mute') { if (room) room.localParticipant.setMicrophoneEnabled(false).then(() => { state.mic = false; syncToolbar(); refreshPeople(); toast('Le modérateur a coupé votre micro'); }).catch(() => {}); }
        else if (msg.type === 'mod_hand_down') { state.hand = false; leveLaMain(people.find((x) => x.id === 'local'), false); refreshPeople(); syncToolbar(); }
        else if (msg.type === 'screen_takeover') { const my = room && room.localParticipant.identity; if (msg.identity && msg.identity !== my && state.screen) { room.localParticipant.setScreenShareEnabled(false).catch(() => {}); state.screen = false; destroyPip(false); majDroitDeTracer(); toast((msg.by || 'Un participant') + ' a pris le partage'); } }
        else if (msg.type === 'recording') { state.recording = !!msg.on; $('recBadge').classList.toggle('on', state.recording); syncToolbar(); if (msg.on) toast('Enregistrement en cours'); }
        else if (msg.type === 'engagement') echangesABouge();
        else if (msg.type === 'floor_time') recoitChrono(msg);
        else if (msg.type === 'comm_done') { scheduledComms.forEach((c) => { if (String(c.order) === String(msg.order)) c.done = true; }); refreshComms(); }
        else if (msg.type === 'pip_visibility') { if (msg.visible === false) { pipVisible = false; destroyPip(false); } else pipVisible = true; }
        else if (msg.type === 'mod_role') {
            const nr = msg.role === 'presenter' ? 'presenter' : 'audience'; state.role = nr;
            // ⚠️ `isFloor` et pas seulement le rôle : au début d'une séance, TOUS les
            // auteurs portent `presenter` (le serveur le donne à chaque communicateur).
            // Ce qui désigne celui qui présente MAINTENANT, c'est la parole donnée.
            const me = people.find((x) => x.id === 'local');
            if (me) { me.role = nr; me.isFloor = nr === 'presenter'; }
            $('localRole').textContent = roleLabel(nr); rootEl.setAttribute('data-role', nr);
            updateModUi(); refreshPeople(); syncToolbar();
            toast(nr === 'presenter' ? 'Vous êtes nommé communicateur' : 'Rôle communicateur retiré');
            if (nr === 'audience' && room) { room.localParticipant.setCameraEnabled(false).catch(() => {}); room.localParticipant.setScreenShareEnabled(false).catch(() => {}); state.cam = false; state.screen = false; destroyPip(); }
            // ⚠️ Prendre la parole avec le micro coupé, c'est la salle entière qui
            // regarde quelqu'un parler en silence - et personne ne sait d'où vient
            // la panne. Le micro s'ouvre donc AVEC la présentation ; la personne
            // reste libre de le recouper juste après, c'est son micro.
            if (nr === 'presenter' && room) ouvreMonMicro('Votre micro a été activé pour votre présentation');
            // Le crayon suit la parole : la barre du tableau s'ouvre ou se ferme ici.
            majDroitDeTracer();
        }
    }

    /** Ouvre son propre micro (promotion en présentation, autorisation du modérateur). */
    async function ouvreMonMicro(message) {
        if (!room || state.mic) return;
        if (!await ensureMedia(false, true)) return;
        try {
            await room.localParticipant.setMicrophoneEnabled(true);
            state.mic = true;
            syncToolbar(); refreshPeople();
            if (message) toast(message);
        } catch (e) { toast('Micro indisponible'); }
    }

    // ---- Largeur du panneau latéral ----
    // ⚠️ Réglable, et RETENUE : ce panneau porte trois usages très différents (le
    // chat, la liste des participants, le pointage de présence avec nom et
    // institution). Aucune largeur ne les sert tous, et personne ne veut la régler
    // à chaque séance.
    const PANNEAU_DEFAUT = 400, PANNEAU_MIN = 360;

    /** Au-delà de la moitié de la salle, le panneau mangerait la scène. */
    function panneauMax() {
        const l = $('mainLayout')?.clientWidth || 1200;

        return Math.max(PANNEAU_MIN, Math.min(680, Math.round(l * 0.5)));
    }
    function poseLargeurPanneau(px, retient) {
        const l = Math.round(Math.max(PANNEAU_MIN, Math.min(panneauMax(), px || PANNEAU_DEFAUT)));
        rootEl.style.setProperty('--vside', l + 'px');
        $('sideGrip')?.setAttribute('aria-valuenow', String(l));
        if (retient) { try { localStorage.setItem('ukc.sideWidth', String(l)); } catch (e) { /* sans mémoire, tant pis */ } }
        layoutGrid();

        return l;
    }
    function largeurRetenue() {
        try {
            const v = parseInt(localStorage.getItem('ukc.sideWidth') || '', 10);
            if (v >= PANNEAU_MIN) return v;
        } catch (e) { /* idem */ }

        return PANNEAU_DEFAUT;
    }
    function largeurCourante() {
        return parseInt(getComputedStyle(rootEl).getPropertyValue('--vside'), 10) || PANNEAU_DEFAUT;
    }
    (function poigneePanneau() {
        const g = $('sideGrip');
        if (!g) return;
        let actif = false;
        g.addEventListener('pointerdown', (e) => {
            actif = true; g.classList.add('on'); g.setPointerCapture(e.pointerId); e.preventDefault();
        });
        g.addEventListener('pointermove', (e) => {
            if (!actif) return;
            // Le BORD du panneau suit le pointeur : la largeur est ce qui reste à
            // droite de la main, pas un déplacement cumulé.
            poseLargeurPanneau($('mainLayout').getBoundingClientRect().right - e.clientX, false);
        });
        // ⚠️ `pointercancel` aussi : un geste interrompu laissait la poignée allumée.
        ['pointerup', 'pointercancel'].forEach((ev) => g.addEventListener(ev, () => {
            if (!actif) return;
            actif = false; g.classList.remove('on');
            poseLargeurPanneau(largeurCourante(), true);
        }));
        g.addEventListener('dblclick', () => { poseLargeurPanneau(PANNEAU_DEFAUT, true); toast('Largeur du panneau rétablie'); });
        // Une salle se pilote aussi au clavier : gauche élargit, droite rétrécit.
        g.addEventListener('keydown', (e) => {
            if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
            e.preventDefault();
            poseLargeurPanneau(largeurCourante() + (e.key === 'ArrowLeft' ? 24 : -24), true);
        });
    })();
    poseLargeurPanneau(largeurRetenue(), false);
    // ⚠️ On repart de la largeur RETENUE et non de la largeur courante : rétrécir
    // la fenêtre borne le panneau, mais ne doit pas effacer le choix de la personne
    // quand la place revient.
    window.addEventListener('resize', () => poseLargeurPanneau(largeurRetenue(), false));

    // ---- Sortie de la salle ----
    // ---- Temps de parole ----
    // ⚠️ Le compte est tenu par le MODÉRATEUR et diffusé. On n'envoie pas une heure
    // de départ mais un NOMBRE DE SECONDES, réémis toutes les cinq secondes : deux
    // machines n'ont pas la même horloge, et un écart de quelques minutes entre
    // elles afficherait un dépassement imaginaire. Celui qui arrive en cours de
    // communication est ainsi à l'heure sans qu'on ait à lui raconter le passé.
    const chrono = { ordre: null, nom: '', minutes: null, secondes: 0, depuis: 0, tic: null, envoi: null };

    /**
     * Qui voit le chronomètre : le modérateur, et celui qui a la parole.
     *
     * ⚠️ Pas toute la salle. Un dépassement regarde celui qui préside et celui qui
     * parle - les deux qui peuvent agir dessus ; l'afficher à quarante personnes en
     * ferait l'affaire de tout le monde, et une pression sur l'orateur.
     */
    function chronoVisible() {
        if (chrono.ordre === null) return false;
        if (state.role === 'moderator') return true;
        const moi = people.find((x) => x.id === 'local');

        return !!(moi && (moi.isFloor || moi.role === 'presenter'));
    }

    function majChrono() {
        const el = $('chrono');
        if (!el) return;
        if (!chronoVisible()) { el.className = 'v-chrono'; return; }
        const e = etatChrono(chrono.secondes, chrono.minutes);
        $('chronoQui').textContent = texteCourt(chrono.nom, 28);
        $('chronoT').textContent = e.texte;
        el.className = 'v-chrono on ' + e.classe;
    }

    function arreteChrono(rafraichir) {
        if (chrono.tic) clearInterval(chrono.tic);
        if (chrono.envoi) clearInterval(chrono.envoi);
        chrono.tic = null; chrono.envoi = null; chrono.ordre = null; chrono.secondes = 0;
        if (rafraichir !== false) majChrono();
    }

    /** Fin du temps de parole, annoncée à ceux qui l'affichaient. */
    function diffuseFinChrono() { sendData({ type: 'floor_time', order: null }); }

    function diffuseChrono() {
        // Le nom et la durée ne voyagent pas : chacun les a déjà dans le programme
        // de la séance. Seul le compte circule.
        if (chrono.ordre !== null) sendData({ type: 'floor_time', order: chrono.ordre, secondes: chrono.secondes });
    }

    function demarreChrono(ordre) {
        if (ordre === null || ordre === undefined) { arreteChrono(); return; }
        arreteChrono(false);
        const c = scheduledComms.find((x) => String(x.order) === String(ordre));
        chrono.ordre = ordre;
        chrono.nom = (c && c.name) || '';
        chrono.minutes = (c && c.minutes) || null;
        chrono.secondes = 0;
        chrono.depuis = Date.now();
        // Sur une horloge locale, et non par incréments : un onglet mis en veille
        // ralentit ses minuteries, et le compte prendrait du retard sur la séance.
        chrono.tic = setInterval(() => {
            chrono.secondes = Math.floor((Date.now() - chrono.depuis) / 1000);
            majChrono();
        }, 1000);
        diffuseChrono();
        chrono.envoi = setInterval(diffuseChrono, 5000);
        majChrono();
    }

    /** Compte reçu du modérateur : il fait foi, on ne fait que l'égrener entre deux. */
    function recoitChrono(msg) {
        if (state.role === 'moderator') return;
        if (msg.order === null || msg.order === undefined) { arreteChrono(); return; }
        const c = scheduledComms.find((x) => String(x.order) === String(msg.order));
        if (String(chrono.ordre) !== String(msg.order)) {
            arreteChrono(false);
            chrono.ordre = msg.order;
            chrono.tic = setInterval(() => { chrono.secondes++; majChrono(); }, 1000);
        }
        chrono.nom = (c && c.name) || '';
        chrono.minutes = (c && c.minutes) || null;
        chrono.secondes = Math.max(0, Math.floor(Number(msg.secondes) || 0));
        majChrono();
    }

    /** Numéro de la communication tenue par cette personne, s'il y en a une. */
    function ordreDe(identity) {
        const c = scheduledComms.find((x) => x.identity === identity);

        return c ? c.order : null;
    }

    // ---- Écran d'entrée ----
    // ⚠️ On tombait dans la salle, et la demande d'autorisation du navigateur
    // arrivait par-dessus une séance déjà commencée. C'est ici que se règlent
    // presque tous les ennuis de première fois.
    const CLE = 'geves.visio.';
    function pref(cle, defaut) {
        // Un navigateur en navigation privée refuse l'écriture ET la lecture :
        // l'écran doit marcher sans mémoire, simplement sans se souvenir.
        try { const v = localStorage.getItem(CLE + cle); return v === null ? defaut : v; } catch (e) { return defaut; }
    }
    function posePref(cle, valeur) { try { localStorage.setItem(CLE + cle, String(valeur)); } catch (e) {} }

    const entree = { cam: false, mic: false, camId: '', micId: '', flux: null, audio: null, boucle: 0 };

    function messageEntree(txt) {
        const el = $('pjMsg');
        if (!el) return;
        el.textContent = txt || '';
        el.classList.toggle('on', !!txt);
    }

    function fermeApercu() {
        // ⚠️ Impérativement AVANT de connecter : une caméra laissée ouverte par
        // l'aperçu reste PRISE, et LiveKit ne peut plus l'ouvrir - l'entrée se solde
        // alors par une tuile noire et un « périphérique déjà utilisé ».
        if (entree.flux) {
            entree.flux.getTracks().forEach((t) => { try { t.stop(); } catch (e) {} });
            entree.flux = null;
        }
        if (entree.boucle) { cancelAnimationFrame(entree.boucle); entree.boucle = 0; }
        if (entree.audio) { try { entree.audio.close(); } catch (e) {} entree.audio = null; }
        const v = $('pjVideo'); if (v) v.srcObject = null;
    }

    function majEntree() {
        $('pjCam')?.setAttribute('aria-pressed', entree.cam ? 'true' : 'false');
        $('pjMic')?.setAttribute('aria-pressed', entree.mic ? 'true' : 'false');
        const image = !!(entree.flux && entree.flux.getVideoTracks().length);
        $('pjVue')?.classList.toggle('on', entree.cam && image);
        if (!entree.mic) { const b = $('pjNiveau'); if (b) b.style.width = '0'; }
    }

    /**
     * Niveau du micro.
     *
     * ⚠️ C'est la seule PREUVE qu'il entend quelque chose : un micro muet ne se voit
     * pas, il se constate - on parle, et la barre bouge. Sans elle, l'écran d'entrée
     * ne ferait que répéter le nom d'un périphérique.
     */
    function mesureLeNiveau() {
        const barre = $('pjNiveau');
        const Contexte = window.AudioContext || window.webkitAudioContext;
        if (!barre || !Contexte || !entree.flux || !entree.flux.getAudioTracks().length) return;
        try {
            entree.audio = new Contexte();
            const analyse = entree.audio.createAnalyser();
            analyse.fftSize = 512;
            entree.audio.createMediaStreamSource(entree.flux).connect(analyse);
            const donnees = new Uint8Array(analyse.frequencyBinCount);
            const tour = () => {
                analyse.getByteTimeDomainData(donnees);
                let somme = 0;
                for (let i = 0; i < donnees.length; i++) { const d = (donnees[i] - 128) / 128; somme += d * d; }
                barre.style.width = Math.round(Math.min(1, Math.sqrt(somme / donnees.length) * 4) * 100) + '%';
                entree.boucle = requestAnimationFrame(tour);
            };
            tour();
        } catch (e) { /* sans niveau, l'écran reste utile */ }
    }

    async function listeDesAppareils() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) return;
        let appareils = [];
        try { appareils = await navigator.mediaDevices.enumerateDevices(); } catch (e) { return; }
        const remplit = (select, genre, choisi, mot) => {
            if (!select) return;
            select.textContent = '';
            appareils.filter((a) => a.kind === genre).forEach((a, i) => {
                const o = document.createElement('option');
                o.value = a.deviceId;
                // ⚠️ Sans autorisation accordée, les étiquettes sont VIDES : on numérote
                // plutôt que de dérouler une liste de lignes blanches.
                o.textContent = a.label || mot + ' ' + (i + 1);
                if (a.deviceId === choisi) o.selected = true;
                select.appendChild(o);
            });
            select.disabled = !select.children.length;
        };
        remplit($('pjCamSel'), 'videoinput', entree.camId, 'Caméra');
        remplit($('pjMicSel'), 'audioinput', entree.micId, 'Micro');
    }

    async function ouvreApercu() {
        fermeApercu();
        if (!entree.cam && !entree.mic) { majEntree(); await listeDesAppareils(); return; }
        if (!window.isSecureContext || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            messageEntree('La visioconférence nécessite une connexion sécurisée (HTTPS).');
            majEntree();

            return;
        }
        try {
            entree.flux = await navigator.mediaDevices.getUserMedia({
                video: entree.cam ? (entree.camId ? { deviceId: { exact: entree.camId } } : true) : false,
                audio: entree.mic ? (entree.micId ? { deviceId: { exact: entree.micId } } : true) : false,
            });
            // L'autorisation obtenue ici vaut pour la salle : la redemander au premier
            // clic sur « Micro » rouvrirait une fenêtre du navigateur pour rien.
            if (entree.cam) mediaPerm.cam = true;
            if (entree.mic) mediaPerm.mic = true;
            const v = $('pjVideo'); if (v) v.srcObject = entree.flux;
            messageEntree('');
            if (entree.mic) mesureLeNiveau();
        } catch (e) {
            messageEntree(messageMedia(e));
        }
        await listeDesAppareils();
        majEntree();
    }

    /**
     * L'écran d'entrée, s'il a lieu d'être.
     *
     * ⚠️ Seulement pour qui diffuse. Un auditeur ne publie ni son ni image : lui
     * dresser un mur avant la porte, ce serait le retarder pour rien.
     */
    /**
     * Ce que le navigateur dit d'une autorisation, SANS la demander.
     *
     * ⚠️ `prompt` n'est pas `granted` : c'est tout l'intérêt. Un navigateur qui
     * n'a pas encore demandé, ou qui a oublié, doit repasser par l'écran d'entrée
     * - sinon la fenêtre d'autorisation s'ouvre par-dessus la séance, ce que cet
     * écran existe précisément pour éviter. Firefox ne connaît pas ces noms : on
     * rend `prompt`, et l'écran s'affiche. Mieux vaut un écran de trop que la
     * mauvaise surprise.
     */
    async function etatPermission(nom) {
        if (!navigator.permissions || !navigator.permissions.query) return 'prompt';
        try { return (await navigator.permissions.query({ name: nom })).state; }
        catch (e) { return 'prompt'; }
    }

    async function ecranDEntree() {
        const ec = $('prejoin'), bouton = $('pjEntrer');
        // ⚠️ Sans le bouton, l'attente ci-dessous ne finirait JAMAIS : la salle ne se
        // connecterait pas, sur un écran vide. Mieux vaut entrer sans vérifier que
        // de ne pas entrer du tout.
        if (!ec || !bouton || !canPublish()) return;
        // ⚠️ Caméra et micro ouverts par défaut : c'est EXACTEMENT ce que la salle
        // faisait d'office jusqu'ici. L'écran d'entrée ne change pas ce qui se
        // passe, il le montre et le rend révocable avant que cela n'arrive -
        // inverser ce défaut ferait entrer muet un communicateur habitué à être
        // entendu. Ensuite, c'est son dernier choix qui vaut.
        entree.cam = pref('cam', '1') === '1';
        entree.mic = pref('mic', '1') === '1';
        entree.camId = pref('camId', '');
        entree.micId = pref('micId', '');
        // ⚠️ Déjà passé par ici POUR CETTE SALLE, et le navigateur accorde encore ce
        // qu'on lui demande : on entre directement. Reposer l'écran à chaque
        // actualisation et à chaque reconnexion en faisait un péage - franchi sans
        // rien lire, et justement au pire moment : quand on vient de perdre la
        // salle en pleine séance.
        if (entreeDirecte(
            pref('vu:' + roomName, '') === '1',
            entree.cam, entree.mic,
            await etatPermission('camera'), await etatPermission('microphone'),
        )) return;
        ec.classList.add('on');
        majEntree();
        await ouvreApercu();
        bouton.focus();
        await new Promise((resoud) => { bouton.addEventListener('click', resoud, { once: true }); });
        fermeApercu();
        ec.classList.remove('on');
        // Franchi une fois pour cette salle : la prochaine venue ira droit au but,
        // tant que les autorisations tiennent.
        posePref('vu:' + roomName, '1');
    }

    /** Ce qui a été choisi avant d'entrer s'applique une fois la salle rejointe. */
    async function appliqueChoixDEntree() {
        if (!room || !canPublish()) return;
        if (entree.cam) {
            try { state.cam = true; await room.localParticipant.setCameraEnabled(true); }
            catch (e) { state.cam = false; toast('Caméra indisponible'); }
        }
        if (entree.mic) {
            try {
                state.mic = true;
                await room.localParticipant.setMicrophoneEnabled(true);
                const moi = people.find((x) => x.id === 'local'); if (moi) moi.micOn = true;
            } catch (e) { state.mic = false; toast('Micro indisponible'); }
        }
        refreshPeople(); syncToolbar();
    }

    // ---- Échanges : questions votées et sondages ----
    // ⚠️ Ils vivaient sur une AUTRE PAGE, atteinte par un lien de la barre du haut :
    // poser une question obligeait à quitter la visio, donc à cesser de voir et
    // d'entendre ce dont on voulait parler.
    //
    // ⚠️ Puis ils ont été un ONGLET du panneau latéral, et c'était trop peu : une
    // colonne de 400px partagée avec le fil, les personnes et la présence ne tient
    // pas une file de questions ET des sondages en cours. Ils ont leur surface et
    // leur bouton - c'est une activité de la séance, pas un onglet qu'il faut
    // d'abord penser à ouvrir.
    //
    // ⚠️ Rien n'est réécrit ici : ce sont les points d'entrée JSON de la page
    // autonome, et c'est le SERVEUR qui dit qui peut poser, voter et modérer. Le
    // bouton n'est qu'une commodité - le masquer n'a jamais protégé personne.
    const echanges = {
        questions: [], sondages: [], peutPoster: false, tic: null, dernier: 0,
        ouvert: false, filtre: 'toutes', vus: 0,
    };

    const FLECHE_HAUT = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">'
        + '<path d="M12 5l7 8h-4v6h-6v-6H5l7-8z"/></svg>';

    function echangesActifs() {
        return !!(cfg.engagement && cfg.engagement.questions);
    }

    function ouvreEchanges(ouvrir) {
        echanges.ouvert = !!ouvrir;
        $('ech')?.classList.toggle('on', echanges.ouvert);
        $('btnEch')?.setAttribute('aria-expanded', echanges.ouvert ? 'true' : 'false');
        if (!echanges.ouvert) return;
        // ⚠️ Ce qu'on vient de voir cesse d'être « nouveau » : une pastille qui
        // clignote encore après qu'on a ouvert ne veut plus rien dire.
        $('btnEch')?.classList.remove('neuf');
        echanges.vus = compteEnAttente(echanges.questions);
        chargeEchanges();
        $('engInput')?.focus();
    }

    async function chargeEchanges() {
        if (!echangesActifs()) return;
        echanges.dernier = Date.now();
        const lire = async (url) => {
            try {
                const res = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });

                return res.ok ? await res.json() : null;
            } catch (e) { return null; }
        };
        const [q, p] = await Promise.all([lire(cfg.engagement.questions), lire(cfg.engagement.polls)]);
        if (q) { echanges.questions = q.questions || []; echanges.peutPoster = !!q.canPost; }
        if (p) { echanges.sondages = p.polls || []; }
        rendEchanges();
    }

    /**
     * Rafraîchissement déclenché par la salle.
     *
     * ⚠️ Bridé : l'annonce passe par le canal de données, que n'importe qui peut
     * émettre. Sans ce frein, un client modifié ferait relire la liste à toute la
     * salle en boucle.
     */
    function echangesABouge() {
        if (!echangesActifs()) return;
        if (Date.now() - echanges.dernier < 2000) return;
        chargeEchanges();
    }

    function majCompteEchanges() {
        const badge = $('echCount');
        if (!badge) return;
        // Ce qui attend une réponse : c'est la file de la séance, pas l'archive.
        const n = compteEnAttente(echanges.questions);
        badge.textContent = String(n);
        badge.classList.toggle('on', n > 0);
        // ⚠️ Le signal ne vaut que fermé : une question arrivée pendant qu'on
        // regardait la file n'a pas à faire clignoter un bouton déjà ouvert.
        if (!echanges.ouvert && n > echanges.vus) $('btnEch')?.classList.add('neuf');
        if (echanges.ouvert) echanges.vus = n;
        // Une question retirée fait redescendre le repère : sinon la suivante
        // n'annoncerait plus rien.
        if (n < echanges.vus) echanges.vus = n;
    }

    function rendEchanges() {
        const zoneP = $('engPolls');
        const zoneQ = $('engList');
        if (!zoneP || !zoneQ) return;
        const estMod = state.role === 'moderator';

        // --- Sondages ---
        zoneP.textContent = '';
        if (estMod) zoneP.appendChild(brouillonDeSondage());
        if (!echanges.sondages.length) {
            zoneP.appendChild(elt('div', 'eng-vide', estMod
                ? 'Aucun sondage. Lancez-en un : la salle répond sans quitter la séance.'
                : 'Aucun sondage en cours.'));
        }
        echanges.sondages.forEach((p) => zoneP.appendChild(carteSondage(p, estMod)));

        // --- Questions ---
        zoneQ.textContent = '';
        const vues = questionsFiltrees(echanges.questions, echanges.filtre);
        if (!vues.length) {
            // ⚠️ Un vide filtré n'est pas un vide : dire « aucune question » alors
            // qu'il y en a trente derrière un filtre, c'est la salle qui ment.
            zoneQ.appendChild(elt('div', 'eng-vide', echanges.questions.length
                ? 'Aucune question ne correspond à ce filtre.'
                : 'Aucune question pour le moment. Posez la vôtre ci-dessous.'));
        }
        vues.forEach((q) => zoneQ.appendChild(ligneQuestion(q, estMod)));

        document.querySelectorAll('#echFiltres button').forEach((b) => {
            b.classList.toggle('on', b.dataset.f === echanges.filtre);
        });
        $('engForm').style.display = echanges.peutPoster ? '' : 'none';
        $('engNote').style.display = echanges.peutPoster ? 'none' : '';
        majCompteEchanges();
    }

    function carteSondage(p, estMod) {
        const c = elt('div', 'poll');
        const tete = elt('div');
        tete.style.cssText = 'display:flex;align-items:flex-start;justify-content:space-between;gap:8px;';
        tete.appendChild(elt('div', 'q', p.question));
        if (p.status !== 'open') tete.appendChild(elt('span', 'clos', 'Clos'));
        c.appendChild(tete);

        const opts = elt('div', 'opts');
        (p.options || []).forEach((libelle, i) => {
            const n = (p.counts && p.counts[i]) || 0;
            const part = p.total ? Math.round((n / p.total) * 100) : 0;
            const b = elt('button', 'opt' + (p.mine === i ? ' mine' : ''));
            b.type = 'button';
            // La barre est le FOND du bouton : le résultat ne se lit pas ailleurs.
            const barre = elt('span', 'bar');
            barre.style.width = part + '%';
            b.appendChild(barre);
            b.appendChild(elt('span', 'lb', libelle));
            b.appendChild(elt('span', 'pc', p.total ? part + '%' : ''));
            b.disabled = p.status !== 'open' || !echanges.peutPoster;
            b.addEventListener('click', () => voteSondage(p.id, i));
            opts.appendChild(b);
        });
        c.appendChild(opts);

        const pied = elt('div', 'pied');
        pied.appendChild(elt('span', 'tot', p.total === 1 ? '1 réponse' : p.total + ' réponses'));
        if (estMod) {
            const b = elt('button', '', p.status === 'open' ? 'Clore' : 'Rouvrir');
            b.type = 'button';
            b.className = 'v-btn ghost sm';
            b.addEventListener('click', () => basculeSondage(p.id));
            pied.appendChild(b);
        }
        c.appendChild(pied);

        return c;
    }

    function ligneQuestion(q, estMod) {
        const l = elt('div', 'qst' + (q.status === 'answered' ? ' answered' : ''));

        const vote = elt('button', 'vote' + (q.voted ? ' on' : ''));
        vote.type = 'button';
        vote.appendChild(icone(FLECHE_HAUT, '', q.voted ? 'Retirer mon vote' : 'Soutenir cette question'));
        vote.appendChild(elt('span', '', String(q.votes)));
        vote.disabled = !echanges.peutPoster;
        vote.addEventListener('click', () => voteQuestion(q.id));
        l.appendChild(vote);

        const corps = elt('div', 'corps');
        corps.appendChild(elt('div', 'txt', q.body));
        const sig = elt('div', 'sig');
        sig.appendChild(elt('span', '', q.author));
        if (q.status === 'answered') sig.appendChild(elt('span', 'st answered', 'Répondue'));
        if (q.status === 'hidden') sig.appendChild(elt('span', 'st hidden', 'Masquée'));
        corps.appendChild(sig);

        if (estMod) {
            const acts = elt('div', 'acts');
            const bouton = (libelle, statut) => {
                const b = elt('button', '', libelle);
                b.type = 'button';
                b.addEventListener('click', () => modereQuestion(q.id, statut));

                return b;
            };
            if (q.status !== 'answered') acts.appendChild(bouton('✓ Répondue', 'answered'));
            if (q.status !== 'hidden') acts.appendChild(bouton('Masquer', 'hidden'));
            if (q.status !== 'pending') acts.appendChild(bouton('Remettre en attente', 'pending'));
            corps.appendChild(acts);
        }
        l.appendChild(corps);

        return l;
    }

    /** Brouillon de sondage du modérateur : replié tant qu'il n'en lance pas un. */
    function brouillonDeSondage() {
        const zone = elt('div');
        const ouvrir = elt('button', 'v-btn ghost sm', '+ Nouveau sondage');
        ouvrir.type = 'button';
        const boite = elt('div', 'poll-new');

        const question = document.createElement('input');
        question.type = 'text'; question.maxLength = 300; question.placeholder = 'Question posée à la salle';
        boite.appendChild(question);

        const champs = elt('div');
        champs.style.cssText = 'display:flex;flex-direction:column;gap:6px;';
        const ajouteOption = () => {
            if (champs.children.length >= 6) return;
            const ligne = elt('div', 'opt-ligne');
            const o = document.createElement('input');
            o.type = 'text'; o.maxLength = 150; o.placeholder = 'Option ' + (champs.children.length + 1);
            ligne.appendChild(o);
            const retirer = elt('button', '', '×');
            retirer.type = 'button'; retirer.title = 'Retirer cette option';
            retirer.addEventListener('click', () => { if (champs.children.length > 2) ligne.remove(); });
            ligne.appendChild(retirer);
            champs.appendChild(ligne);
        };
        ajouteOption(); ajouteOption();
        boite.appendChild(champs);

        const acts = elt('div', 'acts');
        const plus = elt('button', 'v-btn ghost sm', '+ Option');
        plus.type = 'button';
        plus.addEventListener('click', ajouteOption);
        const lancer = elt('button', 'v-btn blue sm', 'Lancer');
        lancer.type = 'button';
        lancer.addEventListener('click', async () => {
            const options = [].slice.call(champs.querySelectorAll('input')).map((i) => i.value.trim()).filter(Boolean);
            if (!question.value.trim() || options.length < 2) { toast('Une question et deux options au moins'); return; }
            await creeSondage(question.value.trim(), options);
            boite.classList.remove('on');
        });
        acts.appendChild(plus); acts.appendChild(lancer);
        boite.appendChild(acts);

        ouvrir.addEventListener('click', () => {
            boite.classList.toggle('on');
            if (boite.classList.contains('on')) question.focus();
        });
        zone.appendChild(ouvrir);
        zone.appendChild(boite);

        return zone;
    }

    /** Envoi commun : le serveur tranche, on relit ensuite ce qu'il a retenu. */
    async function posteEchange(url, corps) {
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', Accept: 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(corps || {}),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) { toast(data.error || data.message || 'Action refusée'); return false; }
            echanges.dernier = 0;
            await chargeEchanges();
            // Les autres salles relisent aussitôt, au lieu d'attendre leur tour.
            sendData({ type: 'engagement' });

            return true;
        } catch (e) { toast('Échanges indisponibles'); return false; }
    }

    async function poseQuestion() {
        const champ = $('engInput');
        const texte = (champ.value || '').trim();
        if (!texte) return;
        champ.value = '';
        if (!await posteEchange(cfg.engagement.ask, { body: texte })) champ.value = texte;
    }

    function voteQuestion(id) {
        posteEchange(cfg.engagement.question + '/' + encodeURIComponent(id) + '/vote');
    }

    function modereQuestion(id, statut) {
        posteEchange(cfg.engagement.question + '/' + encodeURIComponent(id) + '/moderation', { status: statut });
    }

    function creeSondage(question, options) {
        return posteEchange(cfg.engagement.createPoll, { question, options });
    }

    function voteSondage(id, choix) {
        posteEchange(cfg.engagement.poll + '/' + encodeURIComponent(id) + '/vote', { choice: choix });
    }

    function basculeSondage(id) {
        posteEchange(cfg.engagement.poll + '/' + encodeURIComponent(id) + '/etat');
    }

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
        state.hand = !state.hand;
        leveLaMain(people.find((x) => x.id === 'local'), state.hand);
        refreshPeople(); syncToolbar();
        // Son propre rang est déjà sur sa vignette : le répéter ici n'ajoute rien.
        toast(state.hand ? 'Main levée' : 'Main baissée');
        await sendData({ type: 'hand', up: state.hand });
    });
    $('btnReac').addEventListener('click', (e) => {
        e.stopPropagation();
        const m = construitMenuReactions();
        if (!m) return;
        // Les feuilles de la barre partagent la même place au-dessus d'elle :
        // deux ouvertes en même temps se recouvrent.
        ['bgMenu', 'shareMenu', 'volPanel', 'moreSheet'].forEach((id) => $(id)?.classList.remove('open'));
        const ouvert = m.classList.toggle('open');
        $('btnReac').setAttribute('aria-expanded', ouvert ? 'true' : 'false');
        if (ouvert) m.querySelector('button')?.focus();
    });
    $('btnBoard').addEventListener('click', async () => {
        if (board.ouvert && board.parQui === 'local') {
            fermeTableau(true);
            toast('Tableau fermé');

            return;
        }
        if (board.ouvert) {
            // ⚠️ Le tableau ouvert est celui de quelqu'un d'autre. L'ouvrir pour sa
            // propre communication EFFACE son schéma chez tout le monde, et rien ne
            // le rend : on le demande avant. (Son auteur peut l'emporter en image
            // tant qu'il est à l'écran.)
            const ok = !board.traits.length || await confirme(
                'Nouveau tableau',
                'Le tableau en cours est celui de <strong>' + texteEchappe(board.nom || 'quelqu’un d’autre')
                    + '</strong>. L’ouvrir pour votre communication effacera son contenu '
                    + 'pour tous les participants.',
                'Nouveau tableau',
            );
            if (!ok) return;
        }
        // ⚠️ Deviné, pas imposé : quand un écran est partagé, c'est presque
        // toujours pour l'annoter qu'on ouvre le crayon. Le bouton de mode reste
        // là pour dire le contraire.
        const surScene = $('tileMain')?.classList.contains('has-screen') && mediaDeLaScene();
        ouvreTableau('local', '', surScene ? 'annote' : 'blanc', true);
        toast(surScene ? 'Annotation de la présentation' : 'Tableau ouvert');
    });
    (function outilsDuTableau() {
        const palette = $('boardPalette');
        BOARD_CRAYONS.forEach((couleur) => {
            const b = document.createElement('button');
            b.type = 'button'; b.className = 'bd-c'; b.dataset.couleur = couleur;
            b.style.background = couleur;
            b.title = 'Crayon'; b.setAttribute('aria-label', 'Crayon ' + couleur);
            b.addEventListener('click', () => { board.couleur = couleur; board.gomme = false; majOutilsTableau(); });
            palette?.appendChild(b);
        });
        majOutilsTableau();
        document.querySelectorAll('#boardMode button').forEach((b) => {
            b.addEventListener('click', () => poseModeTableau(b.dataset.mode, true));
        });
        $('bdGomme')?.addEventListener('click', () => { board.gomme = !board.gomme; majOutilsTableau(); });
        $('bdTaille')?.addEventListener('input', (e) => { board.taille = Math.max(1, Number(e.target.value) || 4); });
        $('bdUndo')?.addEventListener('click', annuleMonTrait);
        $('bdRedo')?.addEventListener('click', refaisMonTrait);
        $('bdClear')?.addEventListener('click', async () => {
            // ⚠️ Effacer emporte le travail de TOUT LE MONDE, et rien ne le rend.
            if (!board.traits.length) return;
            if (!await confirme('Effacer le tableau', 'Le tableau sera vidé pour tous les participants. Rien ne permet de le retrouver.', 'Effacer')) return;
            board.traits = []; board.enCours = null; board.defaits = [];
            redessineTableau(); majBoutonsHistorique();
            sendData({ type: 'board_clear' });
        });
        $('bdSave')?.addEventListener('click', enregistreTableau);
        $('bdClose')?.addEventListener('click', () => fermeTableau(board.parQui === 'local'));
        const toile = $('boardCanvas');
        toile?.addEventListener('pointerdown', debutTrait);
        toile?.addEventListener('pointermove', suiteTrait);
        // ⚠️ `pointercancel` autant que `pointerup` : un appel entrant ou un geste
        // du système annulent le pointeur sans jamais le relever - le trait
        // resterait ouvert pour toujours.
        ['pointerup', 'pointercancel'].forEach((e) => toile?.addEventListener(e, finTrait));
        // ⚠️ Et PAS `pointerleave` : atteindre le bord du cadre couperait le trait
        // en pleine écriture. Le relâchement hors du canevas est rattrapé par la
        // fenêtre, pour le cas où la capture du pointeur a échoué.
        window.addEventListener('pointerup', finTrait);
    })();
    $('btnLayout').addEventListener('click', () => {
        state.speakerLayout = !state.speakerLayout;
        // ⚠️ On ne touche plus à l'état du bandeau ici : en mosaïque il est masqué
        // (les vignettes sont sur la scène), et au retour en vue présentateur il
        // était REFERMÉ de force - y compris pour qui venait de l'ouvrir. Son
        // ouverture n'appartient qu'à son propre bouton.
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
    // Écran d'entrée : chaque geste rouvre l'aperçu, c'est lui qui fait foi.
    $('pjCam')?.addEventListener('click', async () => {
        entree.cam = !entree.cam; posePref('cam', entree.cam ? 1 : 0); await ouvreApercu();
    });
    $('pjMic')?.addEventListener('click', async () => {
        entree.mic = !entree.mic; posePref('mic', entree.mic ? 1 : 0); await ouvreApercu();
    });
    $('pjCamSel')?.addEventListener('change', async (e) => {
        entree.camId = e.target.value; posePref('camId', entree.camId); await ouvreApercu();
    });
    $('pjMicSel')?.addEventListener('change', async (e) => {
        entree.micId = e.target.value; posePref('micId', entree.micId); await ouvreApercu();
    });
    $('chatVersBas')?.addEventListener('click', descendLeFil);
    // Revenu en bas de lui-même : le bandeau n'a plus rien à annoncer.
    $('chatLog')?.addEventListener('scroll', () => {
        const log = $('chatLog');
        if (aLireEnBas && enBasDuFil(log.scrollHeight, log.scrollTop, log.clientHeight)) {
            aLireEnBas = 0; majALireEnBas();
        }
    });
    $('chatInput').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { sendChat(); return; }
        // Échap abandonne la réponse sans vider ce qu'on a déjà écrit.
        if (e.key === 'Escape' && reponseA) { e.stopPropagation(); annuleReponse(); }
    });
    $('chatReplyCancel')?.addEventListener('click', annuleReponse);
    // Un clic ailleurs referme le choix d'emoji resté ouvert.
    document.addEventListener('click', () => document.querySelectorAll('#visio-room .picker').forEach((p) => p.remove()));
    document.querySelectorAll('#visio-room .side-tabs button').forEach((b) => b.addEventListener('click', () => {
        document.querySelectorAll('#visio-room .side-tabs button').forEach((x) => x.classList.remove('active')); b.classList.add('active');
        const id = b.getAttribute('data-side');
        $('panelChat').classList.toggle('active', id === 'chat'); $('panelPeople').classList.toggle('active', id === 'people');
        const pc = $('panelComms'); if (pc) pc.classList.toggle('active', id === 'comms'); if (id === 'comms') refreshComms();
        const pp = $('panelPresence'); if (pp) pp.classList.toggle('active', id === 'presence');
        if (id === 'presence') loadPresence();
        if (id === 'chat') filLu();
    }));
    // ---- Panneau latéral : colonne sur grand écran, tiroir en dessous de 900px ----
    // ⚠️ L'ancienne bascule se contentait d'inverser deux classes. Sur téléphone, le
    // tiroir partait donc OUVERT, par-dessus les visages, et il fallait appuyer
    // deux fois pour le refermer. Un tiroir qui recouvre la scène doit partir
    // fermé, et se refermer d'un appui sur le voile ou sur sa croix.
    const ETROIT = matchMedia('(max-width: 900px)');
    function basculePanneau(ouvrir) {
        const m = $('mainLayout'), s = $('sidePanel');
        if (!m || !s) return;
        const ouvert = typeof ouvrir === 'boolean' ? ouvrir : !s.classList.contains('open');
        s.classList.toggle('open', ouvert);
        m.classList.toggle('side-closed', !ouvert);
        $('sideScrim')?.classList.toggle('on', ouvert && ETROIT.matches);
        $('btnToggleSide')?.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
        if (ouvert && $('panelChat')?.classList.contains('active')) filLu();
        layoutGrid();
    }
    $('btnToggleSide').addEventListener('click', () => basculePanneau());
    $('btnCloseSide')?.addEventListener('click', () => basculePanneau(false));
    $('sideScrim')?.addEventListener('click', () => basculePanneau(false));
    basculePanneau(!ETROIT.matches);
    ETROIT.addEventListener('change', () => basculePanneau(!ETROIT.matches));

    // ---- Barre d'outils compacte : les commandes secondaires passent en feuille ----
    // ⚠️ Elles ne sont pas RECOPIÉES dans la feuille, elles y sont DÉPLACÉES. Un
    // second jeu de boutons voudrait dire deux écouteurs et deux états à tenir
    // d'accord (micro coupé, enregistrement en cours, partage actif) : la salle
    // mentirait sur elle-même dès la première divergence.
    const BARRE_COMPACTE = matchMedia('(max-width: 767px)');
    // (« btnEngagement » n'est plus de la partie : les échanges sont un onglet du
    // panneau, plus un lien qui fait sortir de la salle.)
    const SECONDAIRES = ['btnScreen', 'btnBoard', 'btnLayout', 'btnFs', 'btnBg', 'btnRec'];
    const ancres = new Map();
    function rangeSecondaires() {
        const slot = $('moreSlot');
        if (!slot) return;
        const compact = BARRE_COMPACTE.matches;
        // ⚠️ Au retour, on remonte du DERNIER au premier : l'ancre d'un bouton est
        // souvent le bouton suivant, qui doit donc avoir déjà retrouvé sa place -
        // sinon tout se retrouvait empilé à la fin de la barre.
        const ids = compact ? SECONDAIRES : SECONDAIRES.slice().reverse();
        ids.forEach((id) => {
            const el = $(id);
            if (!el) return;
            if (!ancres.has(id)) ancres.set(id, { parent: el.parentElement, apres: el.nextElementSibling });
            if (compact) { slot.appendChild(el); return; }
            const a = ancres.get(id);
            a.parent.insertBefore(el, a.apres && a.apres.parentElement === a.parent ? a.apres : null);
        });
        if (!compact) $('moreSheet')?.classList.remove('open');
    }
    rangeSecondaires();
    BARRE_COMPACTE.addEventListener('change', () => { rangeSecondaires(); layoutGrid(); });
    $('btnMore')?.addEventListener('click', (e) => {
        e.stopPropagation();
        ['bgMenu', 'shareMenu', 'volPanel'].forEach((id) => $(id)?.classList.remove('open'));
        const ouvert = $('moreSheet').classList.toggle('open');
        $('btnMore').setAttribute('aria-expanded', ouvert ? 'true' : 'false');
    });
    // ⚠️ En CAPTURE : deux des boutons de la feuille arrêtent la propagation pour
    // ouvrir leur propre menu (arrière-plan, partage). Sans la capture, la feuille
    // restait ouverte derrière le menu qu'ils venaient d'ouvrir.
    $('moreSlot')?.addEventListener('click', (e) => {
        if (e.target.closest('.ctrl,.v-btn')) $('moreSheet')?.classList.remove('open');
    }, true);
    // Volume / son
    $('btnSpeaker').addEventListener('click', (e) => { e.stopPropagation(); const p = $('volPanel'); if (p.classList.contains('open')) { state.speakerOn = !state.speakerOn; applyVolume(); } p.classList.toggle('open'); });
    $('volRange').addEventListener('input', (e) => { state.volume = Math.max(0, Math.min(1, e.target.value / 100)); state.speakerOn = state.volume > 0; applyVolume(); });
    function applyVolume() {
        const vol = state.speakerOn ? state.volume : 0;
        document.querySelectorAll('#visio-room video, #visio-room audio').forEach((el) => { if (el.closest('#tileLocal') || el.closest('.pip-bubble')) return; el.muted = vol === 0; try { el.volume = vol; } catch (e) {} });
        $('volVal').textContent = Math.round(state.volume * 100) + '%'; syncToolbar();
    }
    $('vModalYes')?.addEventListener('click', () => repondModal && repondModal(true));
    $('vModalNo')?.addEventListener('click', () => repondModal && repondModal(false));
    // Un clic sur le voile, ou Échap, annulent : la sortie la plus sûre est
    // toujours celle qui ne fait rien.
    $('vModal')?.addEventListener('click', (e) => { if (e.target === $('vModal') && repondModal) repondModal(false); });
    /**
     * Échap, du plus intérieur au plus extérieur.
     *
     * ⚠️ Il ne fermait QUE la boîte de confirmation : un menu d'arrière-plan, la
     * feuille « Plus d'options » ou le tiroir ouvert par-dessus les visages se
     * refermaient à la souris, et seulement à la souris. L'ordre compte : on ne
     * referme pas le panneau de quelqu'un qui voulait fermer un menu.
     */
    const MENUS = ['bgMenu', 'shareMenu', 'volPanel', 'moreSheet', 'reacMenu'];
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        if (repondModal) { repondModal(false); return; }
        // Avant les menus : c'est la couche la plus haute, donc la première que
        // l'on veut refermer.
        if (echanges.ouvert) { ouvreEchanges(false); return; }

        const choix = document.querySelector('#visio-room .picker');
        if (choix) { choix.remove(); return; }

        const ouverts = MENUS.map($).filter((m) => m && m.classList.contains('open'));
        if (ouverts.length) {
            ouverts.forEach((m) => m.classList.remove('open'));
            $('btnMore')?.setAttribute('aria-expanded', 'false');
            $('btnReac')?.setAttribute('aria-expanded', 'false');

            return;
        }
        if (reponseA) { annuleReponse(); return; }
        // Le tiroir n'est une couche par-dessus la scène qu'en dessous de 900px :
        // au-dessus, il fait partie de la page et Échap n'a pas à l'escamoter.
        if (ETROIT.matches && $('sidePanel')?.classList.contains('open')) basculePanneau(false);
    });
    $('localMic')?.addEventListener('click', (e) => { e.stopPropagation(); basculeMicro('local'); });
    $('btnReconnect')?.addEventListener('click', reconnecte);
    $('btnLeave').addEventListener('click', leave);
    $('btnLeaveTop').addEventListener('click', (e) => { e.preventDefault(); leave(); });
    document.addEventListener('click', (e) => {
        const bg = $('bgMenu'), sm = $('shareMenu'), vp = $('volPanel'), ms = $('moreSheet');
        if (ms && !ms.contains(e.target) && !$('btnMore')?.contains(e.target)) ms.classList.remove('open');
        if (bg && !bg.contains(e.target) && e.target !== $('btnBg') && !$('btnBg').contains(e.target)) bg.classList.remove('open');
        if (sm && !sm.contains(e.target) && !$('btnScreen').contains(e.target)) sm.classList.remove('open');
        if (vp && !vp.contains(e.target) && !$('btnSpeaker').contains(e.target)) vp.classList.remove('open');
        const rm = $('reacMenu');
        if (rm && !rm.contains(e.target) && !$('btnReac')?.contains(e.target)) fermeMenuReactions();
    });
    // La grille se recalcule quand la place change : ouvrir le panneau latéral ou
    // réduire la fenêtre modifie la largeur disponible, donc la bonne grille.
    window.addEventListener('resize', () => { layoutGrid(); cadreDuTableau(); });
    window.addEventListener('beforeunload', () => { try { if (echanges.tic) clearInterval(echanges.tic); } catch (e) {} try { if (presenceTimer) clearInterval(presenceTimer); } catch (e) {} try { if (room) room.disconnect(); } catch (e) {} });

    // ============ Démarrage ============
    $('localName').textContent = state.name; poseAvatar($('localAvatar'), state.name); $('localRole').textContent = 'Vous';
    people = [{ name: state.name, role: state.role, hand: false, screen: false, id: 'local' }];
    updateModUi(); refreshPeople(); refreshComms();
    // L'onglet Présence n'existe que pour qui a le droit de pointer : c'est le
    // serveur qui le dit en donnant (ou non) les adresses.
    // Échanges : l'onglet n'existe que si le serveur a donné les adresses - c'est
    // lui qui décide de l'accès, pas le navigateur.
    if (echangesActifs()) {
        const bouton = $('btnEch'); if (bouton) bouton.style.display = '';
        chargeEchanges();
        // ⚠️ Le rafraîchissement périodique ne tourne que panneau OUVERT sur cet
        // onglet : la salle entière interrogeant le serveur toutes les dix secondes
        // pour un panneau que personne ne regarde, c'est du bruit pur. L'annonce par
        // le canal de données tient le compteur à jour entre-temps.
        // ⚠️ Deux rythmes. Surface ouverte, on relit souvent : un vote qui monte
        // pendant qu'on regarde doit se voir. Fermée, on relit QUAND MÊME, mais
        // rarement - sinon la pastille mentirait jusqu'à ce qu'on pense à ouvrir,
        // et c'est précisément ce qu'elle existe pour éviter. L'annonce par le
        // canal de données rafraîchit de toute façon dès que quelqu'un poste.
        echanges.tic = setInterval(() => {
            if (document.hidden || !echangesActifs()) return;
            if (!echanges.ouvert && Date.now() - echanges.dernier < 45000) return;
            chargeEchanges();
        }, 10000);
    }
    $('btnEch')?.addEventListener('click', () => ouvreEchanges(!echanges.ouvert));
    $('echClose')?.addEventListener('click', () => ouvreEchanges(false));
    // Un clic sur le voile referme : c'est ce qu'on attend d'une couche posée
    // par-dessus la séance.
    $('ech')?.addEventListener('click', (e) => { if (e.target === $('ech')) ouvreEchanges(false); });
    document.querySelectorAll('#echFiltres button').forEach((b) => {
        b.addEventListener('click', () => { echanges.filtre = b.dataset.f; rendEchanges(); });
    });
    $('engSend')?.addEventListener('click', poseQuestion);
    $('engInput')?.addEventListener('keydown', (e) => { if (e.key === 'Enter') poseQuestion(); });
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
    // ⚠️ Dans l'ordre où cela s'est passé : la salle s'ouvre, le fil se déroule,
    // et l'arrivée se marque EN DERNIER. L'inverse donnait un message
    // d'ouverture posé après des échanges plus anciens que lui.
    addChat('Système', 'Bienvenue dans la salle « ' + roomName + ' ».');
    await chargeHistorique();
    // ⚠️ Avant la connexion, pas après : on ne vérifie pas ses périphériques depuis
    // une salle où l'on a déjà fait son entrée. Le fil, lui, se charge derrière
    // l'écran - autant de gagné quand la porte s'ouvre.
    await ecranDEntree();
    await connect();
    syncToolbar();
}
