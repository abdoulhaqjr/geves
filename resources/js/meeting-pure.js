// Règles de la salle de visio qui ne dépendent ni du DOM, ni de LiveKit, ni de
// l'état d'une connexion : on leur donne des valeurs, elles rendent un résultat.
//
// ⚠️ Elles vivaient dans la fermeture de `meeting.js` (~1 600 lignes), donc hors
// d'atteinte de tout test. Ce sont pourtant elles qui portent les décisions qu'on
// a le plus corrigées : qui a le droit de donner un ordre, comment numéroter les
// mains levées, combien de colonnes pour la mosaïque. Elles sont ici, et
// couvertes par `meeting-pure.test.js`.

/**
 * Initiales d'un nom.
 *
 * ⚠️ Filtrées aux lettres et aux chiffres : elles sont posées dans des gabarits
 * construits en `innerHTML` (vignette, bulle caméra), et le premier caractère
 * d'un nom hostile y ouvrirait une balise.
 */
export function initials(n) {
  return (
    (String(n ?? '?').match(/[\p{L}\p{N}]+/gu) || [])
      .map((mot) => mot[0])
      .join('')
      .slice(0, 2)
      .toUpperCase() || '?'
  );
}

/** Texte d'une ligne, ramené à `n` caractères. */
export function texteCourt(t, n) {
  const s = String(t ?? '')
    .replace(/\s+/g, ' ')
    .trim();

  return s.length > n ? s.slice(0, n - 1) + '…' : s;
}

/**
 * Identifiant réduit à l'alphabet attendu.
 *
 * ⚠️ Il sert d'`id` dans le DOM et de clé d'URL : tout le reste tombe.
 */
export function idSur(v) {
  return String(v ?? '')
    .replace(/[^a-z0-9]/gi, '')
    .slice(0, 24);
}

/** Heure courte d'un message (chaîne ISO du serveur, ou instant local). */
export function heure(v, locales) {
  const d = v instanceof Date ? v : new Date(v);

  return isNaN(d.getTime())
    ? ''
    : d.toLocaleTimeString(locales || [], { hour: '2-digit', minute: '2-digit' });
}

/** Nom du rôle tel que la salle le dit. */
export function roleLabel(r) {
  return r === 'presenter' ? 'Communicateur' : r === 'moderator' ? 'Modérateur' : 'Auditeur';
}

/** Nom d'un verdict de présence. */
export function presenceLabel(st) {
  return st === 'present'
    ? 'Présent'
    : st === 'absent'
      ? 'Absent'
      : st === 'excused'
        ? 'Excusé'
        : '';
}

/**
 * Ce que le serveur annonce d'un participant : son rôle et son compte.
 *
 * ⚠️ Seule source digne de confiance pour ces deux informations - elle est signée
 * dans le jeton, et le participant n'a pas le droit de la modifier
 * (`canUpdateOwnMetadata` n'est jamais accordé). Tout ce qui n'est pas un rôle
 * connu retombe sur « audience » : un champ inventé ne donne aucun pouvoir.
 */
export function metadonnee(participant) {
  try {
    const md = JSON.parse((participant && participant.metadata) || '{}');
    const role = md.role === 'moderator' || md.role === 'presenter' ? md.role : 'audience';

    return { role, accountId: md.accountId || null };
  } catch (e) {
    return { role: 'audience', accountId: null };
  }
}

/**
 * Qui a le droit d'émettre quoi sur le canal de données.
 *
 * ⚠️ Rien ne le vérifiait : n'importe quel participant pouvait, depuis la console
 * de son navigateur, couper le micro de toute la salle, retirer la parole au
 * présentateur ou afficher un faux bandeau « enregistrement en cours ». Le canal
 * est public pour tous les connectés ; c'est au RÉCEPTEUR de ne pas obéir à
 * n'importe qui.
 */
export const ORDRES_RESERVES = {
  mod_unmute: 'moderator',
  mod_mute: 'moderator',
  mod_hand_down: 'moderator',
  mod_role: 'moderator',
  comm_done: 'moderator',
  recording: 'moderator',
  chat_removed: 'moderator',
  // Prendre le partage d'écran ou masquer la vignette caméra ne regarde que
  // ceux qui diffusent.
  screen_takeover: 'presenter',
  pip_visibility: 'presenter',
};

/** Vrai si ce rôle a le rang exigé par ce type de message. */
export function ordreRecevable(type, role) {
  const exige = ORDRES_RESERVES[type];
  if (!exige) return true;

  return exige === 'moderator'
    ? role === 'moderator'
    : role === 'moderator' || role === 'presenter';
}

/**
 * Mains levées, de la plus ancienne à la plus récente.
 *
 * ⚠️ L'ordre vient d'un compteur d'arrivée (`handAt`) et non de l'horloge : deux
 * postes n'ont pas la même heure, alors que les messages arrivent dans l'ordre
 * d'émission. Le rang se RECALCULE à chaque affichage, donc une main baissée
 * renumérote les suivantes au lieu de laisser un trou.
 */
export function mainsOrdonnees(gens) {
  return (gens || []).filter((p) => p.hand && p.handAt).sort((a, b) => a.handAt - b.handAt);
}

/** Rang de chacun dans la file des mains levées, par identifiant. */
export function rangsDesMains(gens) {
  return new Map(mainsOrdonnees(gens).map((p, i) => [p.id, i + 1]));
}

/**
 * Nombre de colonnes de la mosaïque : celui qui donne les plus grandes tuiles
 * sans trop s'éloigner du format demandé.
 *
 * ⚠️ Laissé au CSS (`auto-fit`), quatre personnes s'alignaient sur UNE rangée :
 * des tuiles hautes et étroites, où un visage filmé en 16/9 était rogné jusqu'au
 * nez. C'est la place disponible qui décide, pas une largeur écrite à l'avance.
 */
export function grilleOptimale(n, largeur, hauteur, gap = 10, cible = 16 / 9) {
  if (n <= 0) return { cols: 1, rows: 1 };

  let meilleur = 1;
  let meilleureAire = 0;

  for (let cols = 1; cols <= n; cols++) {
    const rows = Math.ceil(n / cols);
    const w = (largeur - gap * (cols - 1)) / cols;
    const h = (hauteur - gap * (rows - 1)) / rows;
    if (w <= 0 || h <= 0) continue;
    // La plus grande tuile au format cible qui tient dans la cellule.
    const tw = Math.min(w, h * cible);
    const aire = tw * (tw / cible);
    if (aire > meilleureAire) {
      meilleureAire = aire;
      meilleur = cols;
    }
  }

  return { cols: meilleur, rows: Math.ceil(n / meilleur) };
}
