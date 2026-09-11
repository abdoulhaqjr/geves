import { Calendar } from '@fullcalendar/core';
import frLocale from '@fullcalendar/core/locales/fr';
import dayGridPlugin from '@fullcalendar/daygrid';
import listPlugin from '@fullcalendar/list';

// Calendrier des échéances de la gestion de projet. La source de vérité est
// l'état client (Alpine) : les événements sont fournis par `events` et le
// glisser-déposer remonte via `onEventDrop`. Le composant appelle ensuite
// removeAllEvents()/addEvent() pour repeindre après chaque changement.
export function createProjectCalendar(el, { events = [], onEventDrop } = {}) {
    const cal = new Calendar(el, {
        plugins: [dayGridPlugin, listPlugin],
        locale: frLocale,
        timeZone: 'local',
        initialView: 'dayGridMonth',
        initialDate: events[0]?.start || undefined,
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listMonth' },
        height: 'auto',
        displayEventTime: false,
        editable: true,
        eventStartEditable: true,
        eventDurationEditable: false,
        events,
        eventDrop: onEventDrop,
    });
    cal.render();
    return cal;
}
