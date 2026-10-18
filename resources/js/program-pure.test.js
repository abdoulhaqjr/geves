import { describe, expect, it } from 'vitest';

import {
  cadreDeLaSeance,
  communicationsChoisissables,
  fenetreHoraire,
  heureDansLaSeance,
  minuteAvant,
  seanceDe,
} from './program-pure.js';

// Un créneau se range DANS une séance : il en prend le jour, la salle et l'heure,
// et n'accueille que des communications des axes de cette séance. Ces cas figent
// ce que le formulaire doit déduire tout seul - ce qu'on saisissait à la main,
// et qu'on pouvait donc contredire.

const meta = {
  sessions: {
    7: {
      label: 'Atelier 1 - séance 1',
      day: '2026-10-12',
      start: '14:00',
      end: '17:00',
      room: 'Salle 1',
      tracks: ['Santé', 'Sport'],
    },
    8: {
      label: 'Séance inaugurale',
      day: '2026-10-12',
      start: '09:00',
      end: '10:30',
      room: '',
      tracks: [],
    },
    9: { label: 'Session à caler', day: '', start: '', end: '', room: '', tracks: ['Santé'] },
  },
  submissions: [
    { id: '1', label: 'AA-001 - Paludisme', track: 'Santé', slot: '' },
    { id: '2', label: 'AA-002 - Athlétisme', track: 'Sport', slot: '12' },
    { id: '3', label: 'AA-003 - Droit minier', track: 'Droit', slot: '' },
    { id: '4', label: 'AA-004 - Sans axe', track: '', slot: '' },
  ],
};

describe('la séance choisie', () => {
  it('se retrouve par une clé en chaîne, comme la rend un select', () => {
    expect(seanceDe(meta, '7').label).toBe('Atelier 1 - séance 1');
    expect(seanceDe(meta, 7).label).toBe('Atelier 1 - séance 1');
    expect(seanceDe(meta, '')).toBeNull();
    expect(seanceDe(meta, '404')).toBeNull();
    expect(seanceDe({}, '7')).toBeNull();
  });
});

describe('l’heure tombe dans la séance', () => {
  const s = meta.sessions[7];

  it('accepte le début de la séance et refuse sa fin', () => {
    // ⚠️ Commencer À l'heure de fin, c'est être hors de la séance.
    expect(heureDansLaSeance(s, '14:00')).toBe(true);
    expect(heureDansLaSeance(s, '16:59')).toBe(true);
    expect(heureDansLaSeance(s, '17:00')).toBe(false);
    expect(heureDansLaSeance(s, '13:59')).toBe(false);
  });

  it('ne contraint rien quand la séance n’a pas d’horaires', () => {
    expect(heureDansLaSeance(meta.sessions[9], '23:00')).toBe(true);
    expect(heureDansLaSeance(null, '23:00')).toBe(true);
  });

  it('refuse une heure absente ou illisible', () => {
    expect(heureDansLaSeance(s, '')).toBe(false);
    expect(heureDansLaSeance(s, null)).toBe(false);
    expect(heureDansLaSeance(s, '14h30')).toBe(false);
  });
});

describe('les bornes du champ heure', () => {
  it('va du début de la séance à la minute qui précède sa fin', () => {
    // `max` est INCLUSIF côté navigateur : sans la minute retirée, il laisserait
    // commencer un créneau à l'heure même où la séance se termine.
    expect(fenetreHoraire(meta.sessions[7])).toEqual({ min: '14:00', max: '16:59' });
  });

  it('ne borne rien sans horaires', () => {
    expect(fenetreHoraire(meta.sessions[9])).toEqual({ min: null, max: null });
    expect(fenetreHoraire(null)).toEqual({ min: null, max: null });
  });

  it('recule d’une minute sans passer sous minuit', () => {
    expect(minuteAvant('10:30')).toBe('10:29');
    expect(minuteAvant('10:00')).toBe('09:59');
    expect(minuteAvant('00:00')).toBe('00:00');
    expect(minuteAvant('')).toBeNull();
  });
});

describe('ce que la séance impose au formulaire', () => {
  it('donne son jour et sa salle', () => {
    expect(cadreDeLaSeance(meta, { session_id: '7', day: '', room: '', start: '' })).toEqual({
      day: '2026-10-12',
      room: 'Salle 1',
      start: '14:00',
    });
  });

  it('garde une heure déjà saisie si elle tombe dans la séance', () => {
    // Sinon, changer de séance reprendrait au gestionnaire l'heure qu'il a voulue.
    expect(
      cadreDeLaSeance(meta, { session_id: '7', day: '', room: '', start: '15:30' }).start
    ).toBeUndefined();
  });

  it('ramène dans la fenêtre une heure qui en sort', () => {
    expect(
      cadreDeLaSeance(meta, { session_id: '7', day: '', room: '', start: '09:00' }).start
    ).toBe('14:00');
  });

  it('n’impose que ce que la séance porte', () => {
    // Séance sans salle ni horaires : rien à déduire, la saisie reste souveraine.
    expect(
      cadreDeLaSeance(meta, { session_id: '9', day: '', room: 'Salle B', start: '08:00' })
    ).toEqual({});
    expect(cadreDeLaSeance(meta, { session_id: '', day: '', room: '', start: '' })).toEqual({});
  });
});

describe('les communications qu’on peut encore placer', () => {
  it('écarte celles qui sont déjà sur un créneau et celles d’un autre axe', () => {
    const r = communicationsChoisissables(meta, '7', '');
    expect(r.options.map((o) => o.id)).toEqual(['1']);
    // AA-002 est déjà placée, AA-003 est hors des axes, AA-004 n'a pas d'axe.
    expect(r.masquees).toBe(3);
    expect(r.axes).toEqual(['Santé', 'Sport']);
  });

  it('offre toutes les communications libres quand la séance n’a pas d’axes', () => {
    // Une plénière ne porte pas d'atelier : filtrer sur un axe vide ne laisserait
    // rien à choisir, et le formulaire paraîtrait cassé.
    const r = communicationsChoisissables(meta, '8', '');
    expect(r.options.map((o) => o.id)).toEqual(['1', '3', '4']);
    expect(r.masquees).toBe(1);
  });

  it('garde le choix courant même déjà placé : c’est le créneau qu’on modifie', () => {
    const r = communicationsChoisissables(meta, '7', '2');
    expect(r.options.map((o) => o.id)).toEqual(['1', '2']);
  });

  it('garde le choix courant hors axe, et le marque', () => {
    // On vient du vivier « Assigner » : la communication est choisie d'abord, la
    // séance ensuite. La faire disparaître effacerait l'intention en silence.
    const r = communicationsChoisissables(meta, '7', '3');
    expect(r.options.map((o) => [o.id, o.horsAxe])).toEqual([
      ['1', false],
      ['3', true],
    ]);
  });

  it('ne filtre rien sans séance', () => {
    const r = communicationsChoisissables(meta, '', '');
    expect(r.options.map((o) => o.id)).toEqual(['1', '3', '4']);
  });
});
