import { describe, expect, it } from 'vitest';

import {
  grilleOptimale,
  idSur,
  initials,
  mainsOrdonnees,
  metadonnee,
  ordreRecevable,
  presenceLabel,
  rangsDesMains,
  roleLabel,
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
