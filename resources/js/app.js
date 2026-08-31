import Alpine from 'alpinejs';

// Alpine est désormais une dépendance autonome (Livewire retiré du projet).
window.Alpine = Alpine;

// Interactions du site public (menu mobile, galerie/lightbox, filtres).
import './public.js';

// Visio : charge le lecteur LiveKit (SDK livekit-client) uniquement sur la page
// « Rejoindre » — l'import dynamique code-splitte le SDK dans son propre chunk.
if (document.getElementById('lk-config')) {
    import('./meeting.js').then((m) => m.startFromConfig()).catch(() => {});
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
    // Assistante Gesco (widget flottant + page dédiée) : conversation côté client,
    // chaque tour appelle l'endpoint JSON /gesco/demander. Aucune action serveur
    // n'est déclenchée par l'IA — elle informe seulement.
    window.Alpine.data('gescoChat', (seed = '', endpoint = '/gesco/demander') => ({
        messages: [],
        input: '',
        sending: false,
        endpoint,
        init() {
            this.messages = [{ role: 'bot', text: seed || 'Bonjour, je suis Gesco.' }];
            this.scrollSoon();
        },
        ask(text) {
            this.input = text;
            this.send();
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
    window.Alpine.data('badgeScan', (checkedCount = 0, totalCount = 0) => ({
        found: null,
        message: '',
        messageType: '',
        recent: [],
        code: '',
        checkedCount,
        totalCount,
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
                    body: JSON.stringify({ code: text }),
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
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': this._csrf() },
                });
                const data = await res.json();
                this.found = data.found;
                this.flash(data.message, 'ok');
                if (data.newlyCheckedIn) {
                    this.checkedCount++;
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
