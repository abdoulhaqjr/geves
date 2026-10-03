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
  // Le temps de parole affiché chez tout le monde : c'est la séance qu'il
  // engage, pas un affichage personnel.
  floor_time: 'moderator',
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

/**
 * Qui parle, ramené aux identifiants de la salle.
 *
 * ⚠️ LiveKit annonce ses propres participants ; notre liste, elle, désigne la
 * personne devant l'écran par `local` - et c'est sur cet identifiant que sont
 * nommées les tuiles comme les lignes du panneau. Sans cette traduction, celui
 * qui parle est repéré pour tout le monde SAUF pour lui-même.
 */
export function idsDesParleurs(speakers, idLocal) {
  return new Set(
    (speakers || [])
      .map((p) => (p && p.identity) || '')
      .filter(Boolean)
      .map((id) => (idLocal && id === idLocal ? 'local' : id))
  );
}

/**
 * Ce qu'il faut dire d'une qualité de connexion - ou rien.
 *
 * ⚠️ « Excellent » ne s'affiche PAS. Un indicateur posé en permanence sur tout
 * le monde ne veut plus rien dire : celui-ci ne doit se remarquer que le jour où
 * il apparaît. Il ne parle donc que des ennuis, et tout ce qui n'est pas un
 * ennui connu reste muet - y compris `unknown`, qui est l'état des premières
 * secondes de chaque arrivée.
 */
export function niveauReseau(q) {
  const v = String(q || '').toLowerCase();

  return v === 'poor'
    ? { classe: 'faible', intitule: 'Réseau instable' }
    : v === 'lost'
      ? { classe: 'perdu', intitule: 'Connexion perdue' }
      : null;
}

/**
 * Le fil est-il collé au bas ?
 *
 * ⚠️ Il se recadrait de force à chaque message : on remontait lire ce qui s'était
 * dit, un message arrivait, et la lecture était arrachée vers le bas. La marge
 * laisse « collé » quelques pixels de jeu - une molette, et plus encore un
 * doigt, ne s'arrêtent pas au pixel près.
 */
export function enBasDuFil(hauteurTotale, defilement, hauteurVue, marge = 80) {
  return hauteurTotale - defilement - hauteurVue <= marge;
}

/**
 * Teinte de l'avatar d'une personne, tirée de son nom.
 *
 * ⚠️ Les pastilles portaient TOUTES le même dégradé bleu-vert : huit caméras
 * fermées, c'était huit disques identiques qu'on ne séparait qu'à deux lettres.
 * La teinte est tirée du nom, donc toujours la même pour la même personne, d'un
 * bout à l'autre de la salle (vignette, liste, bulle) et d'une séance à l'autre.
 *
 * ⚠️ Seule la TEINTE varie : la feuille de style fixe la saturation et une
 * clarté basse, pour que le blanc des initiales garde son contraste quelle que
 * soit la couleur tirée - un jaune clair les effacerait. Sans nom, on retombe
 * sur le bleu de la charte.
 */
export function teinteDuNom(nom) {
  const s = String(nom ?? '')
    .trim()
    .toLowerCase();
  if (!s) return 225;

  let h = 0;
  for (let i = 0; i < s.length; i++) h = (h * 31 + s.charCodeAt(i)) % 360;

  return h;
}

/** Durée parlée, écrite comme une pendule : mm:ss, et h:mm:ss au-delà de l'heure. */
export function chronoLabel(secondes) {
  const t = Math.max(0, Math.floor(Number(secondes) || 0));
  const h = Math.floor(t / 3600);
  const m = Math.floor((t % 3600) / 60);
  const s = t % 60;
  const deux = (n) => String(n).padStart(2, '0');

  return h ? h + ':' + deux(m) + ':' + deux(s) : deux(m) + ':' + deux(s);
}

/**
 * Où en est le temps de parole par rapport au créneau prévu.
 *
 * ⚠️ Trois états et non deux : prévenir SEULEMENT au dépassement ne sert à rien -
 * à ce moment-là, la personne est déjà en train de conclure. L'avertissement
 * tombe au dernier cinquième du créneau, quand il reste de quoi abréger.
 *
 * ⚠️ Sans créneau (`minutes` absent), le chronomètre compte sans jamais alerter :
 * il n'y a rien à dépasser, et une couleur d'alarme sortie de nulle part ferait
 * écourter une communication qui tient son temps.
 */
export function etatChrono(secondes, minutes) {
  const t = Math.max(0, Math.floor(Number(secondes) || 0));
  const prevu = Number(minutes) > 0 ? Math.floor(Number(minutes)) * 60 : 0;
  if (!prevu) return { classe: 'ok', texte: chronoLabel(t), reste: null };

  const classe = t >= prevu ? 'depasse' : t >= prevu * 0.8 ? 'proche' : 'ok';

  return {
    classe,
    texte: chronoLabel(t) + ' / ' + Math.floor(Number(minutes)) + ' min',
    reste: prevu - t,
  };
}

/**
 * Combien de personnes sont dans la salle, en toutes lettres.
 *
 * ⚠️ Le chiffre seul ne dit pas ce qu'il compte : la pastille n'affiche qu'un
 * nombre, et c'est cette phrase qui le nomme au survol comme au lecteur d'écran.
 * Le singulier vaut aussi pour zéro, comme le veut le français.
 */
export function phraseConnectes(n) {
  const t = Math.max(0, Math.floor(Number(n) || 0));

  return t > 1 ? t + ' personnes connectées' : t + ' personne connectée';
}
