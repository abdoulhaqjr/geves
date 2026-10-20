/**
 * Règles pures de la rédaction à plusieurs (documents de tâche du module Projet).
 *
 * ⚠️ Elles vivent ICI, hors de l'éditeur, parce que ce sont elles qui portent
 * les décisions coûteuses - qui enregistre, ce qui tient dans un message, quand
 * renoncer - et qu'elles sont intestables une fois enfermées dans une fermeture
 * qui a besoin d'un navigateur, d'un WebSocket et de deux pairs connectés.
 * Même grammaire que `meeting-pure.js` et `program-pure.js`.
 */

/**
 * Plafond d'utile d'un message du canal, en octets AVANT base64.
 *
 * ⚠️ Le service limite un événement client à 10 Ko. Le base64 gonfle d'un tiers
 * (4 octets pour 3), et l'enveloppe JSON prend sa part : on borne donc l'utile
 * bien en dessous. Un message au-dessus de la limite n'est pas tronqué par le
 * service, il est REFUSÉ - la frappe disparaîtrait sans un mot.
 */
export const MAX_OCTETS = 6000;

/**
 * Suis-je celui qui enregistre ?
 *
 * ⚠️ **Un seul pair écrit en base, et il faut que tous désignent le MÊME.**
 * Sans élection, cinq personnes qui rédigent ensemble enverraient cinq
 * instantanés du même document toutes les deux secondes - quatre de trop, dont
 * chacun repart en conflit contre les autres.
 *
 * La règle est le plus petit identifiant : elle ne dépend d'aucune horloge et
 * d'aucun ordre d'arrivée, donc deux postes y répondent pareil sans se parler.
 *
 * ⚠️ Seul reste à l'écart QUI NE PEUT PAS ÉCRIRE : un lecteur élu scribe
 * n'enregistrerait jamais rien, et le document ne serait plus conservé du tout.
 *
 * @param {string} moi identifiant de ce poste
 * @param {string[]} pairs identifiants des postes connectés qui peuvent écrire (moi compris)
 * @returns {boolean}
 */
export function estLeScribe(moi, pairs) {
    if (!moi) return false;

    const candidats = (pairs || []).filter(Boolean);
    if (!candidats.includes(moi)) return false;

    return candidats.slice().sort()[0] === moi;
}

/**
 * Un message dépasse-t-il ce que le canal accepte ?
 *
 * ⚠️ Le cas se produit pour de vrai : un collage de plusieurs pages, ou
 * l'arrivée de quelqu'un sur un document longuement modifié hors connexion.
 * L'appelant bascule alors sur le serveur (enregistrer, puis faire relire
 * l'état aux autres) au lieu de perdre la modification en silence.
 *
 * @param {number} octets
 * @param {number} [plafond]
 */
export function tropGrosPourLeCanal(octets, plafond = MAX_OCTETS) {
    return !(octets >= 0) || octets > plafond;
}

/**
 * Teinte stable tirée d'un nom, pour le curseur et la pastille d'une personne.
 *
 * ⚠️ Seule la TEINTE varie : la saturation et la clarté restent fixées par la
 * feuille de style, pour que le texte posé dessus garde son contraste quelle que
 * soit la couleur tirée. Même règle que les avatars de la salle de visio.
 *
 * @param {string} nom
 * @returns {number} 0-359
 */
export function teinteDuNom(nom) {
    const texte = String(nom || '');
    let somme = 0;

    for (let i = 0; i < texte.length; i += 1) {
        somme = (somme * 31 + texte.charCodeAt(i)) % 360;
    }

    return somme;
}

/**
 * Faut-il réessayer un enregistrement refusé pour cause de concurrence ?
 *
 * ⚠️ **Un refus n'est PAS une perte.** Le poste repoussé fusionne l'état du
 * serveur au sien - deux états se fusionnent toujours - puis réessaie. Mais on
 * borne : si la révision avance à chaque tentative, c'est que quelqu'un écrit
 * sans arrêt, et une boucle sans fin ferait tourner le navigateur jusqu'à le
 * bloquer. Au-delà, on laisse le prochain enregistrement périodique s'en
 * charger - rien n'est perdu, c'est seulement remis à quelques secondes.
 *
 * @param {number} essais nombre de tentatives déjà faites
 * @param {number} [max]
 */
export function peutReessayer(essais, max = 3) {
    return Number.isFinite(essais) && essais < max;
}

/**
 * Phrase d'état de l'enregistrement, affichée en permanence.
 *
 * ⚠️ Un document qu'on rédige à plusieurs ne porte pas de bouton « Enregistrer » :
 * il faut donc DIRE où en est la conservation, sinon personne ne sait s'il peut
 * fermer l'onglet. Les quatre états sont distincts à dessein - « en cours » et
 * « enregistré » ne veulent pas dire la même chose au moment de partir.
 *
 * @param {{etat: string, quand?: string|null, lecteur?: boolean}} o
 */
export function phraseEtat({ etat, quand = null, lecteur = false }) {
    if (lecteur) return 'Lecture seule';

    switch (etat) {
        case 'modifie':
            return 'Modifications non enregistrées';
        case 'cours':
            return 'Enregistrement…';
        case 'erreur':
            return 'Enregistrement impossible - vérifiez votre connexion';
        case 'enregistre':
            return quand ? `Enregistré à ${quand}` : 'Enregistré';
        default:
            return '';
    }
}

/**
 * Phrase de présence (« qui est là »), accordée.
 *
 * @param {number} nombre pairs connectés, soi NON compris
 */
export function phrasePresence(nombre) {
    const n = Number(nombre) || 0;

    if (n <= 0) return 'Vous êtes seul sur ce document';
    if (n === 1) return '1 autre personne sur ce document';

    return `${n} autres personnes sur ce document`;
}
