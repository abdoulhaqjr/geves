import { describe, expect, it } from 'vitest';

import {
  MAX_OCTETS,
  estLeScribe,
  peutReessayer,
  phraseEtat,
  phrasePresence,
  teinteDuNom,
  tropGrosPourLeCanal,
} from './task-document-pure.js';

// Rédaction à plusieurs d'un document de tâche. Ces cas figent les décisions qui
// coûtent cher quand elles sont fausses : qui enregistre, ce qui tient dans un
// message du canal, et quand renoncer à réessayer.

describe('élection du scribe', () => {
  it('désigne le même poste chez tout le monde', () => {
    // ⚠️ C'est TOUT l'intérêt : les postes ne se concertent pas, ils appliquent
    // chacun la règle et doivent tomber d'accord. Sans cela, cinq rédacteurs
    // enverraient cinq instantanés du même document toutes les deux secondes.
    const pairs = ['12', '3', '47'];

    expect(estLeScribe('12', pairs)).toBe(true);
    expect(estLeScribe('3', pairs)).toBe(false);
    expect(estLeScribe('47', pairs)).toBe(false);

    // Un seul élu, vu depuis n'importe quel poste.
    expect(pairs.filter((p) => estLeScribe(p, pairs))).toHaveLength(1);
  });

  it('ne dépend ni de l’ordre d’arrivée ni d’une horloge', () => {
    // Deux postes ne voient pas forcément les arrivées dans le même ordre, et
    // leurs horloges diffèrent : la règle ne doit s'appuyer sur ni l'un ni l'autre.
    expect(estLeScribe('12', ['12', '3', '47'])).toBe(true);
    expect(estLeScribe('12', ['47', '12', '3'])).toBe(true);
    expect(estLeScribe('12', ['3', '47', '12'])).toBe(true);
  });

  it('⚠️ écarte qui ne fait pas partie des pairs', () => {
    // Un lecteur élu scribe n'enregistrerait jamais rien, et le document ne
    // serait plus conservé du tout : la liste ne porte que ceux qui peuvent écrire.
    expect(estLeScribe('9', ['12', '3'])).toBe(false);
    expect(estLeScribe('', ['12'])).toBe(false);
  });

  it('élit le seul présent quand on est seul', () => {
    // Sans temps réel, il n'y a qu'un poste : il DOIT enregistrer, sinon rien
    // n'est jamais conservé.
    expect(estLeScribe('5', ['5'])).toBe(true);
  });

  it('reprend la main quand le scribe part', () => {
    const avant = ['12', '3', '47'];
    const apres = avant.filter((p) => p !== '12');

    expect(estLeScribe('3', avant)).toBe(false);
    expect(estLeScribe('3', apres)).toBe(true);
  });
});

describe('taille d’un message du canal', () => {
  it('laisse passer une frappe ordinaire', () => {
    expect(tropGrosPourLeCanal(40)).toBe(false);
    expect(tropGrosPourLeCanal(MAX_OCTETS)).toBe(false);
  });

  it('⚠️ refuse ce que le service refuserait EN SILENCE', () => {
    // Un message au-dessus de la limite n'est pas tronqué, il est rejeté : la
    // modification disparaîtrait chez les autres sans un mot. L'appelant bascule
    // alors sur le serveur.
    expect(tropGrosPourLeCanal(MAX_OCTETS + 1)).toBe(true);
  });

  it('reste en dessous de la limite du protocole, base64 compris', () => {
    // ⚠️ Le service plafonne un événement client à 10 Ko, et le base64 gonfle
    // d'un tiers. Le plafond d'utile doit donc laisser de la marge, enveloppe
    // JSON incluse.
    expect(Math.ceil((MAX_OCTETS * 4) / 3)).toBeLessThan(10000);
  });

  it('traite une taille absurde comme trop grosse', () => {
    expect(tropGrosPourLeCanal(NaN)).toBe(true);
    expect(tropGrosPourLeCanal(-1)).toBe(true);
  });
});

describe('réessai après refus de concurrence', () => {
  it('réessaie, mais pas sans fin', () => {
    // ⚠️ Un refus n'est pas une perte - l'état se fusionne et on recommence.
    // Mais si la révision avance à chaque tentative, c'est que quelqu'un écrit
    // sans arrêt : une boucle sans fin bloquerait le navigateur. Rien n'est
    // perdu, c'est seulement remis au prochain enregistrement périodique.
    expect(peutReessayer(0)).toBe(true);
    expect(peutReessayer(2)).toBe(true);
    expect(peutReessayer(3)).toBe(false);
  });
});

describe('teinte d’une personne', () => {
  it('rend toujours la même couleur pour le même nom', () => {
    expect(teinteDuNom('Awa Kodjo')).toBe(teinteDuNom('Awa Kodjo'));
  });

  it('reste dans la roue des teintes', () => {
    // Seule la TEINTE varie : saturation et clarté sont fixées par la feuille de
    // style, pour que le texte posé dessus garde son contraste.
    ['', 'A', 'Awa Kodjo', 'Jean-Baptiste de la Tour'].forEach((nom) => {
      const t = teinteDuNom(nom);
      expect(t).toBeGreaterThanOrEqual(0);
      expect(t).toBeLessThan(360);
    });
  });

  it('sépare deux noms voisins', () => {
    expect(teinteDuNom('Awa Kodjo')).not.toBe(teinteDuNom('Awa Kodja'));
  });
});

describe('ce que l’écran annonce', () => {
  it('⚠️ distingue « en cours » de « enregistré »', () => {
    // Un document rédigé à plusieurs n'a pas de bouton « Enregistrer » : cette
    // phrase est la seule chose qui dise si l'on peut fermer l'onglet. Les deux
    // états ne veulent pas dire la même chose au moment de partir.
    expect(phraseEtat({ etat: 'cours' })).not.toBe(phraseEtat({ etat: 'enregistre' }));
    expect(phraseEtat({ etat: 'enregistre', quand: '14:05' })).toContain('14:05');
    expect(phraseEtat({ etat: 'modifie' })).toContain('non enregistrées');
    expect(phraseEtat({ etat: 'erreur' })).toContain('impossible');
  });

  it('dit « lecture seule » à qui ne peut pas écrire, quel que soit l’état', () => {
    expect(phraseEtat({ etat: 'modifie', lecteur: true })).toBe('Lecture seule');
    expect(phraseEtat({ etat: 'enregistre', lecteur: true })).toBe('Lecture seule');
  });

  it('accorde la présence', () => {
    expect(phrasePresence(0)).toContain('seul');
    expect(phrasePresence(1)).toBe('1 autre personne sur ce document');
    expect(phrasePresence(3)).toBe('3 autres personnes sur ce document');
  });
});
