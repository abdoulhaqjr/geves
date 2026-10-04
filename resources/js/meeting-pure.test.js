import { describe, expect, it } from 'vitest';

import {
  cadreTableau,
  chronoLabel,
  enBasDuFil,
  etatChrono,
  grilleOptimale,
  idSur,
  idsDesParleurs,
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
  presenceLabel,
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
    expect(presenceLabel('excused')).toBe('Excusé');
    expect(presenceLabel('')).toBe('');
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
  it('monte la salle au-dessus de son ancien plafond', () => {
    // ⚠️ La salle capturait 960x540 à 24 i/s, et c'était la couche la plus haute :
    // rien ne pouvait y être plus net, quelle que soit la caméra.
    expect(profilVideo('hd')).toEqual({ largeur: 1280, hauteur: 720, images: 30, debit: 1700000 });
    expect(profilVideo('hd').hauteur).toBeGreaterThan(540);
  });

  it('laisse le choix d’économiser la liaison montante', () => {
    expect(profilVideo('sd').hauteur).toBe(360);
    expect(profilVideo('fhd').hauteur).toBe(1080);
    // Le débit suit la définition, sinon la couche haute sort en bouillie.
    expect(profilVideo('sd').debit).toBeLessThan(profilVideo('hd').debit);
    expect(profilVideo('hd').debit).toBeLessThan(profilVideo('fhd').debit);
  });

  it('retombe sur « hd » devant un nom inconnu', () => {
    // ⚠️ Un réglage saisi hier et retiré du code demain ne doit pas laisser la
    // salle sans image.
    expect(profilVideo('ultra')).toEqual(profilVideo('hd'));
    expect(profilVideo(null)).toEqual(profilVideo('hd'));
    expect(profilVideo(undefined)).toEqual(profilVideo('hd'));
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
