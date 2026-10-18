// Règles du formulaire « créneau » : ce qu'une SÉANCE impose au créneau qu'on y
// range (son jour, sa salle, sa fenêtre horaire) et quelles communications on
// peut encore y placer (celles qui ne sont pas déjà posées ailleurs, dans les
// axes de la séance).
//
// ⚠️ Ce sont des règles d'AIDE À LA SAISIE : elles remplissent et restreignent
// le formulaire pour qu'on n'ait pas à retrouver à la main ce que la séance dit
// déjà. La DÉCISION, elle, se prend au serveur (`ProgramController::slotFrame`) :
// un champ en lecture seule se retire en deux clics.
//
// Elles vivent ici, hors du DOM et hors d'Alpine, pour être couvertes par
// `program-pure.test.js`.

/** La séance choisie, ou null. La clé est une CHAÎNE : elle vient d'un `<select>`. */
export function seanceDe(meta, id) {
  const cle = String(id ?? '');

  return (cle && meta && meta.sessions && meta.sessions[cle]) || null;
}

/** L'heure (HH:MM) tombe-t-elle dans la fenêtre de la séance ? */
export function heureDansLaSeance(seance, heure) {
  if (!seance || !seance.start) {
    return true; // séance sans horaires : rien à contraindre
  }
  const h = String(heure ?? '');
  if (!/^\d{1,2}:\d{2}$/.test(h)) {
    return false;
  }

  // ⚠️ Commencer À l'heure de fin, c'est être hors de la séance ; commencer à
  // son heure de début, non. La durée du créneau, elle, n'a PAS à y tenir : un
  // poster dure plus longtemps que la séance qui l'accueille.
  return h >= seance.start && (!seance.end || h < seance.end);
}

/**
 * Bornes à poser sur le champ « heure de début » : la fenêtre de la séance, sa
 * fin exclue (d'où la minute retirée, `max` étant inclusif côté navigateur).
 * `null` quand rien n'est à borner - Alpine retire alors l'attribut.
 */
export function fenetreHoraire(seance) {
  if (!seance || !seance.start) {
    return { min: null, max: null };
  }

  return { min: seance.start, max: seance.end ? minuteAvant(seance.end) : null };
}

/** HH:MM moins une minute (00:00 reste 00:00 : il n'y a rien avant). */
export function minuteAvant(heure) {
  const m = /^(\d{1,2}):(\d{2})$/.exec(String(heure ?? ''));
  if (!m) {
    return null;
  }
  const total = Number(m[1]) * 60 + Number(m[2]) - 1;
  if (total < 0) {
    return '00:00';
  }

  return (
    String(Math.floor(total / 60)).padStart(2, '0') + ':' + String(total % 60).padStart(2, '0')
  );
}

/**
 * Ce que la séance impose au formulaire quand on la choisit : son jour, sa
 * salle, et une heure prise dans sa fenêtre.
 *
 * ⚠️ L'heure déjà saisie n'est REMPLACÉE que si elle sort de la séance : sinon
 * c'est celle que le gestionnaire a voulue, et la lui reprendre à chaque
 * changement de séance serait une surprise.
 */
export function cadreDeLaSeance(meta, form) {
  const s = seanceDe(meta, form && form.session_id);
  if (!s) {
    return {};
  }

  const cadre = {};
  if (s.day) {
    cadre.day = s.day;
  }
  if (s.room) {
    cadre.room = s.room;
  }
  if (s.start && !heureDansLaSeance(s, form && form.start)) {
    cadre.start = s.start;
  }

  return cadre;
}

/**
 * Les communications qu'on peut encore placer sur ce créneau : celles qui ne
 * sont posées sur aucun autre créneau, et qui relèvent des axes de la séance.
 *
 * ⚠️ **Le choix courant reste toujours offert**, même s'il est déjà placé (c'est
 * le cas quand on modifie le créneau qui le porte) ou hors des axes de la séance
 * (c'est le cas quand on vient du vivier « Assigner », communication d'abord,
 * séance ensuite) : il est alors marqué, jamais supprimé en silence. Hors axe
 * n'est d'ailleurs pas interdit - l'affectation automatique s'y replie elle aussi.
 *
 * @returns {{options: Array<{id: string, label: string, horsAxe: boolean}>, masquees: number, axes: Array<string>}}
 */
export function communicationsChoisissables(meta, sessionId, choisie) {
  const s = seanceDe(meta, sessionId);
  const axes = (s && Array.isArray(s.tracks) && s.tracks) || [];
  const courante = String(choisie ?? '');
  const toutes = (meta && Array.isArray(meta.submissions) && meta.submissions) || [];

  const options = [];
  let masquees = 0;
  toutes.forEach((c) => {
    const estCourante = c.id === courante && courante !== '';
    const horsAxe = axes.length > 0 && !axes.includes(c.track);
    if (!estCourante && (c.slot || horsAxe)) {
      masquees++;

      return;
    }
    options.push({ id: c.id, label: c.label, horsAxe: horsAxe });
  });

  return { options: options, masquees: masquees, axes: axes };
}
