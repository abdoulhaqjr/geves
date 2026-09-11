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

    const taskUrl = el.dataset.taskUrl || '';
    const msUrl = el.dataset.msUrl || '';
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

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
        editable: true,          // glisser-déposer pour re-planifier
        eventStartEditable: true,
        eventDurationEditable: false,
        events,
        // Re-planification : persiste la nouvelle échéance ; annule si le serveur refuse.
        eventDrop(info) {
            const kind = info.event.extendedProps?.kind;
            const id = info.event.extendedProps?.id;
            const base = kind === 'milestone' ? msUrl : taskUrl;
            if (!id || !base) { info.revert(); return; }

            fetch(base.replace(':id', String(id)), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ due_date: info.event.startStr }),
            })
                .then((r) => { if (!r.ok) info.revert(); })
                .catch(() => info.revert());
        },
    });
    cal.render();
    window.__projectCal = cal;
}
