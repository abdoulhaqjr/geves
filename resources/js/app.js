import Alpine from 'alpinejs';
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.css';

// Alpine est désormais une dépendance autonome (Livewire retiré du projet).
window.Alpine = Alpine;

// Tom Select (Tailwise) : transforme tout <select data-tom> en liste
// recherchable ; data-tom-create autorise la saisie libre (tags). Le <select>
// natif reste la source → onchange/submit et la validation serveur inchangés.
function initTomSelects(root = document) {
    root.querySelectorAll('[data-tom]').forEach((el) => {
        if (el.tomselect) return;
        const isInput = el.tagName === 'INPUT';
        const tags = isInput || el.hasAttribute('data-tom-create');
        const max = parseInt(el.getAttribute('data-tom-max') || '', 10);
        // Mode tags sur un <input> : on relit la valeur (mots séparés par des
        // virgules) AVANT init pour ré-injecter les tags existants.
        const seed = isInput ? String(el.value || '').split(',').map((s) => s.trim()).filter(Boolean) : [];
        // On vide la valeur avant init : Tom Select ne ré-analyse pas (sinon des
        // tags avec espaces en double) ; on ré-injecte des tags propres ensuite.
        if (isInput) el.value = '';

        // Filtres à soumission auto : Tom Select redéclenche un « change » à
        // l'initialisation, ce qui relancerait `form.submit()` en boucle (page
        // qui scintille). On retire le onchange natif et on ne soumet que sur un
        // vrai changement utilisateur, une fois l'initialisation terminée.
        const autoSubmit = /\.form\.submit\(\)/.test(el.getAttribute('onchange') || '');
        if (autoSubmit) el.removeAttribute('onchange');
        let ready = false;

        const ts = new TomSelect(el, {
            create: tags,
            delimiter: isInput ? ',' : undefined,
            createOnBlur: isInput,
            persist: false,
            allowEmptyOption: !isInput,
            maxItems: Number.isFinite(max) && max > 0 ? max : null,
            plugins: (el.multiple || isInput) ? ['remove_button'] : [],
            placeholder: el.getAttribute('data-placeholder') || undefined,
            onChange() {
                if (autoSubmit && ready && el.form) {
                    el.form.submit();
                }
            },
        });
        seed.forEach((v) => { ts.addOption({ value: v, text: v }); ts.addItem(v, true); });
        ts.refreshItems();
        ready = true;
    });
}
document.addEventListener('DOMContentLoaded', () => initTomSelects());
window.initTomSelects = initTomSelects;

// Barres de filtres (GET) : on retire les champs vides de l'URL. En surchargeant
// form.submit(), on couvre tous les déclencheurs — selects natifs, Tom Select et
// touche Entrée — sans modifier chaque vue. Le rechargement repart d'une page
// neuve : l'état « disabled » posé juste avant l'envoi n'a aucun effet visible.
function initFilterForms(root = document) {
    root.querySelectorAll('form.filter-bar').forEach((form) => {
        if (form.dataset.filterReady) return;
        form.dataset.filterReady = '1';

        const disableEmpties = () => {
            form.querySelectorAll('input[name], select[name]').forEach((el) => {
                if (! el.disabled && (el.value === '' || el.value == null)) {
                    el.disabled = true;
                }
            });
        };
        const nativeSubmit = HTMLFormElement.prototype.submit.bind(form);
        form.submit = () => { disableEmpties(); nativeSubmit(); };
        form.addEventListener('submit', disableEmpties);
    });
}
document.addEventListener('DOMContentLoaded', () => initFilterForms());
window.initFilterForms = initFilterForms;

// Interactions du site public (menu mobile, galerie/lightbox, filtres).
import './public.js';

// Visio : charge le lecteur LiveKit (SDK livekit-client) uniquement sur la page
// « Rejoindre » — l'import dynamique code-splitte le SDK dans son propre chunk.
if (document.getElementById('lk-config')) {
    import('./meeting.js').then((m) => m.startFromConfig()).catch(() => {});
}

// Programme : charge l'agenda FullCalendar uniquement sur la page Programme.
if (document.getElementById('programCalendar')) {
    import('./program-calendar.js').then((m) => m.startProgramCalendar()).catch(() => {});
}


// Pointage : charge le scanner QR (html5-qrcode) uniquement sur la page de scan.
if (document.getElementById('badge-scanner')) {
    import('./scanner.js').then((m) => m.startBadgeScanner()).catch(() => {});
}

// Tableau de bord admin & rapports : charge Chart.js quand des graphiques sont
// présents (chaque page est un rendu serveur complet → init au chargement).
if (document.getElementById('dashboardData')) {
    import('./charts.js').then((m) => m.startDashboardCharts()).catch(() => {});
}

// ---------------------------------------------------------------------------
// AJAX générique pour les actions inline du back-office (bascules, marquages,
// suppressions…). Un formulaire marqué `data-ajax` est soumis en fetch, puis on
// rafraîchit uniquement le conteneur `data-ajax-root` visé (par `data-ajax-target`
// ou le plus proche) au lieu de recharger toute la page. Toast de confirmation.
// ---------------------------------------------------------------------------
function ukcToast(msg, type = 'ok') {
    let host = document.getElementById('ukc-toasts');
    if (!host) {
        host = document.createElement('div');
        host.id = 'ukc-toasts';
        host.style.cssText = 'position:fixed;top:16px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
        document.body.appendChild(host);
    }
    const el = document.createElement('div');
    el.className = 'ukc-flash ' + (type === 'err' ? 'ukc-flash--err' : 'ukc-flash--ok');
    el.style.position = 'static';
    el.textContent = msg;
    host.appendChild(el);
    setTimeout(() => { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 3000);
}
window.ukcToast = ukcToast;

async function ukcAjaxSubmit(form) {
    const key = form.dataset.ajaxTarget || form.closest('[data-ajax-root]')?.getAttribute('data-ajax-root');
    const btns = form.querySelectorAll('button, [type=submit]');
    btns.forEach((b) => { b.disabled = true; });
    try {
        const res = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'fetch', Accept: 'text/html' },
        });
        if (!res.ok) throw new Error('http');
        ukcToast(form.dataset.ajaxMsg || 'Action effectuée.');
        if (key) {
            const html = await (await fetch(window.location.href, { headers: { 'X-Requested-With': 'fetch' } })).text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const fresh = doc.querySelector('[data-ajax-root="' + key + '"]');
            const cur = document.querySelector('[data-ajax-root="' + key + '"]');
            if (fresh && cur) { cur.innerHTML = fresh.innerHTML; window.Alpine?.initTree?.(cur); }
        }
    } catch (e) {
        ukcToast('Action impossible.', 'err');
    } finally {
        btns.forEach((b) => { b.disabled = false; });
    }
}

document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!form.matches || !form.matches('form[data-ajax]')) return;
    if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) { e.preventDefault(); return; }
    e.preventDefault();
    ukcAjaxSubmit(form);
});

// Formateur « markdown léger » SÛR pour les réponses des agents IA : on échappe
// tout le HTML d'abord, puis on n'applique que des transformations sur liste
// blanche (gras, liens http(s), listes à puces/numérotées, paragraphes). Aucun
// HTML de l'IA n'est jamais rendu tel quel → pas d'injection.
function ukcMarkdownLite(text) {
    const esc = (s) => String(s == null ? '' : s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    const inline = (s) => esc(s)
        .replace(/\b(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener" style="color:inherit;text-decoration:underline;">$1</a>')
        .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
        .replace(/(^|[^*])\*([^*\n]+)\*(?!\*)/g, '$1<em>$2</em>')
        .replace(/`([^`]+)`/g, '<code>$1</code>');

    const lines = String(text == null ? '' : text).split(/\n/);
    let html = '', inUl = false, inOl = false;
    const closeLists = () => {
        if (inUl) { html += '</ul>'; inUl = false; }
        if (inOl) { html += '</ol>'; inOl = false; }
    };
    for (const raw of lines) {
        const line = raw.trim();
        if (line === '') { closeLists(); continue; }
        const ul = line.match(/^[-*•]\s+(.*)$/);
        const ol = line.match(/^\d+[.)]\s+(.*)$/);
        if (ul) {
            if (!inUl) { closeLists(); html += '<ul class="chat-md-list">'; inUl = true; }
            html += '<li>' + inline(ul[1]) + '</li>';
        } else if (ol) {
            if (!inOl) { closeLists(); html += '<ol class="chat-md-list">'; inOl = true; }
            html += '<li>' + inline(ol[1]) + '</li>';
        } else {
            closeLists();
            const h = line.match(/^#{1,4}\s+(.*)$/);
            html += h ? '<p class="chat-md-h">' + inline(h[1]) + '</p>' : '<p>' + inline(line) + '</p>';
        }
    }
    closeLists();
    return html;
}
window.ukcMarkdownLite = ukcMarkdownLite;

// Enregistrement de nos composants Alpine (l'évènement est émis par Alpine.start()).
document.addEventListener('alpine:init', () => {
    // Notifications en popups fermables (toasts), affichées en bas à droite.
    // Auto-fermeture après quelques secondes ; bouton de fermeture manuel.
    window.Alpine.data('ukcToasts', (initial = []) => ({
        items: [],
        init() {
            this.items = (Array.isArray(initial) ? initial : []).map((t, i) => ({
                id: i, type: t.type || 'ok', msg: t.msg || '', show: false, timer: null,
            }));
            this.$nextTick(() => this.items.forEach((t, i) => setTimeout(() => {
                t.show = true;
                t.timer = setTimeout(() => this.dismiss(t.id), 6000);
            }, i * 140)));
        },
        dismiss(id) {
            const t = this.items.find((x) => x.id === id);
            if (t) { clearTimeout(t.timer); t.show = false; }
        },
    }));
    // Gestion de projet d'une édition (kanban + liste + jalons), 100 % AJAX : un
    // seul composant porte l'état (tasks[]/milestones[]) ; tout le CRUD passe par
    // fetch et met à jour l'état en direct — aucun rechargement de page.
    window.Alpine.data('ukcProject', (config = {}) => ({
        tasks: config.tasks || [],
        milestones: config.milestones || [],
        statuses: config.statuses || {},
        routes: config.routes || {},
        view: 'board',
        dragId: null,
        dragFrom: null,
        taskOpen: false,
        task: {},
        saving: false,
        msOpen: false,
        ms: {},
        msSaving: false,
        newCheck: '',
        newComment: '',
        commentBusy: false,
        flash: '',
        flashType: 'ok',
        cal: null,
        charts: null,

        _csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },
        _headers() {
            return { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this._csrf() };
        },
        notify(msg, type = 'ok') {
            this.flash = msg;
            this.flashType = type;
            setTimeout(() => { if (this.flash === msg) this.flash = ''; }, 3500);
        },

        // ---- Dérivés (kanban / liste / indicateurs) ----
        colTasks(status) {
            return this.tasks.filter((t) => t.status === status).sort((a, b) => (a.position || 0) - (b.position || 0));
        },
        count(status) {
            return this.tasks.filter((t) => t.status === status).length;
        },
        get st() {
            const total = this.tasks.length;
            const done = this.tasks.filter((t) => t.status === 'done').length;
            const overdue = this.tasks.filter((t) => t.overdue && t.status !== 'done').length;
            const msDone = this.milestones.filter((m) => m.done).length;
            return {
                total, done, open: total - done, overdue,
                progress: total ? Math.round((done / total) * 100) : 0,
                msTotal: this.milestones.length, msDone,
                next: this.milestones.find((m) => !m.done && m.due_date) || null,
            };
        },
        isEmpty() {
            return this.tasks.length === 0 && this.milestones.length === 0;
        },
        upsertTask(t) {
            const i = this.tasks.findIndex((x) => x.id === t.id);
            if (i >= 0) this.tasks.splice(i, 1, t); else this.tasks.push(t);
        },
        upsertMs(m) {
            const i = this.milestones.findIndex((x) => x.id === m.id);
            if (i >= 0) this.milestones.splice(i, 1, m); else this.milestones.push(m);
            this.sortMs();
        },
        sortMs() {
            this.milestones.sort((a, b) => {
                if (!a.due_date && !b.due_date) return 0;
                if (!a.due_date) return 1;
                if (!b.due_date) return -1;
                return a.due_date.localeCompare(b.due_date);
            });
        },

        // ---- Glisser-déposer kanban ----
        onDragStart(id, from) { this.dragId = id; this.dragFrom = from; },
        onDrop(to) {
            const id = this.dragId; const from = this.dragFrom;
            this.dragId = null; this.dragFrom = null;
            if (id === null || from === null || from === to) return;
            const task = this.tasks.find((t) => t.id === id);
            if (!task) return;
            task.status = to;
            const order = this.colTasks(to).map((t) => t.id);
            order.forEach((tid, i) => { const t = this.tasks.find((x) => x.id === tid); if (t) t.position = i; });
            this.refreshCal();
            this.refreshCharts();
            fetch((this.routes.taskMove || '').replace(':id', String(id)), {
                method: 'POST', headers: this._headers(), body: JSON.stringify({ status: to, order }),
            }).catch(() => {});
        },

        // ---- Panneau tâche ----
        blankTask(status = 'todo') {
            return { id: null, title: '', description: '', status, priority: 'normal', phase: '', link_key: '', assignee_id: '', due_date: '', checklist: [], comments: [] };
        },
        openNewTask(status = 'todo') {
            this.task = this.blankTask(status);
            this.newCheck = ''; this.newComment = '';
            this.taskOpen = true;
        },
        openEditTask(t) {
            this.task = {
                id: t.id, title: t.title || '', description: t.description || '',
                status: t.status || 'todo', priority: t.priority || 'normal',
                phase: t.phase || '', link_key: t.link_key || '', assignee_id: t.assignee_id || '', due_date: t.due_date || '',
                checklist: Array.isArray(t.checklist) ? JSON.parse(JSON.stringify(t.checklist)) : [],
                comments: [],
            };
            this.newCheck = ''; this.newComment = '';
            this.taskOpen = true;
            if (t.id) this.loadComments(t.id);
        },
        checklistProgress() {
            const items = this.task.checklist || [];
            return { done: items.filter((i) => i.done).length, total: items.length };
        },
        addCheck() {
            const text = (this.newCheck || '').trim();
            if (!text) return;
            (this.task.checklist = this.task.checklist || []).push({ text, done: false });
            this.newCheck = '';
        },
        removeCheck(i) { this.task.checklist.splice(i, 1); },
        saveTask() {
            if (this.saving || !(this.task.title || '').trim()) return;
            this.saving = true;
            const isEdit = !!this.task.id;
            const url = isEdit ? (this.routes.taskUpdate || '').replace(':id', String(this.task.id)) : (this.routes.taskStore || '');
            fetch(url, {
                method: isEdit ? 'PUT' : 'POST',
                headers: this._headers(),
                body: JSON.stringify({
                    title: this.task.title, description: this.task.description,
                    status: this.task.status, priority: this.task.priority,
                    phase: this.task.phase, link_key: this.task.link_key,
                    assignee_id: this.task.assignee_id || null, due_date: this.task.due_date || null,
                    checklist_json: JSON.stringify(this.task.checklist || []),
                }),
            })
                .then((r) => (r.ok ? r.json() : Promise.reject(r)))
                .then((d) => { if (d.task) { this.upsertTask(d.task); this.taskOpen = false; this.refreshCal(); this.refreshCharts(); this.notify(isEdit ? 'Tâche mise à jour.' : 'Tâche ajoutée.'); } })
                .catch(() => this.notify('Enregistrement impossible.', 'err'))
                .finally(() => { this.saving = false; });
        },
        deleteTask(t) {
            if (!confirm('Supprimer cette tâche ?')) return;
            fetch((this.routes.taskDelete || '').replace(':id', String(t.id)), { method: 'DELETE', headers: this._headers() })
                .then((r) => { if (r.ok) { this.tasks = this.tasks.filter((x) => x.id !== t.id); this.taskOpen = false; this.refreshCal(); this.refreshCharts(); this.notify('Tâche supprimée.'); } })
                .catch(() => this.notify('Suppression impossible.', 'err'));
        },

        // ---- Commentaires ----
        loadComments(id) {
            const url = (this.routes.taskComments || '').replace(':id', String(id));
            if (!url) return;
            fetch(url, { headers: { Accept: 'application/json' } })
                .then((r) => (r.ok ? r.json() : { comments: [] }))
                .then((d) => { this.task.comments = d.comments || []; })
                .catch(() => {});
        },
        submitComment() {
            const body = (this.newComment || '').trim();
            if (!body || !this.task.id || this.commentBusy) return;
            this.commentBusy = true;
            fetch((this.routes.taskComment || '').replace(':id', String(this.task.id)), {
                method: 'POST', headers: this._headers(), body: JSON.stringify({ body }),
            })
                .then((r) => (r.ok ? r.json() : null))
                .then((d) => { if (d?.comment) { this.task.comments.unshift(d.comment); this.newComment = ''; } })
                .catch(() => {})
                .finally(() => { this.commentBusy = false; });
        },

        // ---- Panneau jalon ----
        openNewMs() { this.ms = { id: null, title: '', description: '', due_date: '', status: 'pending' }; this.msOpen = true; },
        openEditMs(m) { this.ms = { id: m.id, title: m.title || '', description: m.description || '', due_date: m.due_date || '', status: m.status || 'pending' }; this.msOpen = true; },
        saveMs() {
            if (this.msSaving || !(this.ms.title || '').trim()) return;
            this.msSaving = true;
            const isEdit = !!this.ms.id;
            const url = isEdit ? (this.routes.msUpdate || '').replace(':id', String(this.ms.id)) : (this.routes.msStore || '');
            fetch(url, {
                method: isEdit ? 'PUT' : 'POST',
                headers: this._headers(),
                body: JSON.stringify({ title: this.ms.title, description: this.ms.description, due_date: this.ms.due_date || null }),
            })
                .then((r) => (r.ok ? r.json() : Promise.reject(r)))
                .then((d) => { if (d.milestone) { this.upsertMs(d.milestone); this.msOpen = false; this.refreshCal(); this.notify(isEdit ? 'Jalon mis à jour.' : 'Jalon ajouté.'); } })
                .catch(() => this.notify('Enregistrement impossible.', 'err'))
                .finally(() => { this.msSaving = false; });
        },
        toggleMs(m) {
            fetch((this.routes.msToggle || '').replace(':id', String(m.id)), { method: 'POST', headers: this._headers() })
                .then((r) => (r.ok ? r.json() : null))
                .then((d) => { if (d?.milestone) { this.upsertMs(d.milestone); this.refreshCal(); } })
                .catch(() => {});
        },
        deleteMs(m) {
            if (!confirm('Supprimer ce jalon ?')) return;
            fetch((this.routes.msDelete || '').replace(':id', String(m.id)), { method: 'DELETE', headers: this._headers() })
                .then((r) => { if (r.ok) { this.milestones = this.milestones.filter((x) => x.id !== m.id); this.refreshCal(); this.notify('Jalon supprimé.'); } })
                .catch(() => this.notify('Suppression impossible.', 'err'));
        },

        // ---- Amorçage ----
        seed() {
            if (this.saving) return;
            this.saving = true;
            fetch(this.routes.seed || '', { method: 'POST', headers: this._headers() })
                .then((r) => (r.ok ? r.json() : Promise.reject(r)))
                .then((d) => { this.tasks = d.tasks || []; this.milestones = d.milestones || []; this.refreshCal(); this.refreshCharts(); this.notify('Projet initialisé.'); })
                .catch(() => this.notify('Initialisation impossible.', 'err'))
                .finally(() => { this.saving = false; });
        },

        // ---- Calendrier des échéances (live, piloté par l'état) ----
        calEvents() {
            const evts = [];
            this.milestones.forEach((m) => {
                if (!m.due_date) return;
                evts.push({ title: '◆ ' + m.title, start: m.due_date, allDay: true,
                    color: m.done ? '#16a34a' : (m.overdue ? '#dc2626' : '#7c3aed'),
                    extendedProps: { kind: 'milestone', id: m.id } });
            });
            this.tasks.forEach((t) => {
                if (!t.due_date) return;
                evts.push({ title: t.title, start: t.due_date, allDay: true,
                    color: t.status === 'done' ? '#16a34a' : (t.overdue ? '#dc2626' : '#223ea4'),
                    extendedProps: { kind: 'task', id: t.id } });
            });
            return evts;
        },
        refreshCal() {
            if (!this.cal) return;
            this.cal.removeAllEvents();
            this.calEvents().forEach((e) => this.cal.addEvent(e));
        },
        openCalendar() {
            this.view = 'calendar';
            this.$nextTick(async () => {
                const el = document.getElementById('projectCalendar');
                if (!el) return;
                if (!this.cal) {
                    const m = await import('./project-calendar.js');
                    this.cal = m.createProjectCalendar(el, {
                        events: this.calEvents(),
                        onEventDrop: (info) => this.onEventDrop(info),
                    });
                } else {
                    this.refreshCal();
                    this.cal.updateSize();
                }
            });
        },
        onEventDrop(info) {
            const kind = info.event.extendedProps?.kind;
            const id = info.event.extendedProps?.id;
            const base = kind === 'milestone' ? this.routes.msReschedule : this.routes.taskReschedule;
            if (!id || !base) { info.revert(); return; }
            fetch(base.replace(':id', String(id)), {
                method: 'POST', headers: this._headers(), body: JSON.stringify({ due_date: info.event.startStr }),
            })
                .then((r) => (r.ok ? r.json() : Promise.reject(r)))
                .then((d) => { if (d.task) this.upsertTask(d.task); if (d.milestone) this.upsertMs(d.milestone); this.refreshCal(); })
                .catch(() => info.revert());
        },

        // ---- Diagrammes (live, pilotés par l'état) ----
        chartsData() {
            const keys = Object.keys(this.statuses);
            const status = { labels: keys.map((k) => this.statuses[k]), data: keys.map((k) => this.count(k)) };

            const phases = {};
            this.tasks.forEach((t) => {
                const p = t.phase || 'Sans phase';
                phases[p] = phases[p] || { total: 0, done: 0 };
                phases[p].total += 1;
                if (t.status === 'done') phases[p].done += 1;
            });
            const phase = {
                labels: Object.keys(phases),
                done: Object.values(phases).map((x) => x.done),
                remaining: Object.values(phases).map((x) => x.total - x.done),
            };

            const byAssignee = {};
            this.tasks.forEach((t) => { const n = t.assignee || 'Non assigné'; byAssignee[n] = (byAssignee[n] || 0) + 1; });
            const assignee = { labels: Object.keys(byAssignee), data: Object.values(byAssignee) };

            return { status, phase, assignee };
        },
        refreshCharts() {
            if (this.charts) this.charts.update(this.chartsData());
        },
        openCharts() {
            this.view = 'charts';
            this.$nextTick(async () => {
                if (!this.charts) {
                    const m = await import('./project-charts.js');
                    this.charts = await m.createProjectCharts(this.chartsData());
                } else {
                    this.refreshCharts();
                }
            });
        },
    }));
    // Assistante Gesco (widget flottant + page dédiée) : conversation côté client,
    // chaque tour appelle l'endpoint JSON /gesco/demander. Aucune action serveur
    // n'est déclenchée par l'IA — elle informe seulement.
    window.Alpine.data('gescoChat', (seed = '', endpoint = '/gesco/demander', name = 'Gesco') => ({
        messages: [],
        input: '',
        sending: false,
        endpoint,
        agentName: name,
        init() {
            this.messages = [{ role: 'bot', text: seed || 'Bonjour, je suis Gesco.' }];
            this.scrollSoon();
        },
        ask(text) {
            this.input = text;
            this.send();
        },
        render(text) {
            return window.ukcMarkdownLite(text);
        },
        async send() {
            const question = this.input.trim();
            if (!question || this.sending) return;

            const history = this.messages.slice();
            this.messages.push({ role: 'user', text: question });
            this.input = '';
            this.sending = true;
            this.scrollSoon();

            try {
                const res = await fetch(this.endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ question, history }),
                });
                const data = await res.json().catch(() => ({}));
                this.messages.push({
                    role: 'bot',
                    text: data.answer || 'Une erreur est survenue. Réessayez dans un instant.',
                });
            } catch (e) {
                this.messages.push({ role: 'bot', text: 'Connexion impossible. Vérifiez votre réseau et réessayez.' });
            } finally {
                this.sending = false;
                this.scrollSoon();
            }
        },
        scrollSoon() {
            this.$nextTick(() => {
                const log = this.$refs.log;
                if (log) log.scrollTop = log.scrollHeight;
            });
        },
    }));

    // Copilote d'administration (page Assistant IA — mockup admin-ai-assistant).
    // Chat réel (AskAdmin) + propositions à décider + journal des décisions persisté.
    window.Alpine.data('adminCopilot', (seed = '', askUrl = '', decideUrl = '', log = [], name = 'Copilote') => ({
        messages: [],
        input: '',
        sending: false,
        agentName: name,
        decisionLog: Array.isArray(log) ? log : [],
        init() {
            this.messages = [{ role: 'bot', text: seed, proposal: null, decision: null }];
            this.scrollSoon();
        },
        ask(text) {
            this.input = text;
            this.send();
        },
        render(text) {
            return window.ukcMarkdownLite(text);
        },
        csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },
        async send() {
            const question = this.input.trim();
            if (!question || this.sending) return;

            const history = this.messages.map((m) => ({ role: m.role, text: m.text }));
            this.messages.push({ role: 'user', text: question, proposal: null, decision: null });
            this.input = '';
            this.sending = true;
            this.scrollSoon();

            try {
                const res = await fetch(askUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                    body: JSON.stringify({ question, history }),
                });
                const data = await res.json().catch(() => ({}));
                this.messages.push({
                    role: 'bot',
                    text: data.answer || 'Une erreur est survenue. Réessayez dans un instant.',
                    proposal: data.proposal || null,
                    decision: null,
                });
            } catch (e) {
                this.messages.push({ role: 'bot', text: 'Connexion impossible. Vérifiez votre réseau et réessayez.', proposal: null, decision: null });
            } finally {
                this.sending = false;
                this.scrollSoon();
            }
        },
        async decide(index, decision) {
            const m = this.messages[index];
            if (!m || !m.proposal || m.decision) return;
            m.decision = decision;
            try {
                const res = await fetch(decideUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                    body: JSON.stringify({ title: m.proposal.title, decision }),
                });
                const data = await res.json().catch(() => ({}));
                if (data.entry) this.decisionLog.unshift(data.entry);
            } catch (e) {
                /* silencieux : la décision reste affichée côté chat */
            }
        },
        decisionLabel(d) {
            return { approve: 'Approuvé par admin', edit: 'À modifier', reject: 'Refusé par admin' }[d] || '';
        },
        decisionPill(d) {
            return { approve: 'ok', edit: 'info', reject: 'warn' }[d] || 'muted';
        },
        scrollSoon() {
            this.$nextTick(() => {
                const log = this.$refs.log;
                if (log) log.scrollTop = log.scrollHeight;
            });
        },
    }));

    // Surbrillance de la section active dans la navigation (scrollspy).
    window.Alpine.data('ukcScrollspy', () => ({
        observer: null,
        init() {
            const links = Array.from(this.$el.querySelectorAll('[data-spy]'));
            const map = new Map();
            links.forEach((link) => {
                const id = link.getAttribute('href')?.slice(1);
                const section = id && document.getElementById(id);
                if (section) map.set(section, link);
            });
            if (map.size === 0) return;

            this.observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    links.forEach((l) => {
                        l.classList.remove('spy-active');
                        l.removeAttribute('aria-current');
                    });
                    const active = map.get(entry.target);
                    if (active) {
                        active.classList.add('spy-active');
                        active.setAttribute('aria-current', 'true');
                    }
                });
            }, { rootMargin: '-45% 0px -50% 0px', threshold: 0 });

            map.forEach((_, section) => this.observer.observe(section));
        },
        destroy() {
            this.observer?.disconnect();
        },
    }));

    // Wizard de soumission (sans Livewire) : navigation d'étapes, co-auteurs
    // répétables, mots-clés « tags », récapitulatif. Les valeurs vivent côté
    // client et sont soumises via un POST multipart classique.
    window.Alpine.data('submissionWizard', (initial = {}) => ({
        step: initial.errorStep || 1,
        title: initial.title || '',
        track: initial.track || '',
        type: initial.type || 'oral',
        keywords: Array.isArray(initial.keywords) ? initial.keywords : [],
        kwDraft: '',
        coauthors: Array.isArray(initial.coauthors) ? initial.coauthors : [],

        get keywordsString() {
            return this.keywords.join(', ');
        },
        addTag() {
            const v = this.kwDraft.trim().replace(/,+$/, '').trim();
            if (v && !this.keywords.includes(v)) this.keywords.push(v);
            this.kwDraft = '';
        },
        removeTag(i) {
            this.keywords.splice(i, 1);
        },
        tagBackspace() {
            if (this.kwDraft === '' && this.keywords.length) this.keywords.pop();
        },
        addCoauthor() {
            this.coauthors.push({ name: '', email: '', affiliation: '', grade: '' });
        },
        removeCoauthor(i) {
            this.coauthors.splice(i, 1);
        },
        get coauthorCount() {
            return this.coauthors.filter((c) => (c.name || '').trim() || (c.email || '').trim()).length;
        },
        abstractFilled() {
            const el = document.querySelector('input[name="abstract"]');
            return !!(el && el.value.replace(/<[^>]*>/g, '').trim().length);
        },
        fileChosen() {
            const el = document.getElementById('file');
            return !!(el && el.files && el.files.length);
        },
    }));

    // Pointage des badges (admin) : recherche + check-in par fetch (JSON) ;
    // la caméra reste gérée par scanner.js qui émet un évènement « scan ».
    window.Alpine.data('badgeScan', (checkedCount = 0, totalCount = 0, sessions = []) => ({
        found: null,
        message: '',
        messageType: '',
        recent: [],
        code: '',
        checkedCount,
        totalCount,
        sessions: Array.isArray(sessions) ? sessions : [],
        sessionId: 0, // 0 = mode accueil ; >0 = mode séance
        _csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },
        async lookup(raw) {
            const text = String(raw ?? this.code).trim();
            this.code = '';
            if (!text) return;
            try {
                const res = await fetch('/admin/pointage/rechercher', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this._csrf() },
                    body: JSON.stringify({ code: text, session_id: this.sessionId }),
                });
                const data = await res.json();
                if (data.error) { this.found = null; this.flash(data.error, 'err'); }
                else if (data.found) { this.found = data.found; this.message = ''; }
            } catch (e) {
                this.flash('Recherche impossible. Réessayez.', 'err');
            }
        },
        async checkIn() {
            if (!this.found) return;
            try {
                const res = await fetch('/admin/pointage/' + this.found.id + '/pointer', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this._csrf() },
                    body: JSON.stringify({ session_id: this.sessionId }),
                });
                const data = await res.json();
                if (data.found) this.found = data.found;
                if (data.error) { this.flash(data.error, 'err'); return; }
                this.flash(data.message, 'ok');
                if (data.newlyCheckedIn) {
                    if (!this.sessionId) this.checkedCount++;
                    this.recent.unshift(data.recentEntry);
                    this.recent = this.recent.slice(0, 8);
                }
            } catch (e) {
                this.flash('Pointage impossible. Réessayez.', 'err');
            }
        },
        clearFound() { this.found = null; this.message = ''; },
        flash(m, t) { this.message = m; this.messageType = t; },
    }));

    // Engagement live d'une séance (Q&A + sondages) : sondage périodique par fetch.
    window.Alpine.data('sessionEngagement', (config = {}) => ({
        urls: config.urls || {},
        canPost: !!config.canPost,
        canModerate: !!config.canModerate,
        questions: [],
        polls: [],
        draft: '',
        pollDraft: { question: '', options: ['', ''] },
        _timer: null,
        _csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },
        init() {
            this.refresh();
            // Rafraîchissement doux toutes les 8 s, en pause si l'onglet est masqué.
            this._timer = setInterval(() => { if (!document.hidden) this.refresh(); }, 8000);
        },
        async refresh() {
            await Promise.all([this.refreshQuestions(), this.refreshPolls()]);
        },
        async refreshQuestions() {
            try {
                const res = await fetch(this.urls.questions, { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                this.questions = data.questions || [];
            } catch (e) { /* réseau : on retentera au prochain tick */ }
        },
        async refreshPolls() {
            if (!this.urls.polls) return;
            try {
                const res = await fetch(this.urls.polls, { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                this.polls = data.polls || [];
            } catch (e) { /* ignore */ }
        },
        pollReady() {
            return this.pollDraft.question.trim() && this.pollDraft.options.filter(o => o.trim()).length >= 2;
        },
        async createPoll() {
            if (!this.pollReady()) return;
            try {
                const res = await fetch(this.urls.createPoll, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this._csrf() },
                    body: JSON.stringify({ question: this.pollDraft.question.trim(), options: this.pollDraft.options }),
                });
                if (res.ok) { this.pollDraft = { question: '', options: ['', ''] }; this.refreshPolls(); }
            } catch (e) { /* ignore */ }
        },
        async votePoll(p, choice) {
            if (!this.canPost || p.status !== 'open') return;
            try {
                const res = await fetch(this.urls.pollBase + '/' + p.id + '/vote', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this._csrf() },
                    body: JSON.stringify({ choice }),
                });
                if (res.ok) { p.mine = choice; this.refreshPolls(); }
            } catch (e) { /* ignore */ }
        },
        async togglePoll(p) {
            try {
                const res = await fetch(this.urls.pollBase + '/' + p.id + '/etat', {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': this._csrf() },
                });
                if (res.ok) { const d = await res.json(); p.status = d.status; this.refreshPolls(); }
            } catch (e) { /* ignore */ }
        },
        async ask() {
            const body = this.draft.trim();
            if (!body) return;
            try {
                const res = await fetch(this.urls.ask, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this._csrf() },
                    body: JSON.stringify({ body }),
                });
                if (res.ok) { this.draft = ''; this.refresh(); }
            } catch (e) { /* ignore */ }
        },
        async toggleVote(q) {
            if (!this.canPost) return;
            try {
                const res = await fetch(this.urls.vote + '/' + q.id + '/vote', {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': this._csrf() },
                });
                if (!res.ok) return;
                const data = await res.json();
                q.voted = data.voted;
                q.votes = data.votes;
            } catch (e) { /* ignore */ }
        },
        async moderate(q, status) {
            try {
                const res = await fetch(this.urls.moderate + '/' + q.id + '/moderation', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this._csrf() },
                    body: JSON.stringify({ status }),
                });
                if (res.ok) { q.status = status; this.refresh(); }
            } catch (e) { /* ignore */ }
        },
    }));

    // Éditeur d'agents IA (admin) : onglets, ajout/suppression côté client ;
    // les avatars sont téléversés à l'enregistrement du formulaire.
    window.Alpine.data('agentsEditor', (agents = []) => ({
        agents: Array.isArray(agents) ? agents : [],
        active: 0,
        addAgent() {
            this.agents.push({
                key: 'agent-' + Math.random().toString(36).slice(2, 6),
                name: 'Nouvel agent', audience: 'participants', enabled: true,
                avatar: '', persona: '', positive: '', negative: '',
            });
            this.active = this.agents.length - 1;
        },
        removeAgent(i) {
            this.agents.splice(i, 1);
            this.active = Math.max(0, Math.min(this.active, this.agents.length - 1));
        },
    }));

    // Listes répétables du formulaire d'édition (admin) : tarifs, comités,
    // partenaires, FAQ — ajout/suppression côté client.
    const editionArraysFactory = (init = {}) => ({
        fees: Array.isArray(init.fees) ? init.fees : [],
        committees: Array.isArray(init.committees) ? init.committees : [],
        partners: Array.isArray(init.partners) ? init.partners : [],
        faq: Array.isArray(init.faq) ? init.faq : [],
        keyDates: Array.isArray(init.keyDates) ? init.keyDates : [],
        programme: Array.isArray(init.programme) ? init.programme : [],
        axes: Array.isArray(init.axes) ? init.axes : [],
        why: Array.isArray(init.why) ? init.why : [],
        infoCards: Array.isArray(init.infoCards) ? init.infoCards : [],
        stats: Array.isArray(init.stats) ? init.stats : [],
        refAxes: Array.isArray(init.refAxes) ? init.refAxes : [],
        addFee() { this.fees.push({ label: '', amount: '' }); },
        removeFee(i) { this.fees.splice(i, 1); },
        addCommittee() { this.committees.push({ role: 'organisation', person_name: '', person_title: '', institution: '', photo_url: '' }); },
        removeCommittee(i) { this.committees.splice(i, 1); },
        addPartner() { this.partners.push({ name: '', url: '', logo_url: '', level: '' }); },
        removePartner(i) { this.partners.splice(i, 1); },
        addFaq() { this.faq.push({ question: '', answer: '' }); },
        removeFaq(i) { this.faq.splice(i, 1); },
        addKeyDate() { this.keyDates.push({ date: '', label: '' }); },
        removeKeyDate(i) { this.keyDates.splice(i, 1); },
        addProg() { this.programme.push({ day: '', time: '', type: 'Session', title: '', speaker: '', room: '' }); },
        removeProg(i) { this.programme.splice(i, 1); },
        addAxis() { this.axes.push({ label: '' }); },
        removeAxis(i) { this.axes.splice(i, 1); },
        addWhy() { this.why.push({ title: '', text: '' }); },
        removeWhy(i) { this.why.splice(i, 1); },
        addInfo() { this.infoCards.push({ title: '', text: '' }); },
        removeInfo(i) { this.infoCards.splice(i, 1); },
        addStat() { this.stats.push({ label: '', value: '' }); },
        removeStat(i) { this.stats.splice(i, 1); },
    });
    window.Alpine.data('editionArrays', editionArraysFactory);
    window.__editionArrays = editionArraysFactory;

    // Zone de dépôt de fichier (Tailwise) : glisser-déposer + aperçu, l'input
    // natif reste la source (envoi synchrone avec le formulaire).
    window.Alpine.data('ukcFiledrop', () => ({
        over: false,
        file: null,
        size: '',
        pick(e) { this.set(e.target.files && e.target.files[0]); },
        drop(e) {
            this.over = false;
            const dt = e.dataTransfer;
            if (dt && dt.files && dt.files[0]) {
                this.$refs.input.files = dt.files;
                this.set(dt.files[0]);
            }
        },
        set(f) { this.file = f || null; this.size = f ? this.fmt(f.size) : ''; },
        clear() { this.file = null; this.size = ''; this.$refs.input.value = ''; },
        fmt(b) { return b > 1048576 ? (b / 1048576).toFixed(1) + ' Mo' : Math.max(1, Math.round(b / 1024)) + ' Ko'; },
    }));

    // Palette de commandes « Recherche rapide » (⌘K / Ctrl+K) — style Tailwise.
    // Les items sont rendus côté serveur (avec leurs icônes) ; on filtre en
    // masquant les lignes qui ne correspondent pas, sans backend.
    window.Alpine.data('ukcPalette', () => ({
        open: false,
        q: '',
        active: 0,
        get rows() {
            return this.$refs.list ? Array.from(this.$refs.list.querySelectorAll('[data-item]')) : [];
        },
        norm(s) {
            return String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
        },
        get matches() {
            const q = this.norm(this.q);
            return this.rows.filter((el) => !q || this.norm(el.dataset.k).includes(q));
        },
        openPalette() {
            this.open = true; this.q = ''; this.active = 0;
            this.$nextTick(() => { this.$refs.input && this.$refs.input.focus(); this.paint(); });
        },
        close() { this.open = false; },
        hotkey(e) {
            if ((e.metaKey || e.ctrlKey) && String(e.key).toLowerCase() === 'k') {
                e.preventDefault();
                this.open ? this.close() : this.openPalette();
            }
        },
        paint() {
            const m = this.matches;
            if (this.active >= m.length) this.active = Math.max(0, m.length - 1);
            this.rows.forEach((el) => { el.hidden = true; el.classList.remove('active'); });
            m.forEach((el, i) => { el.hidden = false; if (i === this.active) el.classList.add('active'); });
            if (this.$refs.empty) this.$refs.empty.hidden = m.length > 0;
        },
        onInput() { this.active = 0; this.paint(); },
        move(d) {
            const m = this.matches;
            if (!m.length) return;
            this.active = (this.active + d + m.length) % m.length;
            this.paint();
            m[this.active] && m[this.active].scrollIntoView({ block: 'nearest' });
        },
        go() {
            const m = this.matches;
            if (m.length) { m[this.active].click(); }
            else if (this.$refs.fallback && this.q.trim()) {
                this.$refs.fallback.href = this.$refs.fallback.dataset.base + encodeURIComponent(this.q.trim());
                this.$refs.fallback.click();
            }
        },
    }));

    // Éditeur de champs personnalisés du formulaire de soumission (admin), sans
    // Livewire : ajout / suppression / réordonnancement côté client.
    window.Alpine.data('formEditor', (custom = []) => ({
        custom: Array.isArray(custom) ? custom : [],
        add(type = 'text') {
            this.custom.push({
                key: 'custom_' + Math.random().toString(36).slice(2, 8),
                label: '', type: type || 'text', required: false, help: '', options_text: '', open: true,
            });
        },
        remove(i) {
            this.custom.splice(i, 1);
        },
        move(i, dir) {
            const j = i + dir;
            if (j < 0 || j >= this.custom.length) return;
            [this.custom[i], this.custom[j]] = [this.custom[j], this.custom[i]];
        },
    }));

    // Autocomplétion de l'affiliation via HAL (endpoint JSON), sans Livewire.
    window.Alpine.data('ukcAffiliation', (enabled = false) => ({
        enabled,
        suggestions: [],
        open: false,
        timer: null,
        onInput() {
            if (!this.enabled) return;
            clearTimeout(this.timer);
            const q = this.$refs.input.value.trim();
            if (q.length < 3) { this.suggestions = []; this.open = false; return; }
            this.timer = setTimeout(() => this.search(q), 400);
        },
        async search(q) {
            try {
                const res = await fetch('/mon-profil/affiliations?q=' + encodeURIComponent(q), {
                    headers: { Accept: 'application/json' },
                });
                const data = await res.json();
                this.suggestions = Array.isArray(data) ? data : [];
                this.open = this.suggestions.length > 0;
            } catch (e) {
                this.suggestions = [];
                this.open = false;
            }
        },
        choose(label) {
            this.$refs.input.value = label;
            this.suggestions = [];
            this.open = false;
        },
    }));

    // Champ de mots-clés « tags » (style Select2, multi) — sans dépendance.
    // La liste est synchronisée vers un input caché « a, b, c » côté vue.
    window.Alpine.data('ukcTags', (initial = '') => ({
        tags: String(initial || '').split(',').map((t) => t.trim()).filter(Boolean),
        draft: '',
        add() {
            const v = this.draft.trim().replace(/,+$/, '').trim();
            if (v && !this.tags.includes(v)) this.tags.push(v);
            this.draft = '';
        },
        remove(i) {
            this.tags.splice(i, 1);
        },
        backspace() {
            if (this.draft === '' && this.tags.length) this.tags.pop();
        },
    }));

    // Éditeur de texte enrichi minimal (WYSIWYG) — gras/italique/souligné/listes.
    // Sync le HTML vers un input caché (soumission de formulaire classique).
    window.Alpine.data('ukcEditor', (initial = '') => ({
        count: 0,
        init() {
            this.$refs.area.innerHTML = initial || '';
            this.sync();
        },
        cmd(command) {
            this.$refs.area.focus();
            document.execCommand(command, false, null);
            this.sync();
        },
        sync() {
            if (this.$refs.hidden) this.$refs.hidden.value = this.$refs.area.innerHTML;
            this.updateCount();
        },
        updateCount() {
            this.count = (this.$refs.area.innerText || '').replace(/\s+/g, ' ').trim().length;
        },
    }));

    window.Alpine.data('ukcCountdown', (targetIso) => ({
        days: 0,
        hours: 0,
        minutes: 0,
        seconds: 0,
        timer: null,
        init() {
            this.tick();
            this.timer = setInterval(() => this.tick(), 1000);
        },
        destroy() {
            clearInterval(this.timer);
        },
        tick() {
            const diff = Math.max(0, new Date(targetIso).getTime() - Date.now());
            const total = Math.floor(diff / 1000);
            this.days = Math.floor(total / 86400);
            this.hours = Math.floor((total % 86400) / 3600);
            this.minutes = Math.floor((total % 3600) / 60);
            this.seconds = total % 60;
        },
    }));

    // Éditeur visuel de modèles (badges / attestations) : drag-drop sur canvas,
    // même schéma de mise en page que LayoutSanitizer/LayoutRenderer côté serveur.
    window.Alpine.data('docEditor', (config) => ({
        layout: { width: 320, height: 440, bg: '#ffffff', accent: '#223ea4', elements: [] },
        vars: {},
        selectedIndex: null,
        scale: 1,
        _drag: null,
        _onMove: null,
        _onUp: null,

        cfg: {},
        status: '',
        init() {
            this.cfg = config || {};
            this.setLayout(config.layout, config.vars);
            this._fit = () => this.fit();
            window.addEventListener('resize', this._fit);
            // Mise à jour du canvas poussée par la génération IA (évènement fenêtre).
            this._onLayout = (e) => { if (e.detail && e.detail.layout) this.setLayout(e.detail.layout, e.detail.vars); };
            window.addEventListener('layout-updated', this._onLayout);
        },
        destroy() {
            window.removeEventListener('resize', this._fit);
            window.removeEventListener('layout-updated', this._onLayout);
        },

        setLayout(layout, vars) {
            this.layout = JSON.parse(JSON.stringify(layout));
            if (vars) this.vars = vars;
            this.selectedIndex = null;
            this.$nextTick(() => this.fit());
        },

        fit() {
            const wrap = this.$refs.canvasWrap;
            if (!wrap || !this.layout) return;
            this.scale = Math.min(1, (wrap.clientWidth - 2) / this.layout.width);
        },

        get selected() {
            return this.selectedIndex !== null ? this.layout.elements[this.selectedIndex] : null;
        },
        get wrapHeight() {
            return Math.round(this.layout.height * this.scale);
        },

        select(i) { this.selectedIndex = i; },
        deselect() { this.selectedIndex = null; },

        addElement(type) {
            const base = { type, x: 20, y: 20 };
            if (type === 'text') Object.assign(base, { w: 200, h: 32, text: 'Nouveau texte', size: 18, weight: 600, color: '#1a2332', align: 'left' });
            else if (type === 'rect') Object.assign(base, { w: 200, h: 8, color: this.layout.accent });
            else if (type === 'image') Object.assign(base, { w: 140, h: 90, src: '' });
            else if (type === 'qr') Object.assign(base, { size: 96, value: '{verify_url}' });
            this.layout.elements.push(base);
            this.selectedIndex = this.layout.elements.length - 1;
        },
        deleteSelected() {
            if (this.selectedIndex === null) return;
            this.layout.elements.splice(this.selectedIndex, 1);
            this.selectedIndex = null;
        },
        moveLayer(dir) {
            const i = this.selectedIndex;
            if (i === null) return;
            const j = i + dir;
            if (j < 0 || j >= this.layout.elements.length) return;
            const els = this.layout.elements;
            [els[i], els[j]] = [els[j], els[i]];
            this.selectedIndex = j;
        },

        elWidth(el) { return el.type === 'qr' ? el.size : el.w; },
        elHeight(el) { return el.type === 'qr' ? el.size : el.h; },
        elStyle(el) {
            return `left:${el.x}px;top:${el.y}px;width:${this.elWidth(el)}px;height:${this.elHeight(el)}px;`;
        },
        textStyle(el) {
            return `font-size:${el.size}px;font-weight:${el.weight};color:${el.color};text-align:${el.align};`;
        },

        startDrag(e, i) {
            e.preventDefault();
            this.select(i);
            const el = this.layout.elements[i];
            this._drag = { i, mode: 'move', sx: e.clientX, sy: e.clientY, ox: el.x, oy: el.y };
            this._bind();
        },
        startResize(e, i) {
            e.preventDefault(); e.stopPropagation();
            this.select(i);
            const el = this.layout.elements[i];
            this._drag = { i, mode: 'resize', sx: e.clientX, sy: e.clientY, ow: this.elWidth(el), oh: this.elHeight(el) };
            this._bind();
        },
        _bind() {
            this._onMove = (e) => this._move(e);
            this._onUp = () => this._end();
            window.addEventListener('mousemove', this._onMove);
            window.addEventListener('mouseup', this._onUp);
        },
        _move(e) {
            if (!this._drag) return;
            const d = this._drag;
            const dx = (e.clientX - d.sx) / this.scale;
            const dy = (e.clientY - d.sy) / this.scale;
            const el = this.layout.elements[d.i];
            if (d.mode === 'move') {
                el.x = Math.max(0, Math.round(d.ox + dx));
                el.y = Math.max(0, Math.round(d.oy + dy));
            } else if (el.type === 'qr') {
                el.size = Math.max(24, Math.round(d.ow + dx));
            } else {
                el.w = Math.max(8, Math.round(d.ow + dx));
                el.h = Math.max(8, Math.round(d.oh + dy));
            }
        },
        _end() {
            window.removeEventListener('mousemove', this._onMove);
            window.removeEventListener('mouseup', this._onUp);
            this._drag = null;
        },

        substitute(text) {
            return (text || '').replace(/\{(\w+)\}/g, (m, k) => (this.vars[k] != null ? this.vars[k] : m));
        },
        insertVar(v) {
            const el = this.selected;
            if (el && el.type === 'text') el.text = (el.text || '') + v;
        },
        applyAccent(color) {
            this.layout.accent = color;
            this.layout.elements.forEach((el) => { if (el.type === 'rect') el.color = color; });
        },

        async save() {
            if (!this.cfg.saveUrl) return;
            try {
                const res = await fetch(this.cfg.saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json', Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        edition_id: this.cfg.edition_id, kind: this.cfg.kind, type: this.cfg.type,
                        layout: JSON.parse(JSON.stringify(this.layout)),
                    }),
                });
                const data = await res.json();
                if (data.ok) {
                    if (data.layout) this.setLayout(data.layout, this.vars);
                    this.status = 'Modèle enregistré.';
                    setTimeout(() => { this.status = ''; }, 2500);
                } else {
                    this.status = data.error || 'Enregistrement impossible.';
                }
            } catch (e) {
                this.status = 'Enregistrement impossible.';
            }
        },
    }));
});

// Démarre Alpine une fois tous les composants enregistrés.
Alpine.start();
