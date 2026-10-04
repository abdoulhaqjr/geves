import { describe, expect, it } from 'vitest';

import {
  cadreMedia,
  cadreTableau,
  celuiQuiPresente,
  chronoLabel,
  compteEnAttente,
  droitDeTracer,
  enBasDuFil,
  entreeDirecte,
  etatChrono,
  grilleOptimale,
  idSur,
  idsDesParleurs,
  indexDuDernierTraitDe,
  initials,
  mainsOrdonnees,
  lotsDeTraits,
  metadonnee,
  peutReagir,
  placeBulle,
  pointTableau,
  REACTIONS_MAX,
  reactionRecevable,
  TRAIT_POINTS_MAX,
  traitAllege,
  traitRecu,
  niveauReseau,
  ordreRecevable,
  phraseConnectes,
  profilVideo,
  questionsFiltrees,
  presenceLabel,
  gestePointage,
  compteAConfirmer,
  presencesFiltrees,
  filtrePresenceTenable,
  rangsDesMains,
  roleLabel,
  teinteDuNom,
  texteCourt,
} from './meeting-pure.js';

// Ces cas ne sont pas décoratifs : chacun fige un défaut qui a été constaté dans
// une vraie salle, et que rien n'empêchait de revenir.

describe('initiales', () => {
  it('prend la première lettre de deux mots', () => {
    expect(initials('Awa Kodjo')).toBe('AK');
    expect(initials('Organisation')).toBe('O');
  });

  it('ignore tout ce qui n’est pas une lettre ou un chiffre', () => {
    // ⚠️ Les initiales atterrissent dans un gabarit construit en `innerHTML` :
    // le « < » d'un nom hostile y ouvrirait une balise.
    expect(initials('<img src=x onerror=alert(1)>')).toBe('IS');
    expect(initials('   ')).toBe('?');
    expect(initials(null)).toBe('?');
  });

  it('garde les accents, qui sont des lettres', () => {
    expect(initials('Élodie Ávila')).toBe('ÉÁ');
  });
});

describe('identifiant de message', () => {
  it('ne garde que l’alphabet attendu et borne la longueur', () => {
    expect(idSur('abc123')).toBe('abc123');
    // Il sert d'`id` dans le DOM et de morceau d'URL.
    expect(idSur('../../etc/passwd')).toBe('etcpasswd');
    expect(idSur('a'.repeat(50))).toHaveLength(24);
    expect(idSur(null)).toBe('');
  });
});

describe('texte raccourci', () => {
  it('replie les espaces et coupe avec des points de suspension', () => {
    expect(texteCourt('  Bonjour   la   salle ', 40)).toBe('Bonjour la salle');
    expect(texteCourt('Bonjour la salle', 10)).toBe('Bonjour l…');
    expect(texteCourt(null, 10)).toBe('');
  });
});

describe('métadonnée du jeton', () => {
  it('lit le rôle et le compte annoncés par le serveur', () => {
    expect(metadonnee({ metadata: '{"role":"moderator","accountId":7}' })).toEqual({
      role: 'moderator',
      accountId: 7,
    });
  });

  it('retombe sur « audience » devant un rôle inventé ou absent', () => {
    // ⚠️ Un champ inventé ne doit donner aucun pouvoir.
    expect(metadonnee({ metadata: '{"role":"admin"}' }).role).toBe('audience');
    expect(metadonnee({ metadata: 'pas du json' }).role).toBe('audience');
    expect(metadonnee({}).role).toBe('audience');
    expect(metadonnee(null).role).toBe('audience');
  });
});

describe('ordres reçus sur le canal de données', () => {
  it('réserve au modérateur ce qui engage toute la salle', () => {
    // ⚠️ Le défaut : n'importe quel participant pouvait couper le micro de
    // toute la salle depuis la console de son navigateur.
    expect(ordreRecevable('mod_mute', 'audience')).toBe(false);
    expect(ordreRecevable('mod_mute', 'presenter')).toBe(false);
    expect(ordreRecevable('mod_mute', 'moderator')).toBe(true);

    expect(ordreRecevable('comm_done', 'presenter')).toBe(false);
    expect(ordreRecevable('chat_removed', 'audience')).toBe(false);
    expect(ordreRecevable('recording', 'moderator')).toBe(true);
  });

  it('laisse le partage d’écran à ceux qui diffusent', () => {
    expect(ordreRecevable('screen_takeover', 'audience')).toBe(false);
    expect(ordreRecevable('screen_takeover', 'presenter')).toBe(true);
    expect(ordreRecevable('screen_takeover', 'moderator')).toBe(true);
  });

  it('laisse passer ce qui appartient à tout le monde', () => {
    expect(ordreRecevable('chat', 'audience')).toBe(true);
    expect(ordreRecevable('hand', 'audience')).toBe(true);
    expect(ordreRecevable('reaction', 'audience')).toBe(true);
  });
});

describe('file des mains levées', () => {
  const gens = () => [
    { id: 'a', hand: true, handAt: 2 },
    { id: 'b', hand: false, handAt: 0 },
    { id: 'c', hand: true, handAt: 1 },
  ];

  it('ordonne par ordre d’arrivée, pas par position dans la liste', () => {
    expect(mainsOrdonnees(gens()).map((p) => p.id)).toEqual(['c', 'a']);
    expect(rangsDesMains(gens()).get('c')).toBe(1);
    expect(rangsDesMains(gens()).get('a')).toBe(2);
  });

  it('renumérote quand une main se baisse, sans laisser de trou', () => {
    // ⚠️ C'est tout l'intérêt de recalculer le rang à chaque affichage.
    const apres = gens().map((p) => (p.id === 'c' ? { ...p, hand: false, handAt: 0 } : p));
    expect(rangsDesMains(apres).get('a')).toBe(1);
    expect(rangsDesMains(apres).has('c')).toBe(false);
  });

  it('ne compte pas une main levée sans rang', () => {
    expect(mainsOrdonnees([{ id: 'x', hand: true }])).toEqual([]);
    expect(mainsOrdonnees(null)).toEqual([]);
  });
});

describe('grille de la mosaïque', () => {
  it('évite la rangée unique qui écrase les visages', () => {
    // ⚠️ Le défaut d'origine : quatre personnes sur une seule rangée, des
    // tuiles hautes et étroites, un visage rogné jusqu'au nez.
    expect(grilleOptimale(4, 1200, 700)).toEqual({ cols: 2, rows: 2 });
  });

  it('garde une colonne pour une seule personne, et sur un écran étroit', () => {
    expect(grilleOptimale(1, 1200, 700)).toEqual({ cols: 1, rows: 1 });
    expect(grilleOptimale(2, 359, 515)).toEqual({ cols: 1, rows: 2 });
  });

  it('centre la rangée incomplète en ne réclamant pas plus de colonnes que de gens', () => {
    const g = grilleOptimale(3, 1200, 700);
    expect(g.cols).toBeLessThanOrEqual(3);
    expect(g.cols * g.rows).toBeGreaterThanOrEqual(3);
  });

  it('ne divise pas par zéro quand la scène n’est pas encore mesurée', () => {
    expect(grilleOptimale(0, 0, 0)).toEqual({ cols: 1, rows: 1 });
    expect(grilleOptimale(3, 0, 0).cols).toBe(1);
  });
});

describe('intitulés', () => {
  it('nomme les rôles et les verdicts', () => {
    expect(roleLabel('presenter')).toBe('Communicateur');
    expect(roleLabel('moderator')).toBe('Modérateur');
    expect(roleLabel('n’importe quoi')).toBe('Auditeur');
    expect(presenceLabel('present')).toBe('Présent');
    expect(presenceLabel('absent')).toBe('Absent');
    // « Excusé » a été retiré : il ne pesait rien en aval et ajoutait un choix
    // à trancher pendant qu'on appelle des gens à la voix.
    expect(presenceLabel('excused')).toBe('');
    expect(presenceLabel('')).toBe('');
  });
});

describe('pointage de présence', () => {
  it('confirme un pointage automatique au lieu de l’effacer', () => {
    // ⚠️ LE défaut que ce cas gèle : la visio porte quelqu'un présent, le
    // modérateur appuie sur « Présent » pour l'approuver... et l'ancien bascule
    // le retirait. Le geste le plus naturel effaçait la présence.
    expect(gestePointage('present', true, 'present')).toEqual({ statut: 'present', geste: 'confirme' });
  });

  it('retire son propre verdict quand on le rappuie', () => {
    // C'est le moyen de défaire une erreur sans quitter la salle.
    expect(gestePointage('present', false, 'present')).toEqual({ statut: '', geste: 'retire' });
  });

  it('change de verdict d’un seul clic, confirmé ou non', () => {
    expect(gestePointage('present', true, 'absent')).toEqual({ statut: 'absent', geste: 'pose' });
    expect(gestePointage('absent', false, 'present')).toEqual({ statut: 'present', geste: 'pose' });
    expect(gestePointage('', false, 'present')).toEqual({ statut: 'present', geste: 'pose' });
  });

  it('traite une ligne non pointée comme vide, quoi qu’envoie le serveur', () => {
    expect(gestePointage(null, false, 'present').statut).toBe('present');
    expect(gestePointage(undefined, true, 'absent').statut).toBe('absent');
  });

  it('filtre sur chaque étiquette, et rend tout le monde sur « Attendus »', () => {
    const lignes = [
      { id: 1, status: 'present', auto: true },
      { id: 2, status: 'present', auto: false },
      { id: 3, status: 'absent', auto: false },
      { id: 4, status: '', auto: false },
    ];
    expect(presencesFiltrees(lignes, 'present').map((r) => r.id)).toEqual([1, 2]);
    expect(presencesFiltrees(lignes, 'absent').map((r) => r.id)).toEqual([3]);
    expect(presencesFiltrees(lignes, 'confirmer').map((r) => r.id)).toEqual([1]);
    // « Attendus » est le chemin de retour : il ne retire personne, pas même les
    // non pointés - ce sont EUX qu'il reste à appeler.
    expect(presencesFiltrees(lignes, 'tous').map((r) => r.id)).toEqual([1, 2, 3, 4]);
    expect(presencesFiltrees(null, 'present')).toEqual([]);
  });

  it('quitte « À confirmer » quand l’étiquette s’efface', () => {
    // ⚠️ C'est souvent le DERNIER clic du modérateur qui escamote l'étiquette,
    // pendant qu'il la regarde : rester dessus laisserait une liste vide et plus
    // aucune étiquette pour en sortir.
    const restantes = [{ status: 'present', auto: true }];
    expect(filtrePresenceTenable('confirmer', restantes)).toBe('confirmer');
    expect(filtrePresenceTenable('confirmer', [{ status: 'present', auto: false }])).toBe('tous');
    expect(filtrePresenceTenable('confirmer', [])).toBe('tous');
    // Un filtre inconnu (ancien onglet laissé ouvert, valeur bricolée) rend tout.
    expect(filtrePresenceTenable('excused', restantes)).toBe('tous');
    expect(filtrePresenceTenable('absent', restantes)).toBe('absent');
  });

  it('ne compte à confirmer que les lignes automatiques POSÉES', () => {
    // ⚠️ `auto` sans verdict n'existe pas côté serveur, mais une ligne vide
    // comptée « à confirmer » demanderait au modérateur de confirmer le vide.
    expect(compteAConfirmer([
      { auto: true, status: 'present' },
      { auto: true, status: '' },
      { auto: false, status: 'present' },
      { auto: true, status: 'absent' },
    ])).toBe(2);
    expect(compteAConfirmer(null)).toBe(0);
    expect(compteAConfirmer([])).toBe(0);
  });
});

describe('qui parle', () => {
  it('traduit son propre identifiant en « local »', () => {
    // ⚠️ Sans cette traduction, celui qui parle est repéré pour tout le monde
    // SAUF pour lui-même : sa vignette et sa ligne portent l'identifiant `local`.
    const ids = idsDesParleurs([{ identity: 'moi' }, { identity: 'awa' }], 'moi');
    expect([...ids].sort()).toEqual(['awa', 'local']);
  });

  it('ne retient rien quand plus personne ne parle', () => {
    expect(idsDesParleurs([], 'moi').size).toBe(0);
    expect(idsDesParleurs(null, 'moi').size).toBe(0);
    expect(idsDesParleurs([{}, null], 'moi').size).toBe(0);
  });
});

describe('réseau d’un participant', () => {
  it('ne dit quelque chose que sur un ennui', () => {
    // ⚠️ « Excellent » posé en permanence sur tout le monde ne veut plus rien
    // dire : l'indicateur doit se remarquer le jour où il apparaît. `unknown`
    // est l'état des premières secondes de chaque arrivée.
    expect(niveauReseau('excellent')).toBeNull();
    expect(niveauReseau('good')).toBeNull();
    expect(niveauReseau('unknown')).toBeNull();
    expect(niveauReseau(undefined)).toBeNull();

    expect(niveauReseau('poor').intitule).toBe('Réseau instable');
    expect(niveauReseau('lost').classe).toBe('perdu');
  });
});

describe('fil collé au bas', () => {
  it('laisse quelques pixels de jeu', () => {
    // 1 000 px de fil dans une fenêtre de 400 : le bas, c'est 600.
    expect(enBasDuFil(1000, 600, 400)).toBe(true);
    expect(enBasDuFil(1000, 540, 400)).toBe(true);
    // Là, on lit plus haut : un message qui arrive ne doit rien recadrer.
    expect(enBasDuFil(1000, 200, 400)).toBe(false);
  });

  it('est vrai sur un fil qui ne défile pas encore', () => {
    // ⚠️ À l'ouverture, le fil est vide ou tient dans sa fenêtre. S'il n'était
    // pas « collé », la salle s'ouvrirait sur un bandeau « nouveaux messages »
    // pour des messages qu'on n'a jamais eu l'occasion de ne pas lire.
    expect(enBasDuFil(0, 0, 0)).toBe(true);
    expect(enBasDuFil(300, 0, 300)).toBe(true);
  });
});

describe('teinte de l’avatar', () => {
  it('donne toujours la même couleur à la même personne', () => {
    // ⚠️ C'est tout l'intérêt : on reconnaît quelqu'un à sa pastille, dans la
    // mosaïque comme dans la liste, et d'une séance à l'autre.
    expect(teinteDuNom('Awa Kodjo')).toBe(teinteDuNom('Awa Kodjo'));
    expect(teinteDuNom('Awa Kodjo')).toBe(teinteDuNom('  awa kodjo  '));
  });

  it('sépare des noms voisins', () => {
    expect(teinteDuNom('Awa Kodjo')).not.toBe(teinteDuNom('Awa Kodja'));
    expect(teinteDuNom('Moussa Mensah')).not.toBe(teinteDuNom('Moussa Mensa'));
  });

  it('reste dans le tour des teintes, et retombe sur le bleu sans nom', () => {
    ['Élodie', '组织', '<img src=x>', 'a'.repeat(200)].forEach((n) => {
      const h = teinteDuNom(n);
      expect(h).toBeGreaterThanOrEqual(0);
      expect(h).toBeLessThan(360);
      expect(Number.isInteger(h)).toBe(true);
    });
    expect(teinteDuNom('')).toBe(225);
    expect(teinteDuNom(null)).toBe(225);
  });
});

describe('temps de parole', () => {
  it('s’écrit comme une pendule', () => {
    expect(chronoLabel(0)).toBe('00:00');
    expect(chronoLabel(72)).toBe('01:12');
    expect(chronoLabel(3725)).toBe('1:02:05');
    // Une horloge qui recule n'existe pas.
    expect(chronoLabel(-5)).toBe('00:00');
    expect(chronoLabel(null)).toBe('00:00');
  });

  it('prévient AVANT le dépassement, pas au moment où il est trop tard', () => {
    // ⚠️ Un créneau de 15 min : l'alerte tombe à 12, quand il reste de quoi
    // abréger - pas à 15, où la personne conclut déjà.
    expect(etatChrono(11 * 60, 15).classe).toBe('ok');
    expect(etatChrono(12 * 60, 15).classe).toBe('proche');
    expect(etatChrono(15 * 60, 15).classe).toBe('depasse');
    expect(etatChrono(20 * 60, 15).texte).toBe('20:00 / 15 min');
  });

  it('compte sans alerter quand le créneau n’a pas d’horaire', () => {
    // ⚠️ Rien à dépasser : une couleur d'alarme ferait écourter une
    // communication qui tient parfaitement son temps.
    const sans = etatChrono(42 * 60, null);
    expect(sans.classe).toBe('ok');
    expect(sans.texte).toBe('42:00');
    expect(sans.reste).toBeNull();
    expect(etatChrono(60, 0).texte).toBe('01:00');
  });
});

describe('le chronomètre engage la salle', () => {
  it('n’est annonçable que par le modérateur', () => {
    // ⚠️ Sinon n'importe qui affiche « 25 min » chez tout le monde et fait
    // écourter une communication dans les temps.
    expect(ordreRecevable('floor_time', 'audience')).toBe(false);
    expect(ordreRecevable('floor_time', 'presenter')).toBe(false);
    expect(ordreRecevable('floor_time', 'moderator')).toBe(true);
  });
});

describe('nombre de connectés', () => {
  it('accorde le pluriel, et le garde au singulier pour zéro et un', () => {
    expect(phraseConnectes(0)).toBe('0 personne connectée');
    expect(phraseConnectes(1)).toBe('1 personne connectée');
    expect(phraseConnectes(2)).toBe('2 personnes connectées');
    expect(phraseConnectes(42)).toBe('42 personnes connectées');
  });

  it('ne compte jamais en négatif ni en virgule', () => {
    expect(phraseConnectes(-3)).toBe('0 personne connectée');
    expect(phraseConnectes(null)).toBe('0 personne connectée');
    expect(phraseConnectes(2.8)).toBe('2 personnes connectées');
  });
});

describe('profil de capture vidéo', () => {
  it('propose de monter la salle au-dessus de son ancien plafond', () => {
    // ⚠️ La salle capturait 960x540 à 24 i/s, et c'était la couche la plus haute :
    // rien ne pouvait y être plus net, quelle que soit la caméra.
    // ⚠️ Le défaut est REVENU à ce que la salle faisait avant d'être réglable.
    // Porter toute la plateforme en 720p a doublé la liaison montante demandée,
    // et les connexions modestes se sont mises à lâcher : ce n'est plus une
    // image qui saccade, c'est la salle qu'on perd. Monter reste possible,
    // mais c'est un choix, pas un défaut imposé.
    expect(profilVideo('md')).toEqual({ largeur: 960, hauteur: 540, images: 24, debit: 900000 });
    expect(profilVideo('hd').hauteur).toBeGreaterThan(540);
  });

  it('laisse le choix d’économiser la liaison montante', () => {
    expect(profilVideo('sd').hauteur).toBe(360);
    expect(profilVideo('fhd').hauteur).toBe(1080);
    // Le débit suit la définition, sinon la couche haute sort en bouillie.
    expect(profilVideo('sd').debit).toBeLessThan(profilVideo('md').debit);
    expect(profilVideo('md').debit).toBeLessThan(profilVideo('hd').debit);
    expect(profilVideo('hd').debit).toBeLessThan(profilVideo('fhd').debit);
  });

  it('retombe sur « md » devant un nom inconnu', () => {
    // ⚠️ Un réglage saisi hier et retiré du code demain ne doit pas laisser la
    // salle sans image.
    expect(profilVideo('ultra')).toEqual(profilVideo('md'));
    expect(profilVideo(null)).toEqual(profilVideo('md'));
    expect(profilVideo(undefined)).toEqual(profilVideo('md'));
  });
});

describe('réactions de salle', () => {
  it('n’affiche que les emojis que la salle connaît', () => {
    // ⚠️ Le canal est public : sans ce filtre, n'importe quel connecté
    // afficherait en grand, chez tout le monde, le texte de son choix.
    const permis = ['👍', '👏'];
    expect(reactionRecevable('👏', permis)).toBe(true);
    expect(reactionRecevable('🤬', permis)).toBe(false);
    expect(reactionRecevable('<img src=x onerror=1>', permis)).toBe(false);
    expect(reactionRecevable('👏', null)).toBe(false);
  });

  it('espace deux réactions d’une même personne', () => {
    expect(peutReagir(0, 10_000)).toBe(true);
    expect(peutReagir(10_000, 10_200, 1500)).toBe(false);
    expect(peutReagir(10_000, 11_500, 1500)).toBe(true);
  });

  it('ne verrouille pas le bouton quand l’horloge recule', () => {
    // ⚠️ Changement d'heure ou synchronisation réseau : un simple « écoulé >=
    // délai » enfermerait la personne pour une heure.
    expect(peutReagir(10_000, 4_000, 1500)).toBe(true);
  });

  it('donne une place distincte à chaque bulle vivante', () => {
    // ⚠️ Mesuré dans la salle : quand la suite des places reboucle avant le
    // plafond, deux bulles encore à l'écran se posent EXACTEMENT l'une sur
    // l'autre. Les deux nombres sont donc solidaires.
    const vues = new Set();
    for (let i = 0; i < REACTIONS_MAX; i += 1) {
      const p = placeBulle(i);
      vues.add(p.gauche + '/' + p.bas);
    }
    expect(vues.size).toBe(REACTIONS_MAX);
  });

  it('écarte les bulles qui partagent une rangée', () => {
    // Deux rangs qui se suivent ne partagent jamais la rangée.
    for (let i = 0; i < 16; i += 1) {
      expect(placeBulle(i).bas).not.toBe(placeBulle(i + 1).bas);
    }
    // ⚠️ Et quand ils la partagent, leurs colonnes ne doivent pas seulement
    // différer : elles doivent s'écarter, sinon deux emojis partis ensemble se
    // chevauchent - c'est ce que montrait le harnais visuel.
    for (let i = 0; i < REACTIONS_MAX; i += 1) {
      for (let j = i + 1; j < REACTIONS_MAX; j += 1) {
        const a = placeBulle(i);
        const b = placeBulle(j);
        if (a.bas !== b.bas) continue;
        expect(Math.abs(b.gauche - a.gauche)).toBeGreaterThanOrEqual(25);
      }
    }
  });

  it('garde la bulle sur la scène, quel que soit le rang', () => {
    // La suite boucle, et un rang douteux ne jette pas la bulle hors de l'écran.
    expect(placeBulle(REACTIONS_MAX)).toEqual(placeBulle(0));
    expect(placeBulle(null)).toEqual(placeBulle(0));
    expect(placeBulle(-1).gauche).toBeGreaterThanOrEqual(0);
    for (let i = 0; i < 40; i += 1) {
      // Au-delà de 48%, la bulle sortirait du bord droit de la scène.
      expect(placeBulle(i).gauche).toBeGreaterThanOrEqual(0);
      expect(placeBulle(i).gauche).toBeLessThanOrEqual(48);
      expect(placeBulle(i).bas).toBeGreaterThanOrEqual(10);
    }
  });
});

describe('tableau blanc', () => {
  it('garde le même cadre sur deux écrans de formats différents', () => {
    // ⚠️ Le cœur du sujet : un tableau est un document. Un cercle tracé sur un
    // portable doit rester un cercle sur l'écran d'à côté.
    const large = cadreTableau(1600, 600); // scène très large : marges latérales
    const haute = cadreTableau(800, 900); // scène haute : marges haut et bas
    expect(large.w / large.h).toBeCloseTo(16 / 9, 5);
    expect(haute.w / haute.h).toBeCloseTo(16 / 9, 5);
    // Le cadre tient dans la scène, et il est centré.
    expect(large.w).toBeLessThanOrEqual(1600);
    expect(large.h).toBeLessThanOrEqual(600);
    expect(large.x).toBeCloseTo((1600 - large.w) / 2, 5);
    expect(haute.y).toBeCloseTo((900 - haute.h) / 2, 5);
  });

  it('ne rend pas un cadre impossible', () => {
    expect(cadreTableau(0, 500)).toEqual({ x: 0, y: 0, w: 0, h: 0 });
    expect(cadreTableau(NaN, NaN)).toEqual({ x: 0, y: 0, w: 0, h: 0 });
    // Un rapport absurde retombe sur le 16/9 plutôt que de rendre un cadre nul.
    expect(cadreTableau(1600, 900, 0).w / cadreTableau(1600, 900, 0).h).toBeCloseTo(16 / 9, 5);
  });

  it('place le même point au même endroit chez les deux', () => {
    const a = cadreTableau(1600, 600);
    const b = cadreTableau(800, 900);
    // Le centre du cadre, chez l'un et chez l'autre.
    const pa = pointTableau(a.x + a.w / 2, a.y + a.h / 2, a);
    const pb = pointTableau(b.x + b.w / 2, b.y + b.h / 2, b);
    expect(pa).toEqual(pb);
  });

  it('retient le doigt qui sort du cadre', () => {
    // ⚠️ Sans borne, un geste qui déborde enverrait des coordonnées hors tableau,
    // que les autres postes dessineraient par-dessus les vignettes.
    const c = cadreTableau(1600, 900);
    expect(pointTableau(c.x - 500, c.y - 500, c)).toEqual({ x: 0, y: 0 });
    expect(pointTableau(c.x + c.w + 500, c.y + c.h + 500, c)).toEqual({ x: 1, y: 1 });
    expect(pointTableau(10, 10, { x: 0, y: 0, w: 0, h: 0 })).toEqual({ x: 0, y: 0 });
  });

  it('allège un trait sans lui couper la fin', () => {
    // ⚠️ `pointermove` tire des points à moins d'un pixel l'un de l'autre : sans
    // filtre, un seul trait dépasse la taille d'un message du canal.
    const points = [];
    for (let i = 0; i < 200; i += 1) points.push({ x: i / 10000, y: 0 });
    points.push({ x: 0.9, y: 0.9 });
    const allege = traitAllege(points, 0.01);
    expect(allege.length).toBeLessThan(points.length / 4);
    // ⚠️ Le dernier point survit toujours : sinon le trait s'arrête avant la fin
    // du geste, et la ligne ne rejoint pas là où on a levé le crayon.
    expect(allege[allege.length - 1]).toEqual({ x: 0.9, y: 0.9 });
    expect(allege[0]).toEqual(points[0]);
    // Un trait trop court pour être allégé revient intact.
    expect(traitAllege([{ x: 0, y: 0 }, { x: 1, y: 1 }])).toHaveLength(2);
  });

  it('découpe l’instantané par points, pas par traits', () => {
    // ⚠️ Un tableau d'une heure dépasse la taille d'un message : envoyé d'un
    // bloc, il n'arrive pas du tout et le retardataire voit un tableau vide.
    const long = { pts: new Array(500).fill({ x: 0, y: 0 }) };
    const courts = new Array(10).fill(null).map(() => ({ pts: [{ x: 0, y: 0 }] }));
    const lots = lotsDeTraits([...courts, long, ...courts], 20);
    expect(lots.length).toBeGreaterThan(1);
    // ⚠️ Un trait plus long que le lot part SEUL plutôt que coupé en deux : le
    // couper en ferait deux traits, avec un blanc au milieu.
    expect(lots.some((l) => l.length === 1 && l[0] === long)).toBe(true);
    // Rien ne se perd et rien ne se duplique.
    expect(lots.reduce((n, l) => n + l.length, 0)).toBe(21);
    expect(lotsDeTraits([], 20)).toEqual([]);
  });

  it('réserve le tableau à qui anime', () => {
    // ⚠️ Le canal est public : sans cela, un assistant masquerait le
    // présentateur en pleine communication, ou effacerait son schéma.
    ['board_open', 'board_close', 'board_pts', 'board_undo', 'board_clear', 'board_state'].forEach((t) => {
      expect(ordreRecevable(t, 'audience')).toBe(false);
      expect(ordreRecevable(t, 'presenter')).toBe(true);
      expect(ordreRecevable(t, 'moderator')).toBe(true);
    });
  });
});

describe('trait reçu d’un autre poste', () => {
  const crayons = ['#111827', '#1d4ed8', '#ffffff'];

  it('n’accepte que les crayons de la salle', () => {
    // ⚠️ `ordreRecevable` dit d'où vient le message, pas ce qu'il contient.
    expect(traitRecu({ id: 'a-1', c: '#1d4ed8', pts: [] }, crayons).c).toBe('#1d4ed8');
    // Une couleur inventée retombe sur le premier crayon plutôt que d'être tracée.
    expect(traitRecu({ id: 'a-1', c: 'url(http://x)', pts: [] }, crayons).c).toBe('#111827');
  });

  it('borne l’épaisseur et les coordonnées', () => {
    // ⚠️ Sans borne : un trait de mille pixels cache tout le reste, et un point
    // hors cadre se dessine par-dessus les vignettes.
    const t = traitRecu({
      id: 'a-1', c: '#111827', e: 9999,
      pts: [{ x: -4, y: 2 }, { x: 0.5, y: 0.5 }],
    }, crayons);
    expect(t.e).toBe(60);
    expect(t.pts[0]).toEqual({ x: 0, y: 1 });
    expect(t.pts[1]).toEqual({ x: 0.5, y: 0.5 });
  });

  it('écarte une coordonnée infinie', () => {
    // ⚠️ `Infinity` passe `!isNaN` et fige le rendu du canevas chez tout le monde.
    const t = traitRecu({
      id: 'a-1', c: '#111827', e: 3,
      pts: [{ x: Infinity, y: 0 }, { x: 'abc', y: 0 }, { x: 0.2, y: 0.2 }],
    }, crayons);
    expect(t.pts).toEqual([{ x: 0.2, y: 0.2 }]);
  });

  it('refuse ce qui n’est pas un trait', () => {
    expect(traitRecu(null, crayons)).toBeNull();
    expect(traitRecu({ c: '#111827' }, crayons)).toBeNull();
    expect(traitRecu({ id: 'x'.repeat(500), c: '#111827' }, crayons)).toBeNull();
    // Sans crayon permis, rien ne se trace.
    expect(traitRecu({ id: 'a-1', c: '#111827' }, [])).toBeNull();
  });

  it('tronque un trait démesuré au lieu de le garder entier', () => {
    const pts = new Array(TRAIT_POINTS_MAX + 500).fill({ x: 0.1, y: 0.1 });
    expect(traitRecu({ id: 'a-1', c: '#111827', pts }, crayons).pts).toHaveLength(TRAIT_POINTS_MAX);
  });
});

describe('écran d’entrée', () => {
  it('ne se repose pas à chaque actualisation', () => {
    // ⚠️ Reposé à chaque venue, il devient un péage : on le franchit sans rien
    // lire, et on le traverse au pire moment - quand on vient de perdre la salle
    // en pleine séance.
    expect(entreeDirecte(true, true, true, 'granted', 'granted')).toBe(true);
  });

  it('se montre la première fois', () => {
    expect(entreeDirecte(false, true, true, 'granted', 'granted')).toBe(false);
  });

  it('revient dès qu’une autorisation n’est plus acquise', () => {
    // ⚠️ `prompt` n'est pas `granted` : sans ce retour, la fenêtre d'autorisation
    // du navigateur s'ouvrirait par-dessus une séance déjà commencée - ce que cet
    // écran existe précisément pour éviter.
    expect(entreeDirecte(true, true, true, 'prompt', 'granted')).toBe(false);
    expect(entreeDirecte(true, true, true, 'granted', 'denied')).toBe(false);
    expect(entreeDirecte(true, false, true, 'denied', 'granted')).toBe(true);
  });

  it('laisse entrer qui ne demande ni caméra ni micro', () => {
    expect(entreeDirecte(true, false, false, 'denied', 'denied')).toBe(true);
  });
});
describe('annotation de la présentation', () => {
  it('pose le même repère chez deux spectateurs de formats différents', () => {
    // ⚠️ Le repère ne dépend QUE de la taille intrinsèque du média - la même
    // pour tous - et de la boîte locale. Un trait au tiers de l'image doit
    // tomber au tiers de l'image partout.
    const a = cadreMedia(1600, 600, 1920, 1080);
    const b = cadreMedia(800, 900, 1920, 1080);
    expect(a.w / a.h).toBeCloseTo(1920 / 1080, 5);
    expect(b.w / b.h).toBeCloseTo(1920 / 1080, 5);
    // Le même point normalisé retombe au même endroit de l'image chez les deux.
    const pa = pointTableau(a.x + a.w / 3, a.y + a.h / 4, a);
    const pb = pointTableau(b.x + b.w / 3, b.y + b.h / 4, b);
    expect(pa).toEqual(pb);
  });

  it('suit le format du document, pas celui du tableau', () => {
    // Un diaporama 4/3 n'est pas un tableau 16/9 : l'annotation se cale sur ce
    // qui est montré, sinon elle flotte à côté.
    const c = cadreMedia(1600, 900, 1024, 768);
    expect(c.w / c.h).toBeCloseTo(4 / 3, 5);
    expect(c.h).toBe(900);
    expect(c.x).toBeCloseTo((1600 - c.w) / 2, 5);
  });

  it('tient dans la boîte et reste centré', () => {
    const large = cadreMedia(1600, 600, 1920, 1080);
    expect(large.w).toBeLessThanOrEqual(1600);
    expect(large.h).toBeLessThanOrEqual(600);
    expect(large.y).toBeCloseTo((600 - large.h) / 2, 5);
  });

  it('rend une boîte vide quand l’image n’est pas encore arrivée', () => {
    // ⚠️ Mieux vaut que l'appelant retombe sur le cadre 16/9 du tableau qu'un
    // rectangle inventé : une annotation posée sur un faux repère est fausse
    // chez tout le monde, et rien ne le signale.
    expect(cadreMedia(1600, 900, 0, 0)).toEqual({ x: 0, y: 0, w: 0, h: 0 });
    expect(cadreMedia(0, 0, 1920, 1080)).toEqual({ x: 0, y: 0, w: 0, h: 0 });
  });
});

describe('échanges', () => {
  const file = [
    { id: 1, status: 'pending', mine: false },
    { id: 2, status: 'answered', mine: true },
    { id: 3, status: 'pending', mine: true },
    { id: 4, status: 'hidden', mine: false },
  ];

  it('restreint sans réordonner', () => {
    // ⚠️ Le tri par votes vient du serveur : le filtre ne fait que retirer.
    expect(questionsFiltrees(file, 'toutes').map((q) => q.id)).toEqual([1, 2, 3, 4]);
    expect(questionsFiltrees(file, 'attente').map((q) => q.id)).toEqual([1, 3]);
    expect(questionsFiltrees(file, 'miennes').map((q) => q.id)).toEqual([2, 3]);
  });

  it('ne rend pas la liste d’origine', () => {
    // ⚠️ Rendre le tableau lui-même laisserait l'appelant le modifier par
    // mégarde - et c'est la file de la séance qu'il abîmerait.
    const sortie = questionsFiltrees(file, 'toutes');
    sortie.pop();
    expect(file).toHaveLength(4);
    expect(questionsFiltrees(null, 'toutes')).toEqual([]);
  });

  it('n’annonce que ce qui attend une réponse', () => {
    // ⚠️ Une file de quarante questions dont trente-huit sont traitées n'appelle
    // pas l'animateur : c'est le reste à faire qui doit sauter aux yeux.
    expect(compteEnAttente(file)).toBe(2);
    expect(compteEnAttente([{ status: 'answered' }, { status: 'hidden' }])).toBe(0);
    expect(compteEnAttente(null)).toBe(0);
  });
});

describe('annuler un trait', () => {
  const traits = [
    { id: 'alice-1' }, { id: 'bob-1' }, { id: 'alice-2' }, { id: 'bob-2' },
  ];

  it('reprend son dernier trait, pas celui du voisin', () => {
    // ⚠️ Deux personnes annotent la même présentation : « Annuler » ne doit pas
    // effacer par surprise la flèche de l'autre.
    expect(indexDuDernierTraitDe(traits, 'alice')).toBe(2);
    expect(indexDuDernierTraitDe(traits, 'bob')).toBe(3);
    expect(indexDuDernierTraitDe(traits, 'carole')).toBe(-1);
  });

  it('ne confond pas deux identités voisines', () => {
    // ⚠️ Sans le tiret, « alice » reprendrait aussi les traits d'« alice2 ».
    expect(indexDuDernierTraitDe([{ id: 'alice2-1' }], 'alice')).toBe(-1);
  });

  it('se tait devant ce qui n’est pas une liste de traits', () => {
    expect(indexDuDernierTraitDe(null, 'alice')).toBe(-1);
    expect(indexDuDernierTraitDe(traits, '')).toBe(-1);
    expect(indexDuDernierTraitDe([{}, { id: 42 }], 'alice')).toBe(-1);
  });
});

describe('qui tient le crayon', () => {
  it('ne le donne qu’à qui présente en ce moment', () => {
    // ⚠️ Le défaut corrigé : une séance compte plusieurs communicateurs, tous
    // `presenter` pendant toute la séance - ils pouvaient donc tous écrire sur
    // le diaporama de celui qui parle.
    expect(droitDeTracer('presenter', false, false)).toBe(false);
    expect(droitDeTracer('presenter', false, true)).toBe(true);
    expect(droitDeTracer('presenter', true, false)).toBe(true);
  });

  it('le laisse au modérateur, qui conduit la séance', () => {
    // ⚠️ Sans cela, le tableau blanc serait inutilisable tant que personne ne
    // présente - et c'est le moment où l'on veut dessiner quelque chose.
    expect(droitDeTracer('moderator', false, false)).toBe(true);
  });

  it('ne le donne jamais à l’assistance', () => {
    expect(droitDeTracer('audience', true, true)).toBe(false);
    expect(droitDeTracer(undefined, true, true)).toBe(false);
  });
});

describe('qui la salle montre comme présentateur', () => {
  it('désigne la même personne pour la scène et pour le crayon', () => {
    // ⚠️ Le défaut vécu : deux définitions séparées. Un communicateur affiché
    // « Présentateur » en grand trouvait le bouton Tableau grisé.
    const gens = [
      { id: 'local', role: 'presenter' },
      { id: 'b', role: 'audience' },
    ];
    const qui = celuiQuiPresente(gens);
    expect(qui.id).toBe('local');
    expect(droitDeTracer('presenter', false, qui.id === 'local')).toBe(true);
  });

  it('préfère la parole donnée au rôle porté', () => {
    // ⚠️ Au début d'une séance, le serveur donne `presenter` à TOUS les auteurs :
    // c'est la parole donnée qui tranche dès qu'elle l'a été.
    const gens = [
      { id: 'a', role: 'presenter' },
      { id: 'b', role: 'presenter', isFloor: true },
    ];
    expect(celuiQuiPresente(gens).id).toBe('b');
  });

  it('ne désigne personne quand personne ne présente', () => {
    expect(celuiQuiPresente([{ id: 'a', role: 'audience' }])).toBeNull();
    expect(celuiQuiPresente(null)).toBeNull();
    expect(celuiQuiPresente([null, undefined])).toBeNull();
  });
});
