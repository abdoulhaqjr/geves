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

/**
 * Nom d'un verdict de présence.
 *
 * ⚠️ **Deux verdicts, pas trois** : « Excusé » a été retiré. Il ne pesait rien en
 * aval (seul `present` compte dans le PV et dans l'attestation) et ajoutait un
 * choix à trancher pendant qu'on appelle des gens à la voix.
 */
export function presenceLabel(st) {
  return st === 'present' ? 'Présent' : st === 'absent' ? 'Absent' : '';
}

/** Les quatre étiquettes du panneau de présence, qui servent de filtres. */
export const PRESENCE_FILTRES = ['tous', 'present', 'absent', 'confirmer'];

/**
 * Les lignes que ce filtre laisse passer.
 *
 * `tous` répond aux « Attendus » : l'étiquette du total est le chemin de retour
 * vers la liste entière.
 */
export function presencesFiltrees(lignes, filtre) {
  const liste = (Array.isArray(lignes) ? lignes : []).filter(Boolean);

  if (filtre === 'present' || filtre === 'absent') return liste.filter((r) => r.status === filtre);
  if (filtre === 'confirmer') return liste.filter((r) => r.auto && r.status);

  return liste;
}

/**
 * Combien de lignes attendent encore le regard du modérateur.
 *
 * ⚠️ Défini PAR le filtre, et non à côté : le nombre sur l'étiquette et la
 * longueur de la liste qu'elle ouvre doivent être le même calcul, sinon le jour
 * où l'un des deux bouge, l'étiquette annonce un compte que la liste ne montre
 * pas.
 */
export function compteAConfirmer(lignes) {
  return presencesFiltrees(lignes, 'confirmer').length;
}

/**
 * Le filtre qu'on peut VRAIMENT appliquer à ces lignes.
 *
 * ⚠️ « À confirmer » disparaît dès qu'il n'y a plus rien à confirmer - et c'est
 * souvent le dernier clic du modérateur qui l'escamote, pendant qu'il la
 * regarde. Rester dessus laisserait une liste vide et plus aucune étiquette pour
 * en sortir.
 */
export function filtrePresenceTenable(filtre, lignes) {
  if (filtre === 'confirmer' && !compteAConfirmer(lignes)) return 'tous';

  return PRESENCE_FILTRES.indexOf(filtre) >= 0 ? filtre : 'tous';
}

/**
 * Ce qu'un clic sur un bouton de pointage doit envoyer, et de quel geste il
 * s'agit.
 *
 * ⚠️ **Un pointage automatique n'est pas un verdict.** Il dit que la personne
 * est entrée dans la salle, pas que le modérateur l'a constatée - et c'est lui
 * qui endosse le chiffre dans le PV. Cliquer « Présent » sur une ligne
 * automatique la **confirme** (elle devient de sa main), là où le même clic sur
 * une ligne qu'il a posée la **retire**. Sans cette distinction, le geste le
 * plus naturel - appuyer sur le verdict qu'on approuve - effacerait la présence
 * au lieu de l'endosser.
 *
 * @returns {{statut: string, geste: 'pose'|'confirme'|'retire'}} `statut` vide = retirer.
 */
export function gestePointage(statut, auto, bouton) {
  if (String(statut || '') !== bouton) return { statut: bouton, geste: 'pose' };
  if (auto) return { statut: bouton, geste: 'confirme' };

  return { statut: '', geste: 'retire' };
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
  // Le tableau blanc s'affiche en grand chez tout le monde et recouvre la
  // scène : l'ouvrir, y tracer, l'effacer ou le refermer appartient à qui
  // anime. Sans cette réserve, n'importe quel assistant masquerait le
  // présentateur en pleine communication, ou effacerait son schéma.
  board_open: 'presenter',
  board_close: 'presenter',
  board_pts: 'presenter',
  board_undo: 'presenter',
  board_clear: 'presenter',
  board_state: 'presenter',
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

/**
 * Profil de capture vidéo, nommé par l'édition.
 *
 * ⚠️ C'est la résolution de CAPTURE, donc la couche simulcast la plus HAUTE :
 * rien dans la salle ne peut être plus net qu'elle, quelle que soit la caméra.
 * La salle plafonnait à 960x540 à 24 i/s - en dessous du 720p que donne
 * n'importe quelle webcam récente.
 *
 * ⚠️ Les couches basses (180p, 360p) restent publiées à côté : ce sont elles que
 * reçoivent les petites vignettes et les connexions faibles, et c'est ce qui rend
 * une couche haute abordable. Monter le profil n'alourdit donc que la liaison
 * MONTANTE de celui qui diffuse - d'où un réglage par édition et non une valeur
 * unique pour toute la plateforme.
 *
 * ⚠️ Un nom inconnu retombe sur « md » : un réglage retiré du code ne doit pas
 * laisser la salle sans image.
 */
export function profilVideo(nom) {
  const profils = {
    sd: { largeur: 640, hauteur: 360, images: 24, debit: 500000 },
    // ⚠️ `md` est EXACTEMENT ce que la salle faisait avant d'être réglable, et
    // c'est redevenu le défaut. Porter la plateforme entière en 720p a doublé la
    // liaison montante demandée (≈1,45 Mb/s → ≈2,35 une fois les trois couches
    // additionnées), et les connexions modestes se sont mises à lâcher. Monter
    // en qualité reste possible - mais c'est un choix, pas un défaut imposé.
    md: { largeur: 960, hauteur: 540, images: 24, debit: 900000 },
    hd: { largeur: 1280, hauteur: 720, images: 30, debit: 1400000 },
    fhd: { largeur: 1920, hauteur: 1080, images: 30, debit: 2600000 },
  };

  return profils[String(nom ?? '')] || profils.md;
}

/* ===================== Réactions de salle ===================== */

/**
 * Bulles d'emoji qui montent sur la scène, visibles de tous.
 *
 * ⚠️ À ne pas confondre avec les réactions du fil de discussion
 * (`reactionDistante`) : celles-ci se posent SUR un message, se comptent et
 * survivent au rechargement. Une réaction de salle est un applaudissement :
 * elle passe, elle ne se compte pas, et rien ne la conserve.
 */

/**
 * Bulles affichées en même temps : au-delà, la plus ancienne cède la place.
 *
 * ⚠️ Ce nombre est celui des places que `placeBulle` sait distribuer, et les
 * deux doivent le rester : au-delà, la suite reboucle et deux bulles vivantes
 * se posent EXACTEMENT au même endroit.
 */
export const REACTIONS_MAX = 8;
/** Intervalle minimal entre deux réactions d'une même personne (ms). */
export const REACTION_DELAI = 1500;

/**
 * Vrai si cet emoji fait partie de ceux que la salle connaît.
 *
 * ⚠️ Le canal de données est public : n'importe quel client connecté peut
 * annoncer la réaction qu'il veut. Sans ce filtre, on afficherait en grand, chez
 * tout le monde, le texte arbitraire d'un participant - une tribune que personne
 * ne lui a donnée.
 */
export function reactionRecevable(emoji, autorises) {
  return Array.isArray(autorises) && autorises.indexOf(emoji) >= 0;
}

/**
 * Vrai si cette personne a le droit de réagir à nouveau.
 *
 * ⚠️ Garde-fou contre la rafale, appliqué des DEUX côtés : chez l'émetteur pour
 * le bouton, chez le récepteur par expéditeur - un client modifié ne se prive
 * pas d'un verrou qui ne vivrait que dans sa propre page.
 */
export function peutReagir(dernierAt, maintenant, delai = REACTION_DELAI) {
  if (!dernierAt) return true;
  const ecoule = maintenant - dernierAt;
  // ⚠️ Horloge reculée (changement d'heure, synchronisation réseau) : on
  // n'enferme personne pendant une heure. C'est un anti-rafale, pas un verrou.
  if (ecoule < 0) return true;

  return ecoule >= delai;
}

/**
 * Où poser la bulle de rang `n` : `{ gauche }` en % depuis la gauche de la
 * scène, `{ bas }` en pixels depuis le bas.
 *
 * ⚠️ Déterministe, et non tiré au sort : deux bulles tirées au même endroit se
 * superposent exactement et ne se lisent plus que comme une seule.
 *
 * ⚠️ Les deux suites sont solidaires, et c'est tout l'intérêt de les décider
 * ICI plutôt que dans la feuille de style. Mesuré dans la salle : huit colonnes
 * au-dessus de quatre rangées font retomber les rangs n et n+4 sur la MÊME
 * rangée - avec les colonnes « naturelles » (10, 30, 18, 42, 6…), les deux
 * bulles se chevauchaient et aucun des deux noms n'était lisible. Les colonnes
 * sont donc ordonnées pour que n et n+4 soient ÉLOIGNÉS, pas seulement
 * différents.
 */
export function placeBulle(rang) {
  // Huit places, autant que de bulles vivantes : deux d'entre elles ne tombent
  // jamais au même endroit. Deux rangs qui se suivent changent de rangée, et
  // les deux qui partagent une rangée sont à plus de 25% l'une de l'autre.
  // ⚠️ Les écarts sont mesurés au harnais, pas estimés : 42px entre deux
  // rangées, et 26% entre deux places d'une même rangée. La bulle n'est plus
  // qu'un emoji - c'est la carte de la personne qui dit de qui il vient - mais
  // la marge reste : elle rend l'animation lisible quand plusieurs réactions
  // partent en même temps, ce qui est précisément le moment où on applaudit.
  const places = [
    { gauche: 6, bas: 10 }, { gauche: 34, bas: 52 },
    { gauche: 18, bas: 94 }, { gauche: 46, bas: 136 },
    { gauche: 40, bas: 10 }, { gauche: 8, bas: 52 },
    { gauche: 46, bas: 94 }, { gauche: 20, bas: 136 },
  ];
  const n = Math.trunc(Number(rang) || 0);

  return places[((n % places.length) + places.length) % places.length];
}

/**
 * Faut-il remontrer l'écran d'entrée ?
 *
 * ⚠️ Il a sa raison d'être la PREMIÈRE fois : on tombait dans la salle et la
 * demande d'autorisation du navigateur arrivait par-dessus une séance déjà
 * commencée. Mais le reposer à chaque actualisation et à chaque reconnexion le
 * transforme en péage - on le franchit sans rien lire, et on le traverse
 * justement au pire moment, quand on vient de perdre la salle en pleine séance.
 *
 * ⚠️ Il revient dès que quelque chose n'est PAS acquis : première venue dans
 * cette salle, ou autorisation qui n'est plus accordée (refusée, révoquée, ou
 * simplement inconnue du navigateur). C'est exactement le cas où l'écran sert à
 * quelque chose - et c'est là qu'il dit quoi faire du cadenas.
 */
export function entreeDirecte(dejaVu, veutCam, veutMic, permCam, permMic) {
  if (!dejaVu) return false;
  // Entrer sans caméra ni micro ne demande aucune autorisation : rien à vérifier.
  if (!veutCam && !veutMic) return true;
  if (veutCam && permCam !== 'granted') return false;
  if (veutMic && permMic !== 'granted') return false;

  return true;
}
/* ===================== Tableau blanc ===================== */

/**
 * Le cadre de dessin : un rectangle de rapport FIXE, centré dans la scène.
 *
 * ⚠️ C'est la décision qui tient tout le reste. Un tableau est un **document** :
 * il doit être identique chez tout le monde. Si chaque poste normalisait les
 * points sur SA scène, le dessin se déformerait dès que deux participants n'ont
 * pas le même format d'écran - un cercle tracé sur un portable deviendrait une
 * ellipse sur l'écran d'à côté, et un schéma annoté deviendrait faux. Le cadre
 * garde donc son rapport et se centre, quitte à laisser des marges.
 */
export function cadreTableau(largeur, hauteur, ratio = 16 / 9) {
  const L = Math.max(0, Number(largeur) || 0);
  const H = Math.max(0, Number(hauteur) || 0);
  const r = Number(ratio) > 0 ? Number(ratio) : 16 / 9;
  if (!L || !H) return { x: 0, y: 0, w: 0, h: 0 };

  const parLaLargeur = L / r <= H;
  const w = parLaLargeur ? L : H * r;
  const h = parLaLargeur ? L / r : H;

  return { x: (L - w) / 2, y: (H - h) / 2, w, h };
}

/**
 * Point d'écran → repère du tableau (0..1 sur les deux axes).
 *
 * ⚠️ Borné : le doigt qui sort du cadre en plein trait ne doit pas envoyer des
 * coordonnées hors tableau, que les autres postes dessineraient par-dessus les
 * vignettes. Arrondi à trois décimales : au millième d'un cadre de 1280px, c'est
 * déjà sous le pixel, et un trait de 300 points pèse trois fois moins sur le
 * canal de données.
 */
export function pointTableau(x, y, cadre) {
  if (!cadre || !cadre.w || !cadre.h) return { x: 0, y: 0 };
  const borne = (v) => Math.min(1, Math.max(0, v));

  return {
    x: Math.round(borne((x - cadre.x) / cadre.w) * 1000) / 1000,
    y: Math.round(borne((y - cadre.y) / cadre.h) * 1000) / 1000,
  };
}

/**
 * Allège un trait : on ne garde un point que s'il s'éloigne assez du précédent.
 *
 * ⚠️ `pointermove` tire des centaines de points par seconde, dont la plupart à
 * moins d'un pixel l'un de l'autre. Sans ce filtre, un seul trait de tableau
 * dépasse la taille d'un message du canal de données - et l'instantané envoyé à
 * un retardataire n'a plus aucune chance de passer. Le DERNIER point est
 * toujours gardé par l'appelant : sans lui, le trait s'arrêterait avant la fin
 * du geste.
 */
export function traitAllege(points, minimum = 0.004) {
  const liste = Array.isArray(points) ? points : [];
  if (liste.length < 3) return liste.slice();
  const m = Number(minimum) > 0 ? Number(minimum) : 0.004;
  const gardes = [liste[0]];
  for (let i = 1; i < liste.length - 1; i += 1) {
    const d = liste[i];
    const p = gardes[gardes.length - 1];
    if (Math.abs(d.x - p.x) >= m || Math.abs(d.y - p.y) >= m) gardes.push(d);
  }
  gardes.push(liste[liste.length - 1]);

  return gardes;
}

/**
 * Découpe l'instantané du tableau en messages digestes.
 *
 * ⚠️ Le canal de données n'accepte pas un message de n'importe quelle taille.
 * Un tableau d'une heure de séance pèse bien plus que la limite : envoyé d'un
 * bloc à un retardataire, il n'arrive pas du tout, et celui-ci voit un tableau
 * vide pendant que les autres le regardent plein. On découpe donc par nombre de
 * POINTS et non de traits : un seul trait long pèse plus que vingt traits
 * courts.
 */
export function lotsDeTraits(traits, maxPoints = 400) {
  const liste = Array.isArray(traits) ? traits : [];
  const max = Number(maxPoints) > 0 ? Number(maxPoints) : 400;
  const lots = [];
  let lot = [];
  let n = 0;
  liste.forEach((t) => {
    const taille = (t && Array.isArray(t.pts) ? t.pts.length : 0) || 1;
    // ⚠️ Un trait plus long que le lot part SEUL : le couper en deux messages
    // en ferait deux traits distincts, avec un blanc au milieu.
    if (lot.length && n + taille > max) { lots.push(lot); lot = []; n = 0; }
    lot.push(t); n += taille;
  });
  if (lot.length) lots.push(lot);

  return lots;
}

/** Points au-delà desquels un trait reçu est tronqué. */
export const TRAIT_POINTS_MAX = 4000;

/**
 * Assainit un trait venu d'un autre poste. Rend `null` si rien n'est exploitable.
 *
 * ⚠️ Le canal de données est public, et `ordreRecevable` ne dit QUE d'où vient
 * le message - pas ce qu'il contient. Sans ce filtre, un client modifié traçait
 * sur le tableau de tout le monde avec une épaisseur de mille pixels (plus rien
 * d'autre n'est visible), hors du cadre (par-dessus les vignettes), ou avec une
 * couleur de son choix. Un tableau est un document commun : on n'y écrit qu'avec
 * les crayons de la salle.
 */
export function traitRecu(msg, couleurs) {
  if (!msg || typeof msg.id !== 'string' || !msg.id || msg.id.length > 120) return null;
  const permises = Array.isArray(couleurs) ? couleurs : [];
  const c = permises.indexOf(msg.c) >= 0 ? msg.c : permises[0];
  if (!c) return null;
  const e = Math.min(60, Math.max(1, Number(msg.e) || 1));
  const bruts = Array.isArray(msg.pts) ? msg.pts.slice(0, TRAIT_POINTS_MAX) : [];
  const pts = [];
  bruts.forEach((p) => {
    if (!p) return;
    const x = Number(p.x);
    const y = Number(p.y);
    // ⚠️ `Number.isFinite` et non `!isNaN` : `Infinity` passe le second, et une
    // coordonnée infinie fige le rendu du canevas chez tout le monde.
    if (!Number.isFinite(x) || !Number.isFinite(y)) return;
    pts.push({ x: Math.min(1, Math.max(0, x)), y: Math.min(1, Math.max(0, y)) });
  });

  return { id: msg.id, c, e, pts, net: !!msg.net, fin: !!msg.fin };
}

/**
 * Le rectangle réellement occupé par une vidéo dans sa boîte, en `contain`.
 *
 * ⚠️ C'est le repère des ANNOTATIONS, et il doit être calculable à l'identique
 * chez tout le monde. Il l'est parce qu'il ne dépend que de deux choses : la
 * taille INTRINSÈQUE du média (la même pour tous, elle vient de l'émetteur) et
 * la boîte locale. Chacun calcule son propre rectangle, et un trait posé au
 * tiers de l'image tombe au tiers de l'image partout.
 *
 * ⚠️ `contain` et non `cover` : en `cover`, l'image est ROGNÉE, et elle ne l'est
 * pas pareil selon le format de la fenêtre. Deux personnes ne verraient alors
 * pas la même portion du document, et la flèche de l'une tomberait à côté chez
 * l'autre. C'est pourquoi la salle force `contain` pendant qu'on annote.
 *
 * ⚠️ Taille intrinsèque inconnue (image pas encore arrivée) : on rend une boîte
 * vide plutôt qu'un rectangle inventé. L'appelant retombe alors sur le cadre
 * 16/9 du tableau - mieux vaut un cadre franc qu'un cadre faux.
 */
export function cadreMedia(boiteL, boiteH, mediaL, mediaH) {
  const BL = Math.max(0, Number(boiteL) || 0);
  const BH = Math.max(0, Number(boiteH) || 0);
  const ML = Math.max(0, Number(mediaL) || 0);
  const MH = Math.max(0, Number(mediaH) || 0);
  if (!BL || !BH || !ML || !MH) return { x: 0, y: 0, w: 0, h: 0 };

  const parLaLargeur = BL / (ML / MH) <= BH;
  const w = parLaLargeur ? BL : BH * (ML / MH);
  const h = parLaLargeur ? BL / (ML / MH) : BH;

  return { x: (BL - w) / 2, y: (BH - h) / 2, w, h };
}

/* ===================== Échanges ===================== */

/**
 * Les questions que ce filtre laisse passer.
 *
 * ⚠️ À trente questions, la file devient illisible : celle qu'on cherche - la
 * sienne, ou celle qui attend encore - est noyée au milieu de ce qui est déjà
 * traité. Le tri par votes vient du serveur ; ceci ne fait que RESTREINDRE, et
 * ne réordonne rien.
 *
 * ⚠️ « Les miennes » s'appuie sur `mine`, que le serveur calcule : un visiteur
 * anonyme n'a aucune question à lui, et la liste rendue vide le dit mieux
 * qu'une liste pleine qui ne serait pas la sienne.
 */
export function questionsFiltrees(questions, filtre) {
  const liste = Array.isArray(questions) ? questions : [];
  if (filtre === 'attente') return liste.filter((q) => q && q.status === 'pending');
  if (filtre === 'miennes') return liste.filter((q) => q && q.mine);

  return liste.slice();
}

/**
 * Ce que le pastille du bouton « Échanges » doit annoncer.
 *
 * ⚠️ Ce qui ATTEND une réponse, et non le total : une file de quarante
 * questions dont trente-huit sont traitées n'appelle pas l'animateur. C'est le
 * reste à faire qui doit sauter aux yeux.
 */
export function compteEnAttente(questions) {
  return (Array.isArray(questions) ? questions : []).filter((q) => q && q.status === 'pending').length;
}

/**
 * Rang du dernier trait posé par cet auteur, ou -1.
 *
 * ⚠️ Chaque trait porte l'identité de son auteur dans son `id` : on ne défait
 * JAMAIS le trait de quelqu'un d'autre. Deux personnes annotent la même
 * présentation, et « Annuler » doit rester le geste qui reprend SON trait - pas
 * celui qui efface par surprise la flèche du voisin.
 *
 * ⚠️ Le préfixe se compare avec son tiret (`alice-`), sinon l'identité `alice`
 * reprendrait aussi les traits d'`alice2`.
 */
export function indexDuDernierTraitDe(traits, auteur) {
  const liste = Array.isArray(traits) ? traits : [];
  const prefixe = String(auteur ?? '') + '-';
  if (prefixe === '-') return -1;
  for (let i = liste.length - 1; i >= 0; i -= 1) {
    const t = liste[i];
    if (t && typeof t.id === 'string' && t.id.indexOf(prefixe) === 0) return i;
  }

  return -1;
}

/**
 * Qui tient le crayon du tableau.
 *
 * ⚠️ Pas « tout présentateur » : une séance compte plusieurs communicateurs, et
 * tous portent le rôle `presenter` pendant toute la séance. Ils pouvaient donc
 * tous écrire sur le diaporama de celui qui parle. Le crayon suit **la parole** -
 * celui qui présente, ou qui partage son écran en ce moment.
 *
 * ⚠️ Le modérateur garde la main : il conduit la séance, coupe les micros et
 * donne la parole. Lui refuser le crayon rendrait le tableau blanc inutilisable
 * tant que personne ne présente - et c'est précisément le moment où l'on veut
 * dessiner quelque chose.
 *
 * ⚠️ Ceci est une règle de CONDUITE, pas une barrière : la frontière vérifiable
 * reste le rôle signé dans le jeton (`ORDRES_RESERVES`), qui interdit déjà le
 * tableau à l'assistance. Qui tient la parole se décide sur le canal de données,
 * donc ne s'authentifie pas - et un récepteur qui filtrerait là-dessus écarterait
 * des traits légitimes dès que sa vue de la parole diffère d'un poste à l'autre.
 * Un tableau qui perd des traits en silence est pire que le mal qu'on soigne.
 */
export function droitDeTracer(role, partageSonEcran, aLaParole) {
  if (role === 'moderator') return true;
  if (role !== 'presenter') return false;

  return !!partageSonEcran || !!aLaParole;
}

/**
 * La personne que la salle désigne comme présentateur.
 *
 * ⚠️ Cette définition doit être UNIQUE. La scène et le crayon la calculaient
 * chacun de leur côté : la scène prenait « parole donnée OU rôle présentateur »,
 * le crayon exigeait la parole donnée. Un communicateur affiché « Présentateur »
 * en grand sur la scène trouvait donc le bouton Tableau grisé - la salle lui
 * disait deux choses contraires en même temps.
 *
 * ⚠️ `isFloor` d'abord, puis le rôle : au début d'une séance, le serveur donne
 * `presenter` à TOUS les auteurs de communication. Tant que la parole n'a été
 * donnée à personne, c'est le premier de la liste qui tient la scène - et c'est
 * bien lui qu'on y voit.
 */
export function celuiQuiPresente(gens) {
  const liste = Array.isArray(gens) ? gens : [];

  return liste.find((p) => p && p.isFloor) || liste.find((p) => p && p.role === 'presenter') || null;
}
