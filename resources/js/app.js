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

// Tableau de bord admin : charge Chart.js quand des graphiques sont présents.
// Réexécuté après une navigation Livewire (wire:navigate) — l'admin en use.
function initDashboardCharts() {
    if (document.getElementById('dashboardData')) {
        import('./charts.js').then((m) => m.startDashboardCharts()).catch(() => {});
    }
}
initDashboardCharts();
document.addEventListener('livewire:navigated', initDashboardCharts);
// Rafraîchissement à la demande (ex. page Rapports : changement d'édition).
document.addEventListener('livewire:init', () => {
    window.Livewire?.on('charts-refresh', () => setTimeout(initDashboardCharts, 50));
});

// Alpine est fourni par Livewire 4. On enregistre nos composants au démarrage.
document.addEventListener('alpine:init', () => {
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
                    links.forEach((l) => l.classList.remove('spy-active'));
                    map.get(entry.target)?.classList.add('spy-active');
                });
            }, { rootMargin: '-45% 0px -50% 0px', threshold: 0 });

            map.forEach((_, section) => this.observer.observe(section));
        },
        destroy() {
            this.observer?.disconnect();
        },
    }));

    // Champ de mots-clés « tags » (style Select2, multi) — sans dépendance.
    // Synchronise la liste vers une propriété Livewire (chaîne « a, b, c »).
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
    // Sync le HTML vers une propriété Livewire ; le bloc est en wire:ignore.
    window.Alpine.data('ukcEditor', (initial = '', wireProp = 'abstract') => ({
        count: 0,
        init() {
            this.$refs.area.innerHTML = initial || '';
            this.updateCount();
        },
        cmd(command) {
            this.$refs.area.focus();
            document.execCommand(command, false, null);
            this.sync();
        },
        sync() {
            this.$wire.set(wireProp, this.$refs.area.innerHTML, false);
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

        init() {
            this.setLayout(config.layout, config.vars);
            this._fit = () => this.fit();
            window.addEventListener('resize', this._fit);
            if (window.Livewire) {
                window.Livewire.on('layout-updated', (payload) => {
                    const p = Array.isArray(payload) ? payload[0] : payload;
                    if (p && p.layout) this.setLayout(p.layout, p.vars);
                });
            }
        },
        destroy() {
            window.removeEventListener('resize', this._fit);
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

        save() {
            this.$wire.saveLayout(JSON.parse(JSON.stringify(this.layout)));
        },
    }));
});
