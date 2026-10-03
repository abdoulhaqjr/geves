import { describe, expect, it } from 'vitest';

import {
  chronoLabel,
  enBasDuFil,
  etatChrono,
  grilleOptimale,
  idSur,
  idsDesParleurs,
  initials,
  mainsOrdonnees,
  metadonnee,
  niveauReseau,
  ordreRecevable,
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
