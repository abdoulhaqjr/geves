import { Calendar } from '@fullcalendar/core';
import frLocale from '@fullcalendar/core/locales/fr';
import dayGridPlugin from '@fullcalendar/daygrid';
import listPlugin from '@fullcalendar/list';

// Calendrier des échéances de la gestion de projet (jalons + tâches datées).
// FullCalendar v6, vue mois par défaut. Rendu à la demande : le panneau est
// masqué au chargement (bascule Alpine) → on expose updateSize via window.
export function startProjectCalendar() {
    const el = document.getElementById('projectCalendar');
    if (!el || el.dataset.ready) return;
    el.dataset.ready = '1';

    let events = [];
    try {
        events = JSON.parse(document.getElementById('projectEvents')?.textContent || '[]');
    } catch (e) {
        events = [];
    }

    const cal = new Calendar(el, {
        plugins: [dayGridPlugin, listPlugin],
        locale: frLocale,
        timeZone: 'local',
        initialView: 'dayGridMonth',
        initialDate: events[0]?.start || undefined,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listMonth',
        },
        height: 'auto',
        displayEventTime: false,
        events,
    });
    cal.render();
    window.__projectCal = cal;
}
