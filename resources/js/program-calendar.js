import { Calendar } from '@fullcalendar/core';
import frLocale from '@fullcalendar/core/locales/fr';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';

// Agenda du programme (FullCalendar v6, CSS auto-injectée) - chargé à la demande.
// Les événements (sessions horodatées) sont fournis par le serveur en JSON.
export function startProgramCalendar() {
    const el = document.getElementById('programCalendar');
    if (!el || el.dataset.ready) return;
    el.dataset.ready = '1';

    let events = [];
    try {
        events = JSON.parse(document.getElementById('programEvents')?.textContent || '[]');
    } catch (e) {
        events = [];
    }

    const cal = new Calendar(el, {
        plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
        locale: frLocale,
        timeZone: 'local',
        initialView: events.length ? 'timeGridWeek' : 'dayGridMonth',
        initialDate: events[0]?.start || undefined,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
        },
        height: 'auto',
        nowIndicator: true,
        slotMinTime: '07:00:00',
        slotMaxTime: '20:00:00',
        allDaySlot: false,
        events,
    });
    cal.render();
    window.__programCal = cal;
}
