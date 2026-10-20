/**
 * Rédaction À PLUSIEURS d'un document de tâche (module Projet).
 *
 * ⚠️⚠️ **CE N'EST PAS DU TEXTE QUI CIRCULE, CE SONT DES OPÉRATIONS.** Deux
 * personnes qui tapent au même endroit, chacune sur sa copie, ne peuvent pas se
 * réconcilier en s'envoyant leur texte - le dernier arrivé écraserait l'autre.
 * Yjs (CRDT) échange des modifications qui fusionnent dans n'importe quel ordre
 * et donnent toujours le même résultat chez tout le monde. C'est ce qui rend
 * tout le reste de ce fichier possible, et c'est aussi ce qui rend sûr le refus
 * de concurrence du serveur : un poste repoussé FUSIONNE et réessaie, il ne perd
 * rien.
 *
 * ⚠️⚠️ **LES FRAPPES NE PASSENT PAS PAR PHP.** Elles voyagent en « événements
 * client » d'un navigateur à l'autre à travers le service WebSocket : c'est ce
 * qui donne la frappe instantanée. Le serveur n'intervient qu'à deux moments -
 * autoriser l'entrée dans le canal, et CONSERVER l'état par instantanés espacés.
 *
 * ⚠️⚠️ **TOUT CECI EST FACULTATIF.** Sans service WebSocket configuré, il n'y a
 * ni canal, ni curseurs, ni pairs : l'éditeur, l'enregistrement et l'historique
 * fonctionnent à l'identique. Mieux - comme la fusion se fait aussi au moment du
 * refus de concurrence, deux personnes qui écrivent en même temps SANS temps
 * réel ne se perdent pas non plus ; elles ne se voient simplement pas écrire.
 */

import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Collaboration from '@tiptap/extension-collaboration';
import CollaborationCaret from '@tiptap/extension-collaboration-caret';
import * as Y from 'yjs';
import { Awareness, applyAwarenessUpdate, encodeAwarenessUpdate } from 'y-protocols/awareness';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

import {
    estLeScribe,
    peutReessayer,
    phraseEtat,
    phrasePresence,
    teinteDuNom,
    tropGrosPourLeCanal,
} from './task-document-pure';

const cfg = window.UKC_DOC || null;

/** Délai d'inactivité avant enregistrement. */
const DELAI_ENREGISTREMENT = 2500;

/** Regroupement des modifications avant émission sur le canal. */
const DELAI_EMISSION = 120;

// --- Encodage ---------------------------------------------------------------
// ⚠️ Le canal et la base transportent du TEXTE ; Yjs produit des octets. On
// passe donc par base64 des deux côtés, avec la même paire de fonctions - deux
// conversions écrites séparément finiraient par diverger sur les caractères de
// fin de bloc, et un document ne se rouvrirait plus.

function enBase64(octets) {
    let binaire = '';
    const vue = new Uint8Array(octets);
    // ⚠️ Par tranches : `String.fromCharCode(...vue)` dépasse la taille maximale
    // de la pile d'appels dès quelques dizaines de milliers d'octets, c'est-à-dire
    // sur un document d'une page.
    for (let i = 0; i < vue.length; i += 0x8000) {
        binaire += String.fromCharCode.apply(null, vue.subarray(i, i + 0x8000));
    }
    return btoa(binaire);
}

function depuisBase64(texte) {
    const binaire = atob(String(texte || ''));
    const octets = new Uint8Array(binaire.length);
    for (let i = 0; i < binaire.length; i += 1) octets[i] = binaire.charCodeAt(i);
    return octets;
}

// --- Démarrage --------------------------------------------------------------

function demarre() {
    const zone = document.getElementById('ukcDocEditeur');
    if (!cfg || !zone) return;

    const etat = {
        revision: Number(cfg.revision) || 0,
        sale: false,
        enregistrement: null,
        emission: null,
        file: [],
        pairs: [cfg.me.id],
        canal: null,
    };

    const ydoc = new Y.Doc();
    if (cfg.state) Y.applyUpdate(ydoc, depuisBase64(cfg.state), 'serveur');

    const awareness = new Awareness(ydoc);
    awareness.setLocalStateField('user', {
        name: cfg.me.name,
        color: `hsl(${teinteDuNom(cfg.me.name)} 70% 45%)`,
    });

    const editeur = new Editor({
        element: zone,
        editable: !!cfg.canWrite,
        extensions: [
            // ⚠️ L'annulation NATIVE est coupée : avec plusieurs rédacteurs, elle
            // défait la frappe de n'importe qui. Celle de Collaboration ne défait
            // que les SIENNES - c'est la seule qui ait un sens à plusieurs.
            StarterKit.configure({ undoRedo: false }),
            Collaboration.configure({ document: ydoc }),
            CollaborationCaret.configure({
                provider: { awareness },
                user: awareness.getLocalState().user,
            }),
        ],
    });

    // ⚠️ Reprise d'un document écrit AVANT que l'état partagé n'existe (ou
    // enregistré sans lui) : on ne sème le contenu que si le serveur n'a AUCUN
    // état, donc qu'aucun pair ne peut en avoir non plus. Le semer dans le doute
    // le ferait apparaître en double, une fois par poste ouvert.
    if (!cfg.state && cfg.html) {
        editeur.commands.setContent(cfg.html);
    }

    poseEtat(etat, 'enregistre', cfg.savedAtLabel);
    posePresence(0);

    // --- Conservation -------------------------------------------------------

    ydoc.on('update', (maj, origine) => {
        etat.sale = true;
        planifie(etat, () => enregistre(etat, editeur, ydoc));

        if (origine === 'distant' || origine === 'serveur') return;

        etat.file.push(maj);
        if (etat.emission) return;
        etat.emission = window.setTimeout(() => {
            etat.emission = null;
            emet(etat, ydoc, editeur);
        }, DELAI_EMISSION);
    });

    // ⚠️ Dernière chance au départ de la page : `sendBeacon` part même pendant la
    // fermeture, là où un `fetch` est abandonné. Il ne peut pas porter d'en-tête,
    // donc le jeton voyage dans le corps - Laravel l'y lit aussi.
    window.addEventListener('pagehide', () => {
        if (!etat.sale || !cfg.canWrite) return;
        const corps = new FormData();
        corps.append('_token', cfg.csrf);
        corps.append('state', enBase64(Y.encodeStateAsUpdate(ydoc)));
        corps.append('html', editeur.getHTML());
        corps.append('revision', String(etat.revision));
        navigator.sendBeacon?.(cfg.saveUrl, corps);
    });

    // --- Temps réel ---------------------------------------------------------

    if (cfg.realtime) {
        connecte(etat, ydoc, awareness, editeur);
    }

    brancheBarre(etat, editeur, ydoc);
    window.ukcDocEditeur = editeur;
}

// --- Canal ------------------------------------------------------------------

function connecte(etat, ydoc, awareness, editeur) {
    const rt = cfg.realtime;
    window.Pusher = Pusher;

    const options = {
        broadcaster: 'pusher',
        key: rt.key,
        forceTLS: !!rt.forceTLS,
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
        authEndpoint: '/broadcasting/auth',
        auth: { headers: { 'X-CSRF-TOKEN': cfg.csrf } },
    };

    // ⚠️ On ne pose l'hôte QUE s'il est renseigné : laissé vide, le client doit
    // retomber sur les serveurs du protocole, et un `wsHost: undefined` explicite
    // suffit à casser cette résolution.
    if (rt.host) {
        options.wsHost = rt.host;
        options.wsPort = rt.port;
        options.wssPort = rt.port;
    }
    if (rt.cluster) options.cluster = rt.cluster;

    let echo;
    try {
        echo = new Echo(options);
    } catch (e) {
        // Le temps réel est un confort : son échec ne doit pas emporter l'éditeur.
        console.warn('[document] canal indisponible', e);
        return;
    }

    const canal = echo.join(cfg.channel);
    etat.canal = canal;

    const retiens = (membres) => {
        etat.pairs = membres
            .filter((m) => m.canWrite)
            .map((m) => String(m.id));
        posePresence(membres.length - 1);
    };

    canal.here((membres) => {
        retiens(membres);
        // ⚠️ C'est l'ARRIVANT qui demande à se mettre à jour, pas les présents qui
        // devinent : envoyé à l'annonce d'arrivée, l'état partirait peut-être
        // avant que l'arrivant n'écoute. On n'envoie qu'un VECTEUR (quelques
        // octets), et le pair répond par la seule différence qui nous manque.
        canal.whisper('sync', {
            sv: enBase64(Y.encodeStateVector(ydoc)),
            de: String(cfg.me.id),
        });
    });

    canal.joining((m) => {
        etat.pairs = etat.pairs.concat(m.canWrite ? [String(m.id)] : []);
        posePresence(etat.pairs.length - 1);
    });

    canal.leaving((m) => {
        etat.pairs = etat.pairs.filter((id) => id !== String(m.id));
        posePresence(etat.pairs.length - 1);
    });

    canal.listenForWhisper('maj', (d) => {
        Y.applyUpdate(ydoc, depuisBase64(d.u), 'distant');
    });

    canal.listenForWhisper('presence', (d) => {
        applyAwarenessUpdate(awareness, depuisBase64(d.u), 'distant');
    });

    canal.listenForWhisper('sync', (d) => {
        // ⚠️ UN SEUL pair répond, sinon cinq présents envoient cinq fois le même
        // état. Le demandeur est écarté de l'élection - sinon, quand c'est lui le
        // scribe, personne ne répondrait.
        const autres = etat.pairs.filter((id) => id !== String(d.de));
        if (!estLeScribe(String(cfg.me.id), autres)) return;

        const diff = Y.encodeStateAsUpdate(ydoc, depuisBase64(d.sv));
        if (tropGrosPourLeCanal(diff.byteLength)) {
            canal.whisper('recharge', {});
            return;
        }
        canal.whisper('sync-reponse', { u: enBase64(diff), pour: String(d.de) });
    });

    canal.listenForWhisper('sync-reponse', (d) => {
        if (String(d.pour) !== String(cfg.me.id)) return;
        Y.applyUpdate(ydoc, depuisBase64(d.u), 'distant');
    });

    // Un pair a enregistré : on suit sa révision, pour ne pas se refuser
    // mutuellement au prochain instantané.
    canal.listenForWhisper('enregistre', (d) => {
        etat.revision = Number(d.revision) || etat.revision;
        etat.sale = false;
        poseEtat(etat, 'enregistre', d.at);
    });

    canal.listenForWhisper('recharge', () => relitDuServeur(etat, ydoc));

    awareness.on('update', ({ added, updated, removed }, origine) => {
        if (origine === 'distant') return;
        const ids = added.concat(updated, removed);
        canal.whisper('presence', { u: enBase64(encodeAwarenessUpdate(awareness, ids)) });
    });
}

function emet(etat, ydoc, editeur) {
    if (!etat.canal || etat.file.length === 0) return;

    const maj = Y.mergeUpdates(etat.file);
    etat.file = [];

    // ⚠️ Trop gros pour le canal (un collage de plusieurs pages) : on passe par
    // le serveur plutôt que d'émettre un message que le service REFUSERAIT en
    // silence - la modification disparaîtrait chez les autres sans un mot.
    if (tropGrosPourLeCanal(maj.byteLength)) {
        enregistre(etat, editeur, ydoc).then(() => etat.canal?.whisper('recharge', {}));
        return;
    }

    etat.canal.whisper('maj', { u: enBase64(maj) });
}

async function relitDuServeur(etat, ydoc) {
    try {
        const r = await fetch(cfg.stateUrl, { headers: { Accept: 'application/json' } });
        if (!r.ok) return;
        const d = await r.json();
        if (d.state) Y.applyUpdate(ydoc, depuisBase64(d.state), 'serveur');
        etat.revision = Number(d.revision) || etat.revision;
    } catch (e) {
        console.warn('[document] relecture impossible', e);
    }
}

// --- Enregistrement ---------------------------------------------------------

function planifie(etat, action) {
    poseEtat(etat, 'modifie');
    window.clearTimeout(etat.enregistrement);
    etat.enregistrement = window.setTimeout(action, DELAI_ENREGISTREMENT);
}

async function enregistre(etat, editeur, ydoc, essais = 0) {
    if (!cfg.canWrite || !etat.sale) return;

    // ⚠️ Un seul pair écrit en base. Les autres suivent par l'annonce
    // « enregistre » ci-dessus : sans élection, chaque rédacteur enverrait son
    // propre instantané du MÊME document toutes les deux secondes.
    if (!estLeScribe(String(cfg.me.id), etat.pairs)) return;

    poseEtat(etat, 'cours');

    try {
        const r = await fetch(cfg.saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': cfg.csrf,
            },
            body: JSON.stringify({
                state: enBase64(Y.encodeStateAsUpdate(ydoc)),
                html: editeur.getHTML(),
                revision: etat.revision,
            }),
        });

        if (r.status === 409) {
            // ⚠️ Un refus n'est pas une perte : l'état du serveur se FUSIONNE au
            // nôtre - c'est la propriété du CRDT - puis on réessaie. Sans la
            // fusion, réessayer écraserait le travail de l'autre.
            const d = await r.json();
            if (d.state) Y.applyUpdate(ydoc, depuisBase64(d.state), 'serveur');
            etat.revision = Number(d.revision) || 0;

            if (peutReessayer(essais)) {
                return enregistre(etat, editeur, ydoc, essais + 1);
            }
            poseEtat(etat, 'modifie');
            return;
        }

        if (!r.ok) {
            poseEtat(etat, 'erreur');
            return;
        }

        const d = await r.json();
        etat.revision = Number(d.revision) || etat.revision;
        etat.sale = false;
        poseEtat(etat, 'enregistre', formateHeure(d.saved_at));
        etat.canal?.whisper('enregistre', { revision: etat.revision, at: formateHeure(d.saved_at) });
        if (d.versions) poseVersions(d.versions);
    } catch (e) {
        console.warn('[document] enregistrement impossible', e);
        poseEtat(etat, 'erreur');
    }
}

// --- Barre et panneaux ------------------------------------------------------

function brancheBarre(etat, editeur, ydoc) {
    document.querySelectorAll('[data-doc-cmd]').forEach((bouton) => {
        bouton.addEventListener('click', () => {
            const chaine = editeur.chain().focus();
            const [commande, argument] = bouton.dataset.docCmd.split(':');
            if (argument) chaine[commande]({ level: Number(argument) }).run();
            else chaine[commande]().run();
        });
    });

    document.getElementById('ukcDocVersion')?.addEventListener('click', async () => {
        const label = window.prompt('Nom de cette version (facultatif)') ?? null;
        const r = await fetch(cfg.versionUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': cfg.csrf,
            },
            body: JSON.stringify({ label }),
        });
        if (r.ok) poseVersions((await r.json()).versions);
    });

    document.getElementById('ukcDocVersions')?.addEventListener('click', async (e) => {
        const bouton = e.target.closest('[data-version]');
        if (!bouton) return;

        const r = await fetch(bouton.dataset.versionUrl, { headers: { Accept: 'application/json' } });
        if (!r.ok) return;
        const d = await r.json();

        const ok = await window.ukcConfirm(
            'Remettre le document dans l’état de cette version ? Le texte actuel est remplacé pour tout le monde - et cela s’annule comme n’importe quelle autre modification.',
            { title: 'Revenir à cette version', confirm: 'Revenir' }
        );
        if (!ok) return;

        // ⚠️⚠️ **ON RESTAURE PAR UNE MODIFICATION, PAS EN RÉÉCRIVANT L'ÉTAT.**
        // Remplacer l'état partagé ne reviendrait en arrière chez personne : deux
        // états Yjs FUSIONNENT au lieu de se remplacer, et le texte « retiré »
        // reparaîtrait au premier enregistrement suivant. Posée comme une
        // modification ordinaire, la restauration se propage aux autres postes et
        // s'annule normalement.
        editeur.commands.setContent(d.html || '');
        etat.sale = true;
        planifie(etat, () => enregistre(etat, editeur, ydoc));
    });
}

function poseEtat(etat, quoi, quand = null) {
    const cible = document.getElementById('ukcDocEtat');
    if (!cible) return;
    cible.textContent = phraseEtat({ etat: quoi, quand, lecteur: !cfg.canWrite });
    cible.dataset.etat = quoi;
}

function posePresence(autres) {
    const cible = document.getElementById('ukcDocPresence');
    if (cible) cible.textContent = phrasePresence(autres);
}

function poseVersions(versions) {
    const liste = document.getElementById('ukcDocVersions');
    if (!liste || !Array.isArray(versions)) return;

    liste.innerHTML = '';
    versions.forEach((v) => {
        const li = document.createElement('li');
        const bouton = document.createElement('button');
        bouton.type = 'button';
        bouton.dataset.version = v.id;
        bouton.dataset.versionUrl = cfg.versionUrl + '/' + v.id;
        bouton.className = 'ukc-doc-version';

        const titre = document.createElement('strong');
        titre.textContent = v.label || `Version ${v.revision}`;
        const meta = document.createElement('span');
        meta.textContent = [v.atLabel, v.author].filter(Boolean).join(' - ');

        bouton.append(titre, meta);
        li.append(bouton);
        liste.append(li);
    });
}

function formateHeure(iso) {
    if (!iso) return null;
    try {
        return new Date(iso).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    } catch (e) {
        return null;
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', demarre);
} else {
    demarre();
}
