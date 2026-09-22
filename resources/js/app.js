import Alpine from 'alpinejs';
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.css';
import intlTelInput from 'intl-tel-input/intlTelInputWithUtils';
import 'intl-tel-input/styles';

// Alpine est désormais une dépendance autonome (Livewire retiré du projet).
window.Alpine = Alpine;

// Champs téléphone internationaux (intl-tel-input) : drapeau + indicatif pays,
// saisie nationale, et normalisation en E.164 (« +228… ») à la soumission du
// formulaire — le champ reste nommé `phone`/`contact_phone`, validation serveur
// inchangée. Togo en tête, voisins CEDEAO/CEMAC ensuite.
function initPhoneInputs(root = document) {
    root.querySelectorAll('input[data-ukc-tel]').forEach((input) => {
        if (input.dataset.itiReady) return;
        input.dataset.itiReady = '1';

        const iti = intlTelInput(input, {
            initialCountry: 'tg',
            countryOrder: ['tg', 'bj', 'gh', 'ci', 'bf', 'ne', 'ng', 'sn', 'ml', 'cm', 'fr', 'be'],
            separateDialCode: true,
            strictMode: true,
        });

        // À la soumission : remplace la valeur par le format E.164 si disponible.
        const form = input.form;
        if (form) {
            form.addEventListener('submit', () => {
                const full = iti.getNumber();
                if (full) input.value = full;
            });
        }
    });
}

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
// Éditeur de résumé (CKEditor 5, WYSIWYG type Tailwise) : chargé à la demande
// (import dynamique) uniquement sur les pages qui en ont besoin. Le champ reste
// une <textarea name="…"> tenue à jour à chaque frappe ; compteur de MOTS natif.
async function initAbstractEditors(root = document) {
    const els = [...root.querySelectorAll('textarea[data-ckeditor]')].filter((el) => !el.dataset.ckReady);
    if (!els.length) return;

    const ck = await import('ckeditor5');
    await import('ckeditor5/ckeditor5.css');
    const { ClassicEditor, Essentials, Paragraph, Heading, Bold, Italic, Underline,
        List, Link, BlockQuote, WordCount, Autoformat, PasteFromOffice } = ck;

    els.forEach((el) => {
        el.dataset.ckReady = '1';
        ClassicEditor.create(el, {
            // CKEditor 5 (v44+) impose une clé de licence ; usage open-source = « GPL ».
            licenseKey: 'GPL',
            plugins: [Essentials, Paragraph, Heading, Bold, Italic, Underline, List, Link, BlockQuote, WordCount, Autoformat, PasteFromOffice],
            toolbar: ['heading', '|', 'bold', 'italic', 'underline', '|', 'bulletedList', 'numberedList', '|', 'link', 'blockQuote', '|', 'undo', 'redo'],
        }).then((editor) => {
            const out = el.dataset.countTarget ? document.getElementById(el.dataset.countTarget) : null;
            const wc = editor.plugins.get('WordCount');
            // Émet le contenu + le nombre de mots : la textarea (soumission) est tenue
            // à jour, le compteur affiché, et un évènement notifie le wizard (Alpine).
            const emit = () => {
                const html = editor.getData();
                el.value = html;
                if (out) out.textContent = wc.words;
                document.dispatchEvent(new CustomEvent('ukc:abstract', {
                    detail: { name: el.name || 'abstract', html, words: wc.words },
                }));
            };
            editor.model.document.on('change:data', emit);
            wc.on('update', emit);
            emit();
        }).catch((e) => {
            // Repli : si l'éditeur échoue, on révèle la textarea brute pour rester utilisable.
            console.error('CKEditor init failed:', e);
            el.style.display = 'block';
        });
    });
}

document.addEventListener('DOMContentLoaded', () => { initTomSelects(); initPhoneInputs(); initAbstractEditors(); });
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
// Toast unifié (même rendu que <x-ukc.toast> : carte en bas à droite, icône +
// fermeture). Réutilise le conteneur `.ukc-toasts` s'il existe (flash serveur),
// sinon en crée un — un seul empilement, cohérent dans tout le projet.
function ukcToast(msg, type = 'ok') {
    let host = document.querySelector('.ukc-toasts');
    if (!host) {
        host = document.createElement('div');
        host.className = 'ukc-toasts';
        host.setAttribute('aria-live', 'polite');
        document.body.appendChild(host);
    }
    const icon = type === 'err'
        ? '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 2 1 21h22L12 2zm1 14h-2v2h2v-2zm0-6h-2v4h2v-4z"/></svg>'
        : '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>';
    const el = document.createElement('div');
    el.className = 'ukc-toast ukc-toast--' + (type === 'err' ? 'err' : 'ok');
    el.setAttribute('role', 'status');
    el.innerHTML = '<span class="ukc-toast__ico">' + icon + '</span><span class="ukc-toast__msg"></span>'
        + '<button type="button" class="ukc-toast__close" aria-label="Fermer">'
        + '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>';
    el.querySelector('.ukc-toast__msg').textContent = msg;
    const remove = () => { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); };
    el.querySelector('.ukc-toast__close').addEventListener('click', remove);
    host.appendChild(el);
    setTimeout(remove, 4000);
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
        currentUserId: config.currentUserId || null,
        filters: { assignee: '', phase: '', q: '' },
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
        cal: null,
        charts: null,
        ganttLabelW: 200,
        ganttZoom: 1,
        init() {
            try {
                const w = parseInt(localStorage.getItem('ukc.gantt.labelW') || '', 10);
                if (w >= 120 && w <= 600) this.ganttLabelW = w;
                const z = parseFloat(localStorage.getItem('ukc.gantt.zoom') || '');
                if (z >= 0.4 && z <= 4) this.ganttZoom = z;
            } catch (e) { /* stockage indisponible : on garde les valeurs par défaut */ }
        },

        _csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },
        _headers() {
            return { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this._csrf() };
        },
        notify(msg, type = 'ok') {
            window.ukcToast(msg, type);
        },

        // ---- Filtres (Tableau / Liste / Gantt / Diagrammes) ----
        filtered() {
            const f = this.filters;
            const q = (f.q || '').trim().toLowerCase();
            return this.tasks.filter((t) => {
                if (f.assignee === '__none' && t.assignee_id) return false;
                if (f.assignee === '__me' && String(t.assignee_id || '') !== String(this.currentUserId || '')) return false;
                if (f.assignee && !['__none', '__me'].includes(f.assignee) && String(t.assignee_id || '') !== String(f.assignee)) return false;
                if (f.phase && (t.phase || '') !== f.phase) return false;
                if (q && !(t.title || '').toLowerCase().includes(q)) return false;
                return true;
            });
        },
        phaseOptions() {
            return [...new Set(this.tasks.map((t) => t.phase).filter(Boolean))].sort();
        },
        hasFilter() {
            return !!(this.filters.assignee || this.filters.phase || this.filters.q);
        },
        clearFilters() {
            this.filters = { assignee: '', phase: '', q: '' };
            this.refreshCharts();
        },

        // ---- Dérivés (kanban / liste / indicateurs) ----
        colTasks(status) {
            return this.filtered().filter((t) => t.status === status).sort((a, b) => (a.position || 0) - (b.position || 0));
        },
        count(status) {
            return this.filtered().filter((t) => t.status === status).length;
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
            return { id: null, title: '', description: '', status, priority: 'normal', phase: '', link_key: '', assignee_id: '', start_date: '', due_date: '', checklist: [], comments: [] };
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
                phase: t.phase || '', link_key: t.link_key || '', assignee_id: t.assignee_id || '',
                start_date: t.start_date || '', due_date: t.due_date || '',
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
                    assignee_id: this.task.assignee_id || null,
                    start_date: this.task.start_date || null, due_date: this.task.due_date || null,
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

            const rows = this.filtered();
            const phases = {};
            rows.forEach((t) => {
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
            rows.forEach((t) => { const n = t.assignee || 'Non assigné'; byAssignee[n] = (byAssignee[n] || 0) + 1; });
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

        // ---- Diagramme de Gantt (CSS, live) ----
        _d(iso) { return iso ? new Date(iso + 'T00:00:00') : null; },
        ganttRows() {
            return this.filtered()
                .filter((t) => t.start_date || t.due_date)
                .map((t) => ({ ...t, gs: t.start_date || t.due_date, ge: t.due_date || t.start_date }))
                .sort((a, b) => this._d(a.gs) - this._d(b.gs));
        },
        ganttBounds() {
            const ts = [];
            this.ganttRows().forEach((r) => { ts.push(this._d(r.gs).getTime(), this._d(r.ge).getTime()); });
            this.milestones.forEach((m) => { if (m.due_date) ts.push(this._d(m.due_date).getTime()); });
            if (!ts.length) return null;
            let min = Math.min(...ts);
            let max = Math.max(...ts);
            if (max <= min) max = min + 7 * 86400000;
            const pad = Math.round((max - min) * 0.04);
            return { min: min - pad, max: max + pad };
        },
        gPct(iso) {
            const b = this.ganttBounds();
            if (!b) return 0;
            return ((this._d(iso).getTime() - b.min) / (b.max - b.min)) * 100;
        },
        gWidth(r) {
            return Math.max(1.5, this.gPct(r.ge) - this.gPct(r.gs));
        },
        ganttTicks() {
            const b = this.ganttBounds();
            if (!b) return [];
            const ticks = [];
            const d = new Date(b.min);
            d.setDate(1);
            let guard = 0;
            while (d.getTime() <= b.max && guard < 60) {
                const left = ((d.getTime() - b.min) / (b.max - b.min)) * 100;
                if (left >= 0) ticks.push({ label: d.toLocaleDateString('fr-FR', { month: 'short', year: '2-digit' }), left });
                d.setMonth(d.getMonth() + 1);
                guard += 1;
            }
            return ticks;
        },
        ganttMarkers() {
            return this.milestones.filter((m) => m.due_date).map((m) => ({ ...m, left: this.gPct(m.due_date) }));
        },
        // Largeur de piste (px) pilotée par le zoom → règle la longueur des périodes.
        ganttGridStyle() {
            const track = Math.round(820 * this.ganttZoom);
            return `grid-template-columns:${this.ganttLabelW}px ${track}px`;
        },
        _saveGantt() {
            try {
                localStorage.setItem('ukc.gantt.labelW', String(this.ganttLabelW));
                localStorage.setItem('ukc.gantt.zoom', String(this.ganttZoom));
            } catch (e) { /* ignore */ }
        },
        // Glisser la poignée pour élargir / réduire la colonne des tâches.
        startLabelResize(e) {
            e.preventDefault();
            const startX = e.clientX;
            const startW = this.ganttLabelW;
            const move = (ev) => { this.ganttLabelW = Math.max(120, Math.min(560, startW + (ev.clientX - startX))); };
            const up = () => {
                window.removeEventListener('mousemove', move);
                window.removeEventListener('mouseup', up);
                document.body.style.userSelect = '';
                this._saveGantt();
            };
            document.body.style.userSelect = 'none';
            window.addEventListener('mousemove', move);
            window.addEventListener('mouseup', up);
        },
        ganttZoomIn() { this.ganttZoom = Math.min(4, Math.round((this.ganttZoom + 0.25) * 100) / 100); this._saveGantt(); },
        ganttZoomOut() { this.ganttZoom = Math.max(0.4, Math.round((this.ganttZoom - 0.25) * 100) / 100); this._saveGantt(); },
        ganttZoomReset() { this.ganttZoom = 1; this._saveGantt(); },
    }));
    // Assistante Gesco (widget flottant + page dédiée) : conversation côté client,
    // chaque tour appelle l'endpoint JSON /gesco/demander. Aucune action serveur
    // n'est déclenchée par l'IA — elle informe seulement.
    window.Alpine.data('gescoChat', (seed = '', endpoint = '/gesco/demander', name = 'Gesco', scope = 'guest') => ({
        messages: [],
        input: '',
        sending: false,
        endpoint,
        agentName: name,
        copiedIndex: null,
        showGrow: false,
        // Conservation de la discussion pendant la session (survit aux rechargements
        // et à la navigation dans le même onglet) : sessionStorage + repli silencieux.
        // La clé est cloisonnée par utilisateur : un autre utilisateur (ou un visiteur
        // après déconnexion) ne voit jamais la discussion d'un autre.
        storagePrefix: 'gesco.chat.v1.',
        scope: String(scope || 'guest'),
        get storageKey() { return this.storagePrefix + this.scope; },
        seedText: seed || 'Bonjour, je suis Gesco.',
        init() {
            this.purgeForeignScopes();
            const saved = this.restore();
            this.messages = saved || [{ role: 'bot', text: this.seedText }];
            this.$watch('messages', () => this.persist());
            this.scrollSoon();
        },
        // Retire les discussions rattachées à un autre utilisateur (changement de compte).
        purgeForeignScopes() {
            try {
                for (let i = sessionStorage.length - 1; i >= 0; i--) {
                    const k = sessionStorage.key(i);
                    if (k && k.startsWith(this.storagePrefix) && !k.endsWith('.' + this.scope)) {
                        sessionStorage.removeItem(k);
                    }
                }
            } catch (e) { /* stockage indisponible */ }
        },
        restore() {
            try {
                const raw = sessionStorage.getItem(this.storageKey);
                const arr = raw ? JSON.parse(raw) : null;
                return Array.isArray(arr) && arr.length ? arr : null;
            } catch (e) { return null; }
        },
        persist() {
            try { sessionStorage.setItem(this.storageKey, JSON.stringify(this.messages)); } catch (e) { /* stockage indisponible */ }
        },
        reset() {
            this.messages = [{ role: 'bot', text: this.seedText }];
            this.input = '';
            this.resetInputHeight();
            this.persist();
            this.scrollSoon();
        },
        ask(text) {
            this.input = text;
            this.send();
        },
        render(text) {
            return window.ukcMarkdownLite(text);
        },
        async copy(i, text) {
            try {
                await navigator.clipboard.writeText(text);
                this.copiedIndex = i;
                setTimeout(() => { if (this.copiedIndex === i) this.copiedIndex = null; }, 1200);
            } catch (e) { /* presse-papiers indisponible */ }
        },
        editUser(i) {
            const m = this.messages[i];
            if (!m || m.role !== 'user' || this.sending) return;
            this.input = m.text;
            this.messages = this.messages.slice(0, i); // retire ce message + les suivants
            this.$nextTick(() => { const el = this.$refs.input; if (el) { el.focus(); this.autogrow(el); } });
        },
        autogrow(el) {
            if (!el) return;
            const max = 160;
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, max) + 'px';
            el.style.overflowY = el.scrollHeight > max ? 'auto' : 'hidden';
            // Le bouton Agrandir/Réduire n'apparaît qu'à partir de plus de 3 lignes.
            const cs = getComputedStyle(el);
            const lh = parseFloat(cs.lineHeight) || 20;
            const pt = parseFloat(cs.paddingTop) || 0;
            const pb = parseFloat(cs.paddingBottom) || 0;
            this.showGrow = Math.round((el.scrollHeight - pt - pb) / lh) > 3;
        },
        resetInputHeight() {
            this.showGrow = false;
            this.$nextTick(() => { const el = this.$refs.input; if (el) { el.style.height = 'auto'; el.style.overflowY = 'hidden'; } });
        },
        async send() {
            const question = this.input.trim();
            if (!question || this.sending) return;

            const history = this.messages.slice();
            this.messages.push({ role: 'user', text: question });
            this.input = '';
            this.resetInputHeight();
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
    window.Alpine.data('adminCopilot', (seed = '', askUrl = '', decideUrl = '', executeUrl = '', log = [], name = 'Copilote', editionId = '', scope = '0') => ({
        messages: [],
        input: '',
        sending: false,
        agentName: name,
        editionId: editionId ? String(editionId) : '',
        decisionLog: Array.isArray(log) ? log : [],
        copiedIndex: null,
        showGrow: false,
        // Discussion conservée pendant la session, cloisonnée par édition ET par
        // utilisateur (un autre membre du staff ne voit jamais la discussion d'un autre).
        storagePrefix: 'gesco.copilot.v1.',
        scope: String(scope || '0'),
        get storageKey() { return this.storagePrefix + (this.editionId || 'x') + '.' + this.scope; },
        seedText: seed,
        init() {
            this.purgeForeignScopes();
            const saved = this.restore();
            this.messages = saved || [{ role: 'bot', text: this.seedText, proposal: null, decision: null }];
            this.$watch('messages', () => this.persist());
            this.scrollSoon();
        },
        // Retire les discussions d'un autre utilisateur (changement de compte), toutes éditions confondues.
        purgeForeignScopes() {
            try {
                for (let i = sessionStorage.length - 1; i >= 0; i--) {
                    const k = sessionStorage.key(i);
                    if (k && k.startsWith(this.storagePrefix) && !k.endsWith('.' + this.scope)) {
                        sessionStorage.removeItem(k);
                    }
                }
            } catch (e) { /* stockage indisponible */ }
        },
        restore() {
            try {
                const raw = sessionStorage.getItem(this.storageKey);
                const arr = raw ? JSON.parse(raw) : null;
                return Array.isArray(arr) && arr.length ? arr : null;
            } catch (e) { return null; }
        },
        persist() {
            try { sessionStorage.setItem(this.storageKey, JSON.stringify(this.messages)); } catch (e) { /* stockage indisponible */ }
        },
        reset() {
            this.messages = [{ role: 'bot', text: this.seedText, proposal: null, decision: null }];
            this.input = '';
            this.resetInputHeight();
            this.persist();
            this.scrollSoon();
        },
        ask(text) {
            this.input = text;
            this.send();
        },
        render(text) {
            return window.ukcMarkdownLite(text);
        },
        async copy(i, text) {
            try {
                await navigator.clipboard.writeText(text);
                this.copiedIndex = i;
                setTimeout(() => { if (this.copiedIndex === i) this.copiedIndex = null; }, 1200);
            } catch (e) { /* presse-papiers indisponible */ }
        },
        editUser(i) {
            const m = this.messages[i];
            if (!m || m.role !== 'user' || this.sending) return;
            this.input = m.text;
            this.messages = this.messages.slice(0, i);
            this.$nextTick(() => { const el = this.$refs.input; if (el) { el.focus(); this.autogrow(el); } });
        },
        autogrow(el) {
            if (!el) return;
            const max = 160;
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, max) + 'px';
            el.style.overflowY = el.scrollHeight > max ? 'auto' : 'hidden';
            // Le bouton Agrandir/Réduire n'apparaît qu'à partir de plus de 3 lignes.
            const cs = getComputedStyle(el);
            const lh = parseFloat(cs.lineHeight) || 20;
            const pt = parseFloat(cs.paddingTop) || 0;
            const pb = parseFloat(cs.paddingBottom) || 0;
            this.showGrow = Math.round((el.scrollHeight - pt - pb) / lh) > 3;
        },
        resetInputHeight() {
            this.showGrow = false;
            this.$nextTick(() => { const el = this.$refs.input; if (el) { el.style.height = 'auto'; el.style.overflowY = 'hidden'; } });
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
            this.resetInputHeight();
            this.sending = true;
            this.scrollSoon();

            try {
                const res = await fetch(askUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                    body: JSON.stringify({ question, history, edition_id: this.editionId || null }),
                });
                const data = await res.json().catch(() => ({}));
                this.messages.push({
                    role: 'bot',
                    text: data.answer || 'Une erreur est survenue. Réessayez dans un instant.',
                    proposal: data.proposal || null,
                    action: data.action || null,
                    actionState: null,
                    decision: null,
                });
            } catch (e) {
                this.messages.push({ role: 'bot', text: 'Connexion impossible. Vérifiez votre réseau et réessayez.', proposal: null, action: null, decision: null });
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
        // Exécution d'une action APRÈS autorisation explicite de l'utilisateur : le
        // serveur exécute (et revérifie la capacité), le résultat s'affiche dans le chat.
        async runAction(index) {
            const m = this.messages[index];
            if (!m || !m.action || m.actionState === 'running' || m.actionState === 'done') return;
            m.actionState = 'running';
            try {
                const res = await fetch(executeUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                    body: JSON.stringify({ key: m.action.key, params: m.action.params || {}, edition_id: this.editionId || null }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    m.actionState = 'error';
                    this.messages.push({ role: 'bot', text: data.message || 'L’action a échoué.', proposal: null, action: null, decision: null });
                } else {
                    m.actionState = 'done';
                    if (data.entry) this.decisionLog.unshift(data.entry);
                    this.messages.push({ role: 'bot', text: '✅ ' + (data.message || 'Action exécutée.'), proposal: null, action: null, decision: null });
                }
            } catch (e) {
                m.actionState = 'error';
                this.messages.push({ role: 'bot', text: 'Connexion impossible. Réessayez.', proposal: null, action: null, decision: null });
            } finally {
                this.scrollSoon();
            }
        },
        dismissAction(index) {
            const m = this.messages[index];
            if (m && m.action) m.actionState = 'dismissed';
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
    // répétables, récapitulatif. Résumé (CKEditor) et mots-clés (Tom Select)
    // sont lus depuis le DOM ; POST multipart classique.
    window.Alpine.data('submissionWizard', (initial = {}) => ({
        step: initial.errorStep || 1,
        title: initial.title || '',
        track: initial.track || '',
        type: initial.type || 'oral',
        coauthors: Array.isArray(initial.coauthors) ? initial.coauthors : [],
        abstractHtml: '',
        abstractWordCount: 0,
        keywords: [],
        rules: initial.rules || {},
        account: initial.account || { name: '', email: '', affiliation: '', grade: '' },
        corr: initial.corr || { self: true, name: '', email: '', affiliation: '', grade: '' },

        init() {
            // Le résumé (CKEditor) notifie son contenu + compteur : on le garde en
            // état réactif pour le récapitulatif de l'étape Validation.
            document.addEventListener('ukc:abstract', (e) => {
                if ((e.detail?.name || 'abstract') === 'abstract') {
                    this.abstractHtml = e.detail.html || '';
                    this.abstractWordCount = e.detail.words || 0;
                }
            });
            // Mots-clés (Tom Select) : on capte les changements pour rester réactif.
            const kw = document.getElementById('keywords');
            if (kw) {
                const readKw = () => {
                    this.keywords = String(kw.value || '').split(',').map((s) => s.trim()).filter(Boolean);
                };
                kw.addEventListener('change', readKw);
                readKw();
            }
        },

        addCoauthor() {
            this.coauthors.push({ name: '', email: '', affiliation: '', grade: '' });
        },
        removeCoauthor(i) {
            this.coauthors.splice(i, 1);
        },

        // Auteur correspondant : quand « je suis l'auteur correspondant » est coché,
        // on rétablit l'identité (nom + e-mail) du compte connecté (non falsifiable).
        onSelfToggle() {
            if (this.corr.self) {
                this.corr.name = this.account.name;
                this.corr.email = this.account.email;
            }
        },
        corrInitials() {
            const s = (this.corr.name || '').trim();
            if (!s) return '?';
            const parts = s.split(/\s+/);
            return ((parts[0]?.[0] || '') + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase();
        },
        corrMeta() {
            return [this.corr.affiliation, this.corr.grade].filter((v) => (v || '').trim()).join(' · ')
                || 'Affiliation non renseignée';
        },

        // ---- Navigation contrôlée : on ne passe à l'étape suivante que si les
        //      champs obligatoires de l'étape courante sont renseignés. -----------
        next() {
            const c = this.validateStep(this.step);
            if (!c.ok) { window.ukcToast?.(c.msg, 'err'); return; }
            if (this.step < (this.rules.lastStep || this.step)) this.step++;
        },
        prev() {
            if (this.step > 1) this.step--;
        },
        // Saut direct via les pastilles d'étapes : en arrière libre, en avant on
        // valide chaque étape intermédiaire (impossible de sauter un champ requis).
        goTo(n) {
            if (n <= this.step) { this.step = n; return; }
            for (let s = this.step; s < n; s++) {
                const c = this.validateStep(s);
                if (!c.ok) { window.ukcToast?.(c.msg, 'err'); this.step = s; return; }
            }
            this.step = n;
        },
        _ok() { return { ok: true, msg: '' }; },
        _fail(msg) { return { ok: false, msg }; },
        validateStep(n) {
            const r = this.rules;
            if (n === 1) {
                if (!this.title.trim()) return this._fail('Le titre est obligatoire.');
                if (r.titleMin > 0 && this.title.trim().length < r.titleMin) {
                    return this._fail(`Le titre doit contenir au moins ${r.titleMin} caractères.`);
                }
                if (r.trackRequired && !this.track) return this._fail('Veuillez sélectionner un axe.');
                return this._ok();
            }
            if (n === 2) {
                // Auteur correspondant : identité requise (surtout si ce n'est pas le
                // compte connecté). Quand « self », l'identité vient du compte.
                if (!(this.corr.name || '').trim()) return this._fail('Indiquez le nom de l’auteur correspondant.');
                if (!/^\S+@\S+\.\S+$/.test((this.corr.email || '').trim())) return this._fail('L’auteur correspondant doit avoir un e-mail valide.');
                for (const co of this.coauthors) {
                    const any = ['name', 'email', 'affiliation', 'grade'].some((k) => (co[k] || '').trim());
                    if (!any) continue; // ligne vide : ignorée à l'enregistrement
                    if (!(co.name || '').trim()) return this._fail('Indiquez le nom de chaque co-auteur (ou retirez la ligne).');
                    if (!/^\S+@\S+\.\S+$/.test((co.email || '').trim())) return this._fail('Chaque co-auteur doit avoir un e-mail valide.');
                }
                return this._ok();
            }
            if (n === 3) {
                if (!this.abstractFilled()) return this._fail('Le résumé est obligatoire.');
                if (r.abstractMin > 0 && this.abstractWords() < r.abstractMin) {
                    return this._fail(`Le résumé doit contenir au moins ${r.abstractMin} mots.`);
                }
                if (r.keywordsRequired && this.keywords.length === 0) return this._fail('Ajoutez au moins un mot-clé.');
                if (r.keywordsMin > 0 && this.keywords.length < r.keywordsMin) {
                    return this._fail(`Ajoutez au moins ${r.keywordsMin} mots-clés.`);
                }
                const bad = this.invalidCustomField();
                if (bad) return this._fail(bad);
                return this._ok();
            }
            if (r.fileStep && n === 4) {
                if (r.fileRequired && !this.fileChosen()) return this._fail('Veuillez joindre un fichier.');
                return this._ok();
            }
            return this._ok();
        },
        // Champs personnalisés obligatoires (marqués data-custom-req) non remplis.
        invalidCustomField() {
            const els = document.querySelectorAll('[data-custom-req]');
            for (const el of els) {
                const filled = el.type === 'checkbox' ? el.checked : (el.value || '').trim() !== '';
                if (!filled) return 'Veuillez renseigner les champs obligatoires.';
            }
            return null;
        },
        get coauthorCount() {
            return this.coauthors.filter((c) => (c.name || '').trim() || (c.email || '').trim()).length;
        },
        coauthorList() {
            return this.coauthors.filter((c) => (c.name || '').trim() || (c.email || '').trim());
        },
        // Mots-clés : état réactif alimenté par le champ Tom Select (cf. init).
        keywordList() {
            return this.keywords;
        },
        // Résumé : contenu réactif (émis par CKEditor), repli sur la textarea brute
        // si l'éditeur n'a pas (encore) chargé.
        _abstractRaw() {
            if (this.abstractHtml) return this.abstractHtml;
            const el = document.querySelector('textarea[name="abstract"], input[name="abstract"]');
            return el ? el.value : '';
        },
        abstractFilled() {
            return this._abstractRaw().replace(/<[^>]*>/g, '').replace(/&nbsp;/gi, ' ').trim().length > 0;
        },
        abstractWords() {
            if (this.abstractHtml) return this.abstractWordCount;
            const txt = this._abstractRaw().replace(/<[^>]*>/g, ' ').replace(/&[a-z]+;/gi, ' ').trim();
            return txt ? txt.split(/\s+/).length : 0;
        },
        // Aperçu HTML du résumé saisi (contenu de l'éditeur, pas du fichier joint).
        abstractPreview() {
            return this._abstractRaw();
        },
        fileChosen() {
            const el = document.getElementById('file');
            return !!(el && el.files && el.files.length);
        },
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
                const res = await fetch('/profil/affiliations?q=' + encodeURIComponent(q), {
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
