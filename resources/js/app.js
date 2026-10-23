import Alpine from 'alpinejs';
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.css';
import intlTelInput from 'intl-tel-input/intlTelInputWithUtils';
import 'intl-tel-input/styles';
import PerfectScrollbar from 'perfect-scrollbar';
import 'perfect-scrollbar/css/perfect-scrollbar.css';
// Règles du formulaire « créneau », sorties du DOM pour être testées
// (`program-pure.test.js`). Y déplacer une décision, c'est la rendre vérifiable.
import {
    cadreDeLaSeance,
    communicationsChoisissables,
    fenetreHoraire,
    seanceDe,
} from './program-pure.js';

// Alpine est désormais une dépendance autonome (Livewire retiré du projet).
window.Alpine = Alpine;

// Champs téléphone internationaux (intl-tel-input) : drapeau + indicatif pays,
// saisie nationale, et normalisation en E.164 (« +228… ») à la soumission du
// formulaire - le champ reste nommé `phone`/`contact_phone`, validation serveur
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
            // Exemple de numéro (adapté au pays choisi) affiché en placeholder.
            autoPlaceholder: 'aggressive',
            placeholderNumberType: 'MOBILE',
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
        // ⚠️ À lire AVANT l'init : Tom Select force `multiple` sur l'élément
        // d'origine quand il passe en mode multi.
        const isMulti = el.multiple;
        // Le séparateur des mots-clés est propre à chaque événement. La
        // valeur persistée par Tom Select emploie un seul délimiteur ; « both »
        // accepte aussi le second lors de la saisie et du collage.
        const separator = el.getAttribute('data-keyword-separator') || 'comma';
        const delimiter = separator === 'semicolon' ? ';' : ',';
        const splitOn = separator === 'both' ? /\s*[,;]+\s*/ : separator === 'semicolon' ? /\s*;+\s*/ : /\s*,+\s*/;
        const seed = isInput ? String(el.value || '').split(splitOn).map((s) => s.trim()).filter(Boolean) : [];
        // On vide la valeur avant init : Tom Select ne ré-analyse pas (sinon des
        // tags avec espaces en double) ; on ré-injecte des tags propres ensuite.
        if (isInput) el.value = '';

        // Filtres à soumission auto : Tom Select redéclenche un « change » à
        // l'initialisation, ce qui relancerait `form.submit()` en boucle (page
        // qui scintille). On retire le onchange natif et on ne soumet que sur un
        // vrai changement utilisateur, une fois l'initialisation terminée.
        // Libellé de l'option vide (« Tous les axes »…), s'il existe.
        const emptyOption = isInput ? null : el.querySelector('option[value=""]');
        const emptyLabel = emptyOption ? (emptyOption.textContent || '').trim() : '';

        const autoSubmit = /\.form\.submit\(\)/.test(el.getAttribute('onchange') || '');
        if (autoSubmit) el.removeAttribute('onchange');
        let ready = false;

        // Filtre à choix unique doté d'une option « tout » (« Tous les axes ») :
        // cette option est un PLACEHOLDER, pas une valeur. Si on la laisse devenir
        // un élément (allowEmptyOption), elle occupe l'unique emplacement, Tom
        // Select se croit plein (isFull) et refuse d'ouvrir la liste - plus aucun
        // axe n'était sélectionnable. Un bouton « effacer » permet d'y revenir.
        const isSingleFilter = ! isInput && ! isMulti && emptyLabel !== '';

        const plugins = [];
        if (isMulti || isInput) plugins.push('remove_button');
        if (isSingleFilter) plugins.push('clear_button');
        if (el.hasAttribute('data-tom-dropdown-search')) plugins.push('dropdown_input');

        const ts = new TomSelect(el, {
            create: tags,
            delimiter: isInput ? delimiter : undefined,
            splitOn: isInput ? splitOn : undefined,
            createOnBlur: isInput,
            persist: false,
            allowEmptyOption: ! isInput && ! isSingleFilter,
            // Un <select> simple doit rester à UN choix : sans maxItems, Tom Select
            // bascule en mode multi et laisse accumuler des valeurs que le filtre
            // ne sait pas envoyer (une seule est soumise).
            maxItems: (isInput || isMulti) ? (Number.isFinite(max) && max > 0 ? max : null) : 1,
            plugins,
            // Le libellé de l'option « tout » sert de placeholder : en poser un
            // second afficherait les deux l'un sur l'autre.
            placeholder: emptyLabel || el.getAttribute('data-placeholder') || undefined,
            hidePlaceholder: true,
            // ⚠️ La liste est rendue dans <body>, pas dans le conteneur : une barre
            // de filtres qui défile (overflow) découpait le menu déroulant, qui
            // s'ouvrait sans être visible.
            dropdownParent: 'body',
            dropdownClass: el.hasAttribute('data-tom-dropdown-search') ? 'ts-dropdown ukc-track-dropdown' : 'ts-dropdown',
            render: el.hasAttribute('data-tom-dropdown-search') ? {
                item: (data, escape) => `<div title="${escape(data.text)}">${escape(data.text)}</div>`,
            } : undefined,
            onChange() {
                if (autoSubmit && ready && el.form) {
                    el.form.submit();
                }
            },
        });
        if (el.hasAttribute('data-tom-dropdown-search')) {
            ts.control_input.placeholder = 'Rechercher un axe…';
            ts.dropdown.style.position = 'fixed';
            const optionStyle = getComputedStyle(ts.dropdown_content.querySelector('.option') || ts.dropdown_content);
            const textMeasure = document.createElement('canvas').getContext('2d');
            if (textMeasure) textMeasure.font = optionStyle.font;
            const optionWidth = textMeasure
                ? Math.ceil(Math.max(0, ...Array.from(el.options, (option) => textMeasure.measureText(option.textContent.trim()).width))) + 48
                : 640;

            // La barre de filtres défile horizontalement. Avec le menu ajouté
            // au <body>, les coordonnées de Tom Select peuvent se décaler :
            // on l'ancre au contrôle dans le repère visible de la fenêtre.
            ts.positionDropdown = () => {
                if (!ts.isOpen) return;
                const rect = ts.control.getBoundingClientRect();
                const gap = 6;
                const width = Math.min(Math.max(rect.width, optionWidth), window.innerWidth - 16);
                const below = window.innerHeight - rect.bottom - gap - 8;
                const above = rect.top - gap - 8;
                const openAbove = below < Math.min(ts.dropdown.offsetHeight, 260) && above > below;
                const available = openAbove ? above : below;

                ts.dropdown_content.style.maxHeight = `${Math.max(100, Math.min(260, available - 54))}px`;
                const height = ts.dropdown.offsetHeight;
                const left = Math.max(8, Math.min(rect.left, window.innerWidth - width - 8));
                const top = openAbove ? Math.max(8, rect.top - height - gap) : rect.bottom + gap;

                Object.assign(ts.dropdown.style, {
                    top: `${top}px`, left: `${left}px`, width: `${width}px`, margin: '0',
                });
            };
            ts.on('dropdown_open', () => window.requestAnimationFrame(() => ts.positionDropdown()));
            document.addEventListener('scroll', () => ts.positionDropdown(), true);
            window.addEventListener('resize', () => ts.positionDropdown());
        }
        seed.forEach((v) => { ts.addOption({ value: v, text: v }); ts.addItem(v, true); });
        if (isInput && separator === 'both') {
            ts.control_input.addEventListener('keydown', (event) => {
                if (event.key === ';') {
                    event.preventDefault();
                    ts.createItem();
                }
            });
        }
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
window.initPhoneInputs = initPhoneInputs;

// Perfect Scrollbar (overlay qui apparaît en fondu au survol / pendant le défilement,
// comme la barre latérale de l'admin bizzcart). On l'attache à la navigation latérale
// du back-office et de l'espace participant - et, de façon extensible, à tout conteneur
// défilant portant `data-ps`. ⚠️ Pas sur la fenêtre : PS ne gère que des conteneurs à
// hauteur bornée et `overflow` masqué (il le pose lui-même) ; le défilement de page
// garde le scrollbar natif stylé (`ukc.css`). Chaque instance est mémorisée sur l'élément
// (`__ps`) pour être mise à jour quand sa taille change, et détruite proprement avant une
// ré-initialisation (ex. conteneur ré-injecté par un swap AJAX).
function initPerfectScrollbars(root = document) {
    // Un pointeur grossier (tactile) a déjà un scrollbar overlay natif et pas de survol :
    // PS y masquerait le défilement par inertie sans rien apporter. On s'abstient.
    if (window.matchMedia && window.matchMedia('(pointer: coarse)').matches) return;

    const targets = root.querySelectorAll('.ukc-sidebar-nav, [data-ps]');
    targets.forEach((el) => {
        if (el.__ps) { el.__ps.update(); return; }
        try {
            el.__ps = new PerfectScrollbar(el, {
                suppressScrollX: true,       // la nav latérale ne défile qu'en vertical
                wheelPropagation: false,
            });
            // La hauteur du conteneur ou de son contenu peut changer (menu déplié,
            // fenêtre redimensionnée) : on recalcule le rail en conséquence.
            if (window.ResizeObserver) {
                const ro = new ResizeObserver(() => el.__ps && el.__ps.update());
                ro.observe(el);
                Array.from(el.children).forEach((c) => ro.observe(c));
            }
        } catch (e) {
            /* PS indisponible : on retombe sur le scrollbar natif stylé. */
        }
    });
}
document.addEventListener('DOMContentLoaded', () => initPerfectScrollbars());
window.addEventListener('resize', () => initPerfectScrollbars());
window.initPerfectScrollbars = initPerfectScrollbars;

// Champs « nom de famille » en MAJUSCULES : dès la frappe, la valeur saisie est
// mise en capitales (accents inclus). Cible tout champ last_name (y compris les
// co-auteurs `coauthors[i][last_name]`), qu'il soit piloté par Alpine ou non.
// L'affichage majuscule est aussi assuré en CSS (repli si JS indisponible).
document.addEventListener('input', (e) => {
    const el = e.target;
    if (! (el instanceof HTMLInputElement)) return;
    const name = el.getAttribute('name') || '';
    const isLastName = el.hasAttribute('data-uppercase') || name === 'last_name' || /\[last_name\]$/.test(name);
    if (! isLastName) return;
    const up = el.value.toLocaleUpperCase('fr');
    if (up !== el.value) {
        const start = el.selectionStart;
        const end = el.selectionEnd;
        el.value = up;
        try { el.setSelectionRange(start, end); } catch (_) { /* champs sans sélection */ }
    }
});

// Barres de filtres (GET) : on retire les champs vides de l'URL. En surchargeant
// form.submit(), on couvre tous les déclencheurs - selects natifs, Tom Select et
// touche Entrée - sans modifier chaque vue. Le rechargement repart d'une page
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
// « Rejoindre » - l'import dynamique code-splitte le SDK dans son propre chunk.
if (document.getElementById('lk-config')) {
    import('./meeting.js').then((m) => m.startFromConfig()).catch(() => {});
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
// sinon en crée un - un seul empilement, cohérent dans tout le projet.
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

// Choix de la variante du badge : enregistrer la préférence et remplacer
// uniquement le rendu concerné, sans perdre la position dans « Mes documents ».
document.addEventListener('change', async (event) => {
    const toggle = event.target;
    if (! (toggle instanceof HTMLInputElement) || ! toggle.matches('[data-badge-photo-toggle]')) return;

    const form = toggle.closest('[data-badge-photo-form]');
    const entry = form?.closest('.ukc-badge-entry');
    const canvas = entry?.querySelector('.ukc-badge-canvas');
    const scaler = canvas?.querySelector('.scaler');
    const state = form?.querySelector('[data-badge-photo-state]');
    if (! form || ! canvas || ! scaler || ! state) return;

    const previous = toggle.dataset.saved === 'with';
    const requestedMode = toggle.checked ? 'with' : 'without';
    const body = new FormData(form);
    body.set('photo_mode', requestedMode);
    toggle.setAttribute('aria-checked', String(toggle.checked));
    state.textContent = toggle.checked ? 'Avec photo' : 'Sans photo';
    toggle.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body,
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (! response.ok) throw new Error(`HTTP ${response.status}`);
        const { ok, badge } = await response.json();
        if (! ok || typeof badge?.html !== 'string' || ! badge.w || ! badge.h) throw new Error('Badge invalide');

        const scale = 280 / badge.w;
        scaler.innerHTML = badge.html;
        scaler.style.width = `${badge.w}px`;
        scaler.style.height = `${badge.h}px`;
        scaler.style.transform = `scale(${scale})`;
        canvas.style.height = `${Math.round(badge.h * scale)}px`;
        toggle.dataset.saved = badge.photo_mode;
        toggle.checked = badge.photo_mode === 'with';
        toggle.setAttribute('aria-checked', String(toggle.checked));
        state.textContent = toggle.checked ? 'Avec photo' : 'Sans photo';
        ukcToast('Présentation du badge enregistrée.');
    } catch (error) {
        toggle.checked = previous;
        toggle.setAttribute('aria-checked', String(previous));
        state.textContent = previous ? 'Avec photo' : 'Sans photo';
        ukcToast('Enregistrement du choix impossible.', 'err');
    } finally {
        toggle.disabled = false;
    }
});

// Popup de confirmation (style Tailwise) - remplace window.confirm(). Renvoie une
// Promise<boolean>. Fermeture au clic sur le fond, Échap (annule) ou Entrée (confirme).
window.ukcConfirm = function (message, opts = {}) {
    const { title = 'Confirmer', confirmLabel = 'Confirmer', cancelLabel = 'Annuler', danger = false, wide = false } = opts;
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'ukc-confirm-overlay';
        const box = document.createElement('div');
        box.className = 'ukc-confirm-box';
        if (danger) box.classList.add('ukc-confirm-box--danger');
        if (wide) box.classList.add('ukc-confirm-box--wide');
        const h = document.createElement('div'); h.className = 'ukc-confirm-box__head'; h.textContent = title;
        const body = document.createElement('div'); body.className = 'ukc-confirm-box__body'; body.textContent = message;
        const foot = document.createElement('div'); foot.className = 'ukc-confirm-box__foot';
        const cancel = document.createElement('button'); cancel.type = 'button'; cancel.className = 'ukc-btn ukc-btn--ghost'; cancel.textContent = cancelLabel;
        const ok = document.createElement('button'); ok.type = 'button'; ok.className = 'ukc-btn ' + (danger ? 'ukc-btn--danger' : 'ukc-btn--blue'); ok.textContent = confirmLabel;
        foot.append(cancel, ok); box.append(h, body, foot); overlay.append(box);
        const shell = document.querySelector('.ukc-shell');
        (shell || document.body).append(overlay);
        box.setAttribute('role', 'dialog');
        box.setAttribute('aria-modal', 'true');
        h.id = 'ukc-confirm-title';
        box.setAttribute('aria-labelledby', h.id);

        const close = (val) => { overlay.remove(); document.removeEventListener('keydown', onKey); resolve(val); };
        const onKey = (e) => { if (e.key === 'Escape') close(false); else if (e.key === 'Enter') close(true); };
        overlay.addEventListener('click', (e) => { if (e.target === overlay) close(false); });
        cancel.addEventListener('click', () => close(false));
        ok.addEventListener('click', () => close(true));
        document.addEventListener('keydown', onKey);
        ok.focus();
    });
};

// Rétablit les adresses e-mail masquées par Cloudflare dans un fragment qu'on
// vient d'injecter.
//
// ⚠️ Pourquoi il le faut. Derrière Cloudflare, l'option « Email Address
// Obfuscation » (Scrape Shield) réécrit TOUTE adresse de la page en
// `<a class="__cf_email__" data-cfemail="HEX">[email protected]</a>`, et injecte
// un script qui les redéchiffre dans le navigateur. Ce script ne tourne QU'UNE
// FOIS, au chargement : un fragment remplacé en AJAX arrive masqué et le reste.
// La liste des utilisateurs montrait donc de vraies adresses au chargement, et
// « [email protected] » partout dès la première recherche. Constaté sur
// geves.univkara.tg le 11/10/2026 ; rien de tel en local, qui n'a pas de proxy.
//
// Le déchiffrement est celui de Cloudflare : le premier octet de `data-cfemail`
// est la clé, les suivants sont l'adresse passée en XOR avec elle.
function ukcCfAdresse(hex) {
    const cle = parseInt(hex.slice(0, 2), 16);
    const octets = [];
    for (let i = 2; i < hex.length; i += 2) octets.push(parseInt(hex.slice(i, i + 2), 16) ^ cle);
    return new TextDecoder().decode(new Uint8Array(octets));
}

function ukcDecodeCfEmails(racine) {
    if (! racine) return;
    racine.querySelectorAll('.__cf_email__[data-cfemail]').forEach((el) => {
        let adresse;
        try { adresse = ukcCfAdresse(el.getAttribute('data-cfemail')); } catch (e) { return; }
        if (! adresse) return;
        // ⚠️ `textContent`, jamais `innerHTML` : c'est une donnée saisie.
        el.textContent = adresse;
        el.classList.remove('__cf_email__');
        el.removeAttribute('data-cfemail');
        if (el.tagName === 'A') el.setAttribute('href', 'mailto:' + adresse);
    });
    // Lien dont seule l'adresse de destination est masquée.
    racine.querySelectorAll('a[href*="/cdn-cgi/l/email-protection#"]').forEach((a) => {
        const hex = (a.getAttribute('href') || '').split('#')[1] || '';
        if (hex) a.setAttribute('href', 'mailto:' + ukcCfAdresse(hex));
    });
}

async function ukcAjaxSubmit(form, submitter) {
    const key = form.dataset.ajaxTarget || form.closest('[data-ajax-root]')?.getAttribute('data-ajax-root');
    // IMPORTANT : construire le corps AVANT de désactiver les boutons - un bouton
    // désactivé est exclu de FormData, ce qui perdrait la valeur du bouton cliqué
    // (ex. name="status"). On passe `submitter` pour inclure ce bouton.
    const body = new FormData(form, submitter);
    const btns = form.querySelectorAll('button, [type=submit]');
    btns.forEach((b) => { b.disabled = true; });
    try {
        const res = await fetch(form.action, {
            method: 'POST',
            body,
            headers: { 'X-Requested-With': 'fetch', Accept: 'text/html' },
        });
        if (!res.ok) throw new Error('http');
        ukcToast(form.dataset.ajaxMsg || 'Action effectuée.');
        if (key) {
            // ⚠️ `fetch` suit la redirection : la réponse EST déjà la page
            // rafraîchie, messages flash compris. La relire ici évite un
            // aller-retour, et surtout ne consomme pas le flash - un second GET
            // le trouverait vide, ce qui effaçait le résultat des actions IA.
            let doc = new DOMParser().parseFromString(await res.text(), 'text/html');
            let fresh = doc.querySelector('[data-ajax-root="' + key + '"]');
            if (!fresh) {
                const html = await (await fetch(window.location.href, { headers: { 'X-Requested-With': 'fetch' } })).text();
                doc = new DOMParser().parseFromString(html, 'text/html');
                fresh = doc.querySelector('[data-ajax-root="' + key + '"]');
            }
            const cur = document.querySelector('[data-ajax-root="' + key + '"]');
            if (fresh && cur) { cur.innerHTML = fresh.innerHTML; ukcDecodeCfEmails(cur); window.Alpine?.initTree?.(cur); }
        }
    } catch (e) {
        ukcToast('Action impossible.', 'err');
    } finally {
        btns.forEach((b) => { b.disabled = false; });
        if (form.hasAttribute('data-ai') || form.__ukcAiVeil) {
            form.__ukcAiVeil = false;
            window.ukcAiVeil?.hide();
        }
    }
}

// Options du voile portées par le formulaire OU par le bouton cliqué (un même
// formulaire peut avoir un bouton « Enregistrer » et un bouton « Rédiger par IA »).
function ukcAiVeilOptions(el) {
    return {
        title: el.dataset.aiTitle || undefined,
        note: el.dataset.aiNote || undefined,
        steps: (el.dataset.aiSteps || '').split('|').map((s) => s.trim()).filter(Boolean),
    };
}

// Confirmation (popup) + soumission. `data-confirm` peut être sur le formulaire OU
// sur le bouton cliqué (messages différents par bouton). Vaut pour les formulaires
// data-ajax ET les formulaires classiques (soumission native après confirmation).
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (! (form instanceof HTMLFormElement)) return;
    const submitter = e.submitter;
    const isAjax = form.hasAttribute('data-ajax');
    const confirmText = submitter?.dataset.confirm || form.dataset.confirm || '';
    if (! isAjax && ! confirmText) return; // formulaire classique sans confirmation : soumission native

    if (form.__ukcConfirmed) { form.__ukcConfirmed = false; return; } // déjà confirmé (soumission native)

    e.preventDefault();
    const run = async () => {
        if (confirmText && ! (await window.ukcConfirm(confirmText, { danger: submitter?.classList.contains('ukc-btn--danger') }))) return;

        // ⚠️ Le voile IA se lève APRÈS la confirmation : sinon il recouvrirait la
        // popup, et resterait affiché si l'admin renonce. `form.submit()` plus bas
        // ne déclenche aucun évènement « submit », d'où ce point d'accroche ici.
        const aiEl = submitter?.hasAttribute('data-ai') ? submitter : (form.hasAttribute('data-ai') ? form : null);
        if (aiEl) {
            form.__ukcAiVeil = true;
            window.ukcAiVeil?.show(ukcAiVeilOptions(aiEl));
        }

        if (isAjax) { ukcAjaxSubmit(form, submitter); return; }
        // Formulaire classique : re-soumission native en préservant le bouton cliqué
        // (name/value) et son éventuel `formaction`/`formmethod`.
        if (submitter) {
            if (submitter.name) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden'; hidden.name = submitter.name; hidden.value = submitter.value;
                form.appendChild(hidden);
            }
            if (submitter.getAttribute('formaction')) form.action = submitter.formAction;
            if (submitter.getAttribute('formmethod')) form.method = submitter.formMethod;
        }
        form.__ukcConfirmed = true;
        form.submit();
    };
    run();
});

// ---------------------------------------------------------------------------
// Filtres de liste en AJAX (back-office).
//
// ⚠️ **Un champ de recherche obligeait à taper PUIS valider**, et rechargeait
// toute la page. Or chercher, c'est tâtonner - « aw », « awa », « awa k » - et
// chaque tâtonnement coûtait un aller-retour complet : on perdait le focus, la
// position de défilement, et l'attente rendait la recherche pénible au point
// qu'on préférait parcourir la liste à la main.
//
// Le formulaire porte `data-filter`, et chaque région qui dépend des filtres
// porte `data-filter-part="<nom>"` (tableau, pagination, barre d'export, lien
// « Réinitialiser »…). ⚠️ **Le formulaire reste HORS des régions échangées** :
// le remplacer sous les doigts de qui tape lui prendrait le focus et replacerait
// le curseur au début.
//
// ⚠️ **La valeur de `data-filter` est un PRÉFIXE** et chaque région lui
// appartient par son nom (`data-filter="payments"` → `payments-table`,
// `payments-reset`). Les onglets de Programme portent DEUX barres de filtres
// dans le même document (Sessions et Créneaux, l'une masquée) : sans
// cloisonnement, filtrer les créneaux réécrirait aussi le tableau des sessions,
// avec les filtres par défaut de celles-ci.
// ---------------------------------------------------------------------------
const UKC_FILTRE_DELAI = 350;

// L'URL que produirait une soumission GET du formulaire, les valeurs vides en
// moins : le serveur ne distingue pas « absent » de « vide », et une URL
// `?type=&state=&search=` n'est pas partageable de bonne grâce.
function ukcFiltreUrl(form) {
    const url = new URL(form.getAttribute('action') || window.location.href, window.location.href);
    const params = new URLSearchParams();
    const preserveEmpty = new Set((form.dataset.filterPreserveEmpty || '').split(/\s+/).filter(Boolean));
    new FormData(form).forEach((valeur, cle) => {
        const v = typeof valeur === 'string' ? valeur.trim() : '';
        if (v !== '' || preserveEmpty.has(cle)) params.append(cle, v);
    });
    url.search = params.toString();

    return url;
}

async function ukcFiltreApplique(form) {
    const prefixe = form.getAttribute('data-filter') || '';
    const regions = [...document.querySelectorAll('[data-filter-part]')]
        .filter((r) => ! prefixe || (r.getAttribute('data-filter-part') || '').startsWith(prefixe + '-'));
    if (! regions.length) return;

    // ⚠️ Jeton de fraîcheur : on tape plus vite que le réseau ne répond, et deux
    // requêtes peuvent revenir dans le désordre. Sans ce garde-fou, la liste
    // afficherait le résultat d'un filtre déjà abandonné - avec, dans la barre,
    // le texte qui ne lui correspond pas.
    const jeton = (form.__ukcFiltreJeton = (form.__ukcFiltreJeton || 0) + 1);
    const url = ukcFiltreUrl(form);

    regions.forEach((r) => r.classList.add('is-filtering'));
    try {
        const res = await fetch(url, { headers: { 'X-Requested-With': 'fetch', Accept: 'text/html' } });
        if (! res.ok) throw new Error('http');
        const html = await res.text();
        if (jeton !== form.__ukcFiltreJeton) return;

        const doc = new DOMParser().parseFromString(html, 'text/html');
        regions.forEach((courante) => {
            const nom = courante.getAttribute('data-filter-part');
            const fraiche = doc.querySelector('[data-filter-part="' + nom + '"]');
            if (! fraiche) return;
            courante.innerHTML = fraiche.innerHTML;
            ukcDecodeCfEmails(courante);
            window.Alpine?.initTree?.(courante);
        });

        // ⚠️ Les filtres vivent dans l'URL (convention du projet : une liste
        // filtrée se partage par lien). `replaceState` et non `pushState` : un
        // historique à une entrée par caractère frappé rendrait le bouton
        // « précédent » inutilisable.
        window.history.replaceState({}, '', url);
    } catch (e) {
        ukcToast('Filtrage impossible.', 'err');
    } finally {
        if (jeton === form.__ukcFiltreJeton) regions.forEach((r) => r.classList.remove('is-filtering'));
    }
}

// Champ de saisie : on attend une pause dans la frappe. Les autres contrôles
// (listes déroulantes, dates, cases) sont des gestes uniques : ils filtrent net.
const ukcEstSaisie = (el) => el instanceof HTMLElement
    && el.matches('input[type=search], input[type=text], input[type=number]');

document.addEventListener('input', (e) => {
    const form = e.target?.closest?.('form[data-filter]');
    if (! form || ! ukcEstSaisie(e.target)) return;
    clearTimeout(form.__ukcFiltreTimer);
    form.__ukcFiltreTimer = setTimeout(() => ukcFiltreApplique(form), UKC_FILTRE_DELAI);
});

document.addEventListener('change', (e) => {
    const form = e.target?.closest?.('form[data-filter]');
    if (! form || ukcEstSaisie(e.target)) return;
    clearTimeout(form.__ukcFiltreTimer);
    ukcFiltreApplique(form);
});

// Entrée dans le champ : le filtre est déjà appliqué (ou le sera dans l'instant).
// On coupe la soumission native, qui rechargerait la page pour rien.
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (! (form instanceof HTMLFormElement) || ! form.hasAttribute('data-filter')) return;
    e.preventDefault();
    clearTimeout(form.__ukcFiltreTimer);
    ukcFiltreApplique(form);
});

// Glisser-déposer du planning (onglet Programme > Planning) : on tire une
// communication - depuis le pool « À planifier » ou un créneau déjà placé - vers
// un créneau « libre » du board. La persistance passe par l'endpoint d'assignation
// via un formulaire data-ajax éphémère : le board et le pool sont rafraîchis d'un
// bloc (swap de data-ajax-root="prog-planning"). Desktop uniquement (le clic reste
// le repli tactile). La règle plénière est déjà garantie : un créneau libre
// n'existe que dans un atelier, qui ne peut chevaucher une plénière.
(function () {
    var drag = null; // { sub, slotId, unassignUrl } de l'élément tiré

    function post(url, submissionId, msg) {
        var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        form.setAttribute('data-ajax', '');
        form.setAttribute('data-ajax-target', 'prog-planning');
        form.setAttribute('data-ajax-msg', msg);
        form.hidden = true;
        form.innerHTML = '<input type="hidden" name="_token" value="' + token + '">'
            + '<input type="hidden" name="submission_id" value="' + submissionId + '">';
        document.body.appendChild(form);
        if (form.requestSubmit) { form.requestSubmit(); } else { form.submit(); }
        setTimeout(() => form.remove(), 0);
    }

    document.addEventListener('dragstart', (e) => {
        var src = e.target.closest('[data-sub-id][draggable="true"]');
        if (! src) return;
        drag = { sub: src.dataset.subId, slotId: src.dataset.slotId || '', unassignUrl: src.dataset.unassignUrl || '' };
        try { e.dataTransfer.setData('text/plain', drag.sub); e.dataTransfer.effectAllowed = 'move'; } catch (_) {}
        src.classList.add('dragging');
    });
    document.addEventListener('dragend', (e) => {
        var src = e.target.closest('[data-sub-id]');
        if (src) src.classList.remove('dragging');
        document.querySelectorAll('.plan-slot.free.hot, [data-pool-drop].hot').forEach((s) => s.classList.remove('hot'));
        drag = null;
    });
    document.addEventListener('dragover', (e) => {
        if (! drag) return;
        var free = e.target.closest('.plan-slot.free[data-assign-url]');
        if (free) { e.preventDefault(); try { e.dataTransfer.dropEffect = 'move'; } catch (_) {} free.classList.add('hot'); return; }
        // Déposer sur « À planifier » = déplanifier (seulement un créneau déjà placé).
        var pool = drag.slotId ? e.target.closest('[data-pool-drop]') : null;
        if (pool) { e.preventDefault(); try { e.dataTransfer.dropEffect = 'move'; } catch (_) {} pool.classList.add('hot'); }
    });
    document.addEventListener('dragleave', (e) => {
        var free = e.target.closest('.plan-slot.free[data-assign-url]');
        if (free && ! free.contains(e.relatedTarget)) free.classList.remove('hot');
        var pool = e.target.closest('[data-pool-drop]');
        if (pool && ! pool.contains(e.relatedTarget)) pool.classList.remove('hot');
    });
    document.addEventListener('drop', (e) => {
        if (! drag) return;
        var free = e.target.closest('.plan-slot.free[data-assign-url]');
        if (free) {
            e.preventDefault();
            free.classList.remove('hot');
            var sub = String(drag.sub || (e.dataTransfer && e.dataTransfer.getData('text/plain'))).replace(/[^0-9]/g, '');
            if (sub) { post(free.dataset.assignUrl, sub, 'Communication placée.'); }
            return;
        }
        var pool = drag.slotId ? e.target.closest('[data-pool-drop]') : null;
        if (pool && drag.unassignUrl) {
            e.preventDefault();
            pool.classList.remove('hot');
            post(drag.unassignUrl, '', 'Communication déplanifiée.'); // submission_id vide = libère le créneau
        }
    });
})();

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
    // Filtres client-side d'un tableau (onglet Programme > Créneaux / Sessions).
    // Générique : chaque ligne filtrable porte `data-filter-row`, un `data-id`, un
    // `data-text` (recherche libre) et un `data-<clé>` par filtre déclaré dans
    // `keys`. La ligne (un <tr> ou un <tbody>) se montre via
    // `x-show="visibleIds.includes(ID)"`. Placer le x-data sur un élément recréé au
    // swap AJAX (la carte), pas sur le data-ajax-root, pour que init() se rejoue.

    // Onglets pilotés par l'URL SANS rechargement : `?tab=` nomme l'onglet
    // ouvert, chaque clic le réécrit (pushState) et les flèches du navigateur
    // reviennent au précédent. ⚠️ À réserver aux pages dont les panneaux vivent
    // dans un même formulaire (Réglages) : ailleurs, une vraie navigation
    // serveur est préférable (cf. `?tab=` de Programme et Soumissions). Une
    // navigation perdrait ici les saisies non enregistrées des autres onglets.
    window.Alpine.data('ukcUrlTabs', (initial = '') => ({
        tab: initial,
        init() {
            // Retour/avance du navigateur : on suit l'URL, sans la réécrire.
            window.addEventListener('popstate', (e) => {
                this.tab = e.state?.ukcTab
                    || new URLSearchParams(window.location.search).get('tab')
                    || initial;
            });
        },
        go(key) {
            if (key === this.tab) return;
            this.tab = key;
            const url = new URL(window.location.href);
            url.searchParams.set('tab', key);
            window.history.pushState({ ukcTab: key }, '', url);
        },
    }));

    // Filtres de tableau côté client (instantanés) : les lignes portent
    // `data-filter-row` + `data-<clé>` + `data-text`, et se gouvernent par
    // `x-show="visibleIds.includes(ID)"`. Pour une liste qui doit aussi être
    // paginée/triée, on passe côté serveur (paginate + <x-ukc.th>), pas ici.
    window.Alpine.data('tableFilters', (keys = []) => ({
        f: Object.fromEntries([...keys, 'q'].map((k) => [k, ''])),
        rows: [],
        init() {
            this.rows = Array.from(this.$el.querySelectorAll('[data-filter-row]')).map((el) => {
                const r = { id: Number(el.dataset.id) || 0, text: el.dataset.text || '' };
                keys.forEach((k) => { r[k] = el.dataset[k] || ''; });
                return r;
            });
        },
        get active() {
            return Object.values(this.f).some((v) => v !== '');
        },
        get visibleIds() {
            const f = this.f;
            const q = (f.q || '').trim().toLowerCase();
            // Un data-<clé> peut porter PLUSIEURS valeurs séparées par « \n » (ex.
            // les axes d'un atelier) : on teste l'appartenance. Pour une valeur
            // simple, la liste n'a qu'un élément (rétrocompatible).
            return this.rows.filter((r) => keys.every((k) => !f[k] || String(r[k]).split('\n').includes(f[k]))
                && (!q || r.text.includes(q))).map((r) => r.id);
        },
        reset() {
            Object.keys(this.f).forEach((k) => { this.f[k] = ''; });
        },
    }));

    // Éditeur du « programme synthétique » (onglet Programme du module) : titre +
    // jours (date + intitulé libre) contenant des lignes (type, heures début/fin,
    // libellé, gras). État client pur ; le <form> poste les tableaux imbriqués
    // (days[i][date], days[i][rows][j][…]). C'est aussi la SOURCE de génération
    // automatique des sessions et créneaux (bouton dédié).
    window.Alpine.data('programSynthesis', (initial = {}) => ({
        title: initial.title || '',
        days: (Array.isArray(initial.days) ? initial.days : []).map((d) => ({
            date: d.date || '',
            heading: d.heading || '',
            rows: (Array.isArray(d.rows) ? d.rows : []).map((r) => ({
                start: r.start || '', end: r.end || '',
                type: r.type || 'other', label: r.label || '', highlight: !!r.highlight,
            })),
        })),
        newRow() { return { start: '', end: '', type: 'other', label: '', highlight: false }; },
        addDay() { this.days.push({ date: '', heading: '', rows: [this.newRow()] }); },
        removeDay(i) { this.days.splice(i, 1); },
        moveDay(i, dir) {
            const j = i + dir;
            if (j < 0 || j >= this.days.length) return;
            const [d] = this.days.splice(i, 1);
            this.days.splice(j, 0, d);
        },
        addRow(di) { this.days[di].rows.push(this.newRow()); },
        removeRow(di, j) { this.days[di].rows.splice(j, 1); },
        moveRow(di, j, dir) {
            const rows = this.days[di].rows;
            const k = j + dir;
            if (k < 0 || k >= rows.length) return;
            const [r] = rows.splice(j, 1);
            rows.splice(k, 0, r);
        },
    }));

    // Formulaire « créneau » du module Programme : un créneau se range DANS une
    // séance, dont il prend le jour, la salle et la fenêtre horaire, et qui dit
    // quelles communications on peut encore y placer. Les règles sont dans
    // `program-pure.js` ; ici on ne fait que les brancher sur le formulaire.
    //
    // ⚠️ Les méthodes reçoivent `form` EN ARGUMENT : il appartient à la portée
    // Alpine du module (le composant est imbriqué dedans), et `this` d'un
    // composant ne voit que ses propres données - pas celles du parent.
    window.Alpine.data('ukcSlotForm', (meta = {}) => ({
        slotMeta: meta,
        slotSeance(id) { return seanceDe(this.slotMeta, id); },
        /** Applique au formulaire ce que la séance choisie impose. */
        appliqueSeance(form) { Object.assign(form, cadreDeLaSeance(this.slotMeta, form)); },
        slotChoix(sessionId, choisie) { return communicationsChoisissables(this.slotMeta, sessionId, choisie); },
        slotFenetre(id) { return fenetreHoraire(seanceDe(this.slotMeta, id)); },
    }));

    // Comparateur de similarité : ouvre une modale montrant la soumission courante
    // et une soumission proche côte à côte, avec surlignage des mots partagés
    // (≥ 5 lettres, accents/casse ignorés). `self` = résumé de la soumission courante.
    window.Alpine.data('simCompare', (self = '') => ({
        self,
        sim: null,
        open(d) {
            const shared = this._shared(this.self, d.abstract || '');
            this.sim = Object.assign({}, d, {
                aHtml: this._mark(this.self, shared),
                bHtml: this._mark(d.abstract || '', shared),
            });
        },
        _norm(w) { return w.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); },
        _tokens(t) { return (String(t).match(/\p{L}+/gu) || []).map((w) => this._norm(w)).filter((w) => w.length >= 5); },
        _shared(a, b) {
            const A = new Set(this._tokens(a));
            return new Set(this._tokens(b).filter((x) => A.has(x)));
        },
        _esc(t) { const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; },
        _mark(text, shared) {
            return String(text).replace(/(\p{L}+)|([^\p{L}]+)/gu, (m, word, other) => {
                if (word) { const e = this._esc(word); return shared.has(this._norm(word)) ? '<mark>' + e + '</mark>' : e; }
                return this._esc(other);
            });
        },
    }));

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

    // Le WebSocket ne transporte qu'un signal. La cloche relit toujours ses
    // notifications depuis GEVES, sur la session du destinataire authentifié.
    window.Alpine.data('ukcNotificationBell', (config = {}) => ({
        open: false,
        count: Number(config.unreadCount) || 0,
        pusher: null,
        refreshTimer: null,
        refreshing: false,
        pendingRefresh: false,
        onFocus: null,
        onVisible: null,
        init() {
            this.onFocus = () => this.scheduleRefresh();
            this.onVisible = () => { if (!document.hidden) this.scheduleRefresh(); };
            window.addEventListener('focus', this.onFocus);
            document.addEventListener('visibilitychange', this.onVisible);

            const rt = config.realtime;
            if (!rt?.key || !config.userId || (!rt.host && !rt.cluster)) return;

            import('pusher-js').then(({ default: Pusher }) => {
                if (!this.$el?.isConnected) return;
                const options = {
                    cluster: rt.cluster || 'mt1',
                    forceTLS: rt.forceTLS,
                    enabledTransports: rt.forceTLS ? ['wss'] : ['ws'],
                    channelAuthorization: {
                        endpoint: '/broadcasting/auth',
                        transport: 'ajax',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                    },
                };
                if (rt.host) {
                    options.wsHost = rt.host;
                    options.wsPort = rt.port;
                    options.wssPort = rt.port;
                }

                this.pusher = new Pusher(rt.key, options);
                this.pusher.subscribe(`private-ukc.notifications.${config.userId}`)
                    .bind('notification.arrived', () => this.scheduleRefresh());
                this.scheduleRefresh();
            }).catch(() => {});
        },
        scheduleRefresh() {
            if (this.refreshTimer) clearTimeout(this.refreshTimer);
            this.refreshTimer = setTimeout(() => {
                this.refreshTimer = null;
                this.refresh();
            }, 120);
        },
        async refresh() {
            if (this.refreshing) {
                this.pendingRefresh = true;
                return;
            }
            this.refreshing = true;
            try {
                const response = await fetch(config.previewUrl, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                });
                if (!response.ok) return;
                const data = await response.json();
                this.count = Number(data.unread_count) || 0;
                this.$refs.items.innerHTML = data.items_html || '';
            } catch {
                // La cloche servie au chargement reste utilisable hors connexion.
            } finally {
                this.refreshing = false;
                if (this.pendingRefresh) {
                    this.pendingRefresh = false;
                    this.scheduleRefresh();
                }
            }
        },
        destroy() {
            if (this.refreshTimer) clearTimeout(this.refreshTimer);
            if (this.onFocus) window.removeEventListener('focus', this.onFocus);
            if (this.onVisible) document.removeEventListener('visibilitychange', this.onVisible);
            this.pusher?.disconnect();
        },
    }));

    // Gestion de projet d'un événement (kanban + liste + jalons), 100 % AJAX : un
    // seul composant porte l'état (tasks[]/milestones[]) ; tout le CRUD passe par
    // fetch et met à jour l'état en direct - aucun rechargement de page.
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
        deletingDocumentId: null,
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
            return { id: null, title: '', description: '', status, priority: 'normal', phase: '', link_key: '', assignee_id: '', collaborator_ids: [], documents: [], documentsUrl: '', start_date: '', due_date: '', checklist: [], comments: [] };
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
                // ⚠️ Copie, pas référence : modifier la liste du panneau
                // retoucherait sinon la carte du tableau derrière, y compris
                // quand on referme sans enregistrer.
                collaborator_ids: Array.isArray(t.collaborator_ids) ? t.collaborator_ids.slice() : [],
                documents: Array.isArray(t.documents) ? t.documents.slice() : [],
                documentsUrl: t.documentsUrl || '',
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

        // ---- Personnes associées ----
        // ⚠️ Elles ne remplacent PAS le responsable : une tâche garde quelqu'un
        // qui en répond, et réunit autour de lui ceux qui y travaillent. Ce sont
        // eux qui pourront écrire dans son document.
        ajouterCollaborateur(select) {
            const id = Number(select.value);
            if (id && !this.estCollaborateur(id)) this.basculeCollaborateur(id);
            select.value = '';
        },
        basculeCollaborateur(id) {
            const n = Number(id);
            const liste = (this.task.collaborator_ids = this.task.collaborator_ids || []);
            const i = liste.indexOf(n);
            if (i >= 0) liste.splice(i, 1);
            else liste.push(n);
        },
        estCollaborateur(id) {
            return (this.task.collaborator_ids || []).includes(Number(id));
        },
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
                    collaborator_ids: this.task.collaborator_ids || [],
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

        async deleteDocument(d) {
            if (this.deletingDocumentId !== null) return;
            const confirmed = await window.ukcConfirm(
                `Supprimer « ${d.title} » et toutes ses versions ? Cette action est définitive.`,
                { title: 'Supprimer le document', confirmLabel: 'Supprimer', danger: true, wide: true },
            );
            if (!confirmed) return;

            this.deletingDocumentId = d.id;
            try {
                const response = await fetch(d.deleteUrl, { method: 'DELETE', headers: this._headers() });
                if (!response.ok) {
                    this.notify('Suppression impossible. Réessayez ou contactez l’administrateur.', 'err');
                    return;
                }
                this.task.documents = (this.task.documents || []).filter((item) => item.id !== d.id);
                const task = this.tasks.find((item) => item.id === this.task.id);
                if (task) task.documents = (task.documents || []).filter((item) => item.id !== d.id);
                this.notify('Document supprimé.');
            } catch (error) {
                this.notify('Connexion interrompue. Réessayez.', 'err');
            } finally {
                this.deletingDocumentId = null;
            }
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
    // n'est déclenchée par l'IA - elle informe seulement.
    // ⚠️ Replis NEUTRES : le nom de l'assistante se règle dans Réglages, et les
    // deux appelants (widget, page dédiée) le transmettent. Un prénom écrit en
    // dur ici ressortirait le jour où l'un d'eux oublierait de le passer.
    window.Alpine.data('gescoChat', (seed = '', endpoint = '/gesco/demander', name = 'Assistante', scope = 'guest') => ({
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
        // Clé de l'identifiant de conversation : se termine par « .<scope> » pour
        // que purgeForeignScopes la conserve pour l'utilisateur courant et la purge
        // pour les autres (même règle que la discussion).
        get cidKey() { return this.storagePrefix + 'cid.' + this.scope; },
        conversationId: '',
        seedText: seed || 'Bonjour !',
        init() {
            this.purgeForeignScopes();
            const saved = this.restore();
            this.messages = saved || [{ role: 'bot', text: this.seedText }];
            this.conversationId = this.restoreCid() || this.freshConversation();
            this.$watch('messages', () => this.persist());
            this.scrollSoon();
        },
        // Un identifiant par discussion : une nouvelle conversation = une éventuelle
        // nouvelle réclamation ; les tours suivants mettent à jour la même.
        freshConversation() {
            let id;
            try { id = crypto.randomUUID(); } catch (e) { id = 'c-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10); }
            this.conversationId = id;
            try { sessionStorage.setItem(this.cidKey, id); } catch (e) { /* stockage indisponible */ }
            return id;
        },
        restoreCid() {
            try { return sessionStorage.getItem(this.cidKey) || ''; } catch (e) { return ''; }
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
            this.freshConversation(); // nouvelle discussion = nouvelle réclamation possible
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
                    body: JSON.stringify({ question, history, conversation_id: this.conversationId }),
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

    // Copilote d'administration (page Assistant IA - mockup admin-ai-assistant).
    // Chat réel (AskAdmin) + propositions à décider + journal des décisions persisté.
    window.Alpine.data('adminCopilot', (seed = '', askUrl = '', executeUrl = '', log = [], name = 'Copilote', editionId = '', scope = '0') => ({
        messages: [],
        input: '',
        sending: false,
        agentName: name,
        editionId: editionId ? String(editionId) : '',
        decisionLog: Array.isArray(log) ? log : [],
        copiedIndex: null,
        showGrow: false,
        // Discussion conservée pendant la session, cloisonnée par événement ET par
        // utilisateur (un autre membre du staff ne voit jamais la discussion d'un autre).
        storagePrefix: 'gesco.copilot.v1.',
        scope: String(scope || '0'),
        get storageKey() { return this.storagePrefix + (this.editionId || 'x') + '.' + this.scope; },
        seedText: seed,
        init() {
            this.purgeForeignScopes();
            const saved = this.restore();
            this.messages = saved || [{ role: 'bot', text: this.seedText }];
            this.$watch('messages', () => this.persist());
            this.scrollSoon();
        },
        // Retire les discussions d'un autre utilisateur (changement de compte), tous événements confondues.
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
            this.messages.push({ role: 'user', text: question });
            this.input = '';
            this.resetInputHeight();
            this.sending = true;
            this.scrollSoon();

            try {
                const res = await fetch(askUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                    body: JSON.stringify({ question, history, event_id: this.editionId || null }),
                });
                const data = await res.json().catch(() => ({}));
                this.messages.push({
                    role: 'bot',
                    text: data.answer || 'Une erreur est survenue. Réessayez dans un instant.',
                    action: data.action || null,
                    actionState: null,
                });
            } catch (e) {
                this.messages.push({ role: 'bot', text: 'Connexion impossible. Vérifiez votre réseau et réessayez.', action: null });
            } finally {
                this.sending = false;
                this.scrollSoon();
            }
        },
        // Exécution d'une action APRÈS autorisation explicite de l'utilisateur : le
        // serveur exécute (et revérifie la capacité), le résultat s'affiche dans le chat.
        async runAction(index) {
            const m = this.messages[index];
            if (!m || !m.action || m.actionState === 'running' || m.actionState === 'done') return;
            if (m.action.key === 'delete_submission') {
                const confirmed = await window.ukcConfirm(m.action.summary || 'Supprimer ce résumé ?', {
                    title: 'Supprimer le résumé', confirmLabel: 'Supprimer', danger: true, wide: true,
                });
                if (!confirmed) return;
            }
            m.actionState = 'running';
            try {
                const res = await fetch(executeUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                    body: JSON.stringify({ key: m.action.key, params: m.action.params || {}, event_id: this.editionId || null }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    m.actionState = 'error';
                    this.messages.push({ role: 'bot', text: data.message || 'L’action a échoué.', action: null });
                } else {
                    m.actionState = 'done';
                    if (data.entry) this.decisionLog.unshift(data.entry);
                    this.messages.push({ role: 'bot', text: '✅ ' + (data.message || 'Action exécutée.'), action: null });
                }
            } catch (e) {
                m.actionState = 'error';
                this.messages.push({ role: 'bot', text: 'Connexion impossible. Réessayez.', action: null });
            } finally {
                this.scrollSoon();
            }
        },
        dismissAction(index) {
            const m = this.messages[index];
            if (m && m.action) m.actionState = 'dismissed';
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
        presentationMode: initial.presentationMode || '',
        coauthors: (Array.isArray(initial.coauthors) ? initial.coauthors : [])
            .map((c) => Object.assign({ last_name: '', first_name: '', email: '', phone: '', affiliation: '', grade: '', hint: '', hintType: 'muted' }, c)),
        abstractHtml: '',
        abstractWordCount: 0,
        keywords: [],
        rules: initial.rules || {},
        lookupUrl: initial.lookupUrl || '',
        suggestUrl: initial.suggestUrl || '',
        coauthorsMax: initial.coauthorsMax || 8,
        _affLast: '',
        account: initial.account || { name: '', first_name: '', last_name: '', email: '', phone: '', affiliation: '', grade: '' },
        corr: Object.assign({ self: true, first_name: '', last_name: '', email: '', phone: '', affiliation: '', grade: '', hint: '', hintType: 'muted' }, initial.corr || {}),

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
                    const separator = kw.getAttribute('data-keyword-separator') || 'comma';
                    const splitOn = separator === 'both' ? /[;,]/ : separator === 'semicolon' ? /;/ : /,/;
                    this.keywords = kw.tomselect ? [...kw.tomselect.items] : String(kw.value || '').split(splitOn).map((s) => s.trim()).filter(Boolean);
                };
                kw.addEventListener('change', readKw);
                readKw();
            }
        },

        addCoauthor() {
            if (this.coauthors.length >= this.coauthorsMax) {
                window.ukcToast?.(`Nombre maximum de co-auteurs atteint (${this.coauthorsMax}).`, 'err');
                return;
            }
            this.coauthors.push({ last_name: '', first_name: '', email: '', phone: '', affiliation: '', grade: '', hint: '', hintType: 'muted' });
        },
        coauthorName(co) {
            return (((co.first_name || '') + ' ' + (co.last_name || '')).trim());
        },
        removeCoauthor(i) {
            this.coauthors.splice(i, 1);
        },

        // --- Vérification discrète d'existence d'un compte (AJAX) --------------
        //     Guide le déposant sans révéler l'identité d'autrui : indice fondé
        //     sur l'e-mail qu'il a saisi + suggestion de mise en forme du nom.
        async _authorLookup(name, email) {
            if (! this.lookupUrl || (! (name || '').trim() && ! (email || '').trim())) return null;
            try {
                const res = await fetch(this.lookupUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ name: name || '', email: email || '' }),
                });
                return res.ok ? await res.json() : null;
            } catch (e) {
                return null;
            }
        },
        // Pré-remplit les champs VIDES d'un auteur à partir d'un compte trouvé
        // (l'e-mail a été fourni par le déposant). N'écrase jamais une saisie.
        _fillFrom(target, account) {
            if (! account) return;
            if ('name' in target && ! (target.name || '').trim() && account.name) target.name = account.name;
            if ('first_name' in target && ! (target.first_name || '').trim() && account.first_name) target.first_name = account.first_name;
            if ('last_name' in target && ! (target.last_name || '').trim() && account.last_name) target.last_name = account.last_name;
            if (! (target.affiliation || '').trim() && account.affiliation) target.affiliation = account.affiliation;
            if (! (target.grade || '').trim() && account.grade) target.grade = account.grade;
        },
        corrName() {
            return (((this.corr.first_name || '') + ' ' + (this.corr.last_name || '')).trim());
        },
        async lookupCorr() {
            if (this.corr.self) { this.corr.hint = ''; return; }
            const email = (this.corr.email || '').trim().toLowerCase();

            // Le déposant a saisi SON propre e-mail alors qu'il déclare ne pas être
            // l'auteur correspondant → l'inviter à (ré)activer l'interrupteur.
            if (email && email === (this.account.email || '').trim().toLowerCase()) {
                this.corr.hint = 'C’est votre e-mail : activez plutôt « Je suis l’auteur correspondant ».';
                this.corr.hintType = 'err';
                return;
            }
            // Doublon avec un co-auteur déjà saisi.
            if (email && this.coauthors.some((c) => (c.email || '').trim().toLowerCase() === email)) {
                this.corr.hint = 'Cet e-mail est déjà celui d’un co-auteur : chaque auteur doit être unique.';
                this.corr.hintType = 'err';
                return;
            }
            // Saisie live : on attend une adresse complète avant de proposer.
            if (email && ! /^\S+@\S+\.\S+$/.test(email)) { this.corr.hint = ''; return; }

            const d = await this._authorLookup(this.corrName(), this.corr.email);
            if (d) {
                this.corr.hint = d.hint || '';
                this.corr.hintType = d.type || 'muted';
                this._fillFrom(this.corr, d.account);
            }
        },
        async lookupCoauthor(i) {
            const co = this.coauthors[i];
            if (! co) return;
            const email = (co.email || '').trim().toLowerCase();

            // Doublon détecté DÈS la saisie (prioritaire sur l'indice « compte trouvé »).
            const corrEmail = ((this.corr.self ? this.account.email : this.corr.email) || '').trim().toLowerCase();
            if (email && email === corrEmail) {
                co.hint = 'Cette personne est déjà l’auteur correspondant : retirez cette ligne.';
                co.hintType = 'err';
                co.suggestion = '';
                return;
            }
            if (email && this.coauthors.some((c, j) => j !== i && (c.email || '').trim().toLowerCase() === email)) {
                co.hint = 'Même e-mail qu’un autre co-auteur : chaque auteur doit être unique.';
                co.hintType = 'err';
                co.suggestion = '';
                return;
            }
            // Saisie live : tant que l'e-mail n'est pas complet, on n'affiche rien
            // (on attend une adresse valide pour proposer une correspondance).
            if (email && ! /^\S+@\S+\.\S+$/.test(email)) { co.hint = ''; return; }

            const d = await this._authorLookup(this.coauthorName(co), co.email);
            if (d) {
                co.hint = d.hint || '';
                co.hintType = d.type || 'muted';
                this._fillFrom(co, d.account);
            }
        },

        // Autocomplétion des affiliations via l'API ROR (référentiel mondial des
        // organisations de recherche) : alimente le <datalist> partagé en direct.
        async affiliationSuggest(q) {
            q = (q || '').trim();
            if (q.length < 3 || ! this.suggestUrl || q === this._affLast) return;
            this._affLast = q;
            try {
                const res = await fetch(this.suggestUrl + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } });
                if (! res.ok) return;
                const data = await res.json();
                const dl = document.getElementById('ukc-affiliations');
                if (! dl) return;
                dl.replaceChildren(...(data.items || []).map((v) => {
                    const o = document.createElement('option');
                    o.value = v;
                    return o;
                }));
            } catch (e) { /* réseau : saisie libre préservée */ }
        },

        // Interrupteur « Je suis l'auteur correspondant » :
        // - activé  → rétablit l'identité (nom + e-mail + profil) du compte connecté ;
        // - désactivé → vide les champs pour saisir une AUTRE personne (évite de
        //   laisser l'identité du déposant préremplie, source de confusion/doublon).
        onSelfToggle() {
            this.corr.hint = '';
            this.corr.hintType = 'muted';
            const a = this.corr.self ? this.account : { first_name: '', last_name: '', email: '', phone: '', affiliation: '', grade: '' };
            this.corr.first_name = a.first_name || '';
            this.corr.last_name = a.last_name || '';
            this.corr.email = a.email || '';
            this.corr.affiliation = a.affiliation || '';
            this.corr.grade = a.grade || '';
            // Le champ téléphone est piloté par intl-tel-input (pas de x-model) :
            // on met sa valeur à jour impérativement.
            const ph = document.getElementById('corr-phone');
            if (ph) { ph.value = a.phone || ''; ph.dispatchEvent(new Event('input')); }
        },
        corrInitials() {
            const parts = this.corrName().split(/\s+/).filter(Boolean);
            if (parts.length === 0) return '?';
            return ((parts[0]?.[0] || '') + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase();
        },
        corrMeta() {
            return [this.corr.affiliation, this.corr.grade].filter((v) => (v || '').trim()).join(' - ')
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
                if (!this.presentationMode) return this._fail('Veuillez sélectionner un mode de présentation.');
                return this._ok();
            }
            if (n === 2) {
                // Auteur correspondant : identité requise (surtout si ce n'est pas le
                // compte connecté). Quand « self », l'identité vient du compte.
                if (!(this.corr.last_name || '').trim() || !(this.corr.first_name || '').trim()) return this._fail('Indiquez le nom et le prénom de l’auteur correspondant.');
                if (!/^\S+@\S+\.\S+$/.test((this.corr.email || '').trim())) return this._fail('L’auteur correspondant doit avoir un e-mail valide.');

                // E-mail de l'auteur correspondant (compte connecté si « self »).
                const corrEmail = ((this.corr.self ? this.account.email : this.corr.email) || '').trim().toLowerCase();
                const seen = new Set();
                for (const co of this.coauthors) {
                    const any = ['last_name', 'first_name', 'email', 'phone', 'affiliation', 'grade'].some((k) => (co[k] || '').trim());
                    if (!any) continue; // ligne vide : ignorée à l'enregistrement
                    if (!(co.last_name || '').trim() || !(co.first_name || '').trim()) return this._fail('Indiquez le nom et le prénom de chaque co-auteur (ou retirez la ligne).');
                    const email = (co.email || '').trim().toLowerCase();
                    if (!/^\S+@\S+\.\S+$/.test(email)) return this._fail('Chaque co-auteur doit avoir un e-mail valide.');
                    if (!(co.affiliation || '').trim()) return this._fail('Indiquez l’affiliation de chaque co-auteur.');
                    if (email === corrEmail) return this._fail('Un co-auteur ne peut pas être aussi l’auteur correspondant (même e-mail).');
                    if (seen.has(email)) return this._fail('Deux co-auteurs ont le même e-mail : chaque auteur doit être unique.');
                    seen.add(email);
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
            return this.coauthors.filter((c) => this.coauthorName(c) || (c.email || '').trim()).length;
        },
        coauthorList() {
            return this.coauthors
                .filter((c) => this.coauthorName(c) || (c.email || '').trim())
                .map((c) => ({ name: this.coauthorName(c) || c.email, affiliation: c.affiliation }));
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
    window.Alpine.data('agentsEditor', (agents = [], gallery = []) => ({
        // `upload` = aperçu local (objectURL) d'une image choisie mais pas encore
        // envoyée ; il n'est pas posté (aucun champ ne le porte), seul le fichier l'est.
        agents: (Array.isArray(agents) ? agents : []).map((a) => ({ upload: '', ...a })),
        // Galerie d'avatars fournie (public/img/agents) : [{ path, url, group }].
        gallery: Array.isArray(gallery) ? gallery : [],
        active: 0,
        addAgent() {
            this.agents.push({
                key: 'agent-' + Math.random().toString(36).slice(2, 6),
                name: 'Nouvel agent', audience: 'participants', enabled: true,
                avatar: '', upload: '', persona: '', positive: '', negative: '',
            });
            this.active = this.agents.length - 1;
        },
        removeAgent(i) {
            this.agents.splice(i, 1);
            this.active = Math.max(0, Math.min(this.active, this.agents.length - 1));
        },
        // Choisit un avatar de la galerie pour l'agent courant (re-cliquer = retirer).
        // Un choix de galerie annule un fichier en attente (et vide l'input fichier).
        pickAvatar(i, path) {
            this.agents[i].avatar = this.agents[i].avatar === path ? '' : path;
            this.clearUpload(i);
        },
        // Fichier choisi via la tuile « + » : aperçu local, et la nouvelle image
        // remplace la sélection de galerie (c'est le fichier qui sera enregistré).
        onAvatarFile(i, event) {
            const file = event.target.files && event.target.files[0];
            if (!file) return;
            this.agents[i].avatar = '';
            if (this.agents[i].upload) { try { URL.revokeObjectURL(this.agents[i].upload); } catch (e) { /* noop */ } }
            this.agents[i].upload = URL.createObjectURL(file);
        },
        clearUpload(i) {
            if (this.agents[i].upload) { try { URL.revokeObjectURL(this.agents[i].upload); } catch (e) { /* noop */ } }
            this.agents[i].upload = '';
            const input = document.getElementById('agent-file-' + i);
            if (input) input.value = '';
        },
        // Ce qu'affiche l'aperçu : le fichier en attente, sinon l'avatar enregistré.
        avatarPreview(agent) {
            return agent.upload ? agent.upload : this.avatarUrl(agent.avatar);
        },
        // URL servable d'un chemin d'avatar (galerie `img/…` ou ancien téléversement).
        avatarUrl(avatar) {
            if (!avatar) return '';
            if (/^https?:\/\//.test(avatar)) return avatar;
            return avatar.startsWith('img/') ? '/' + avatar : '/storage/' + avatar;
        },
    }));

    // Listes répétables du formulaire d'événement (admin) : tarifs, comités,
    // partenaires, FAQ - ajout/suppression côté client.
    // ⚠️ Un répéteur qui se RÉORDONNE ne peut pas être repéré par sa position :
    // Alpine recycle les nœuds à clé égale, et le fichier déjà choisi dans un
    // champ de téléversement resterait en place pendant que la ligne, elle, se
    // déplacerait - on enregistrerait le logo d'un partenaire chez son voisin.
    // Chaque ligne porte donc un identifiant qui la suit.
    let partnerSeq = 0;
    const partnerRow = (p = {}) => ({
        uid: 'p' + (++partnerSeq),
        name: p.name || '',
        url: p.url || '',
        logo_url: p.logo_url || '',
        level: p.level || '',
        // Aperçu : l'URL enregistrée, ou l'image choisie tant qu'elle n'est pas envoyée.
        preview: p.logo_url || '',
        logo_ko: false,
    });

    const editionArraysFactory = (init = {}) => ({
        fees: Array.isArray(init.fees) ? init.fees : [],
        committees: Array.isArray(init.committees) ? init.committees : [],
        partners: (Array.isArray(init.partners) ? init.partners : []).map((p) => partnerRow(p)),
        faq: Array.isArray(init.faq) ? init.faq : [],
        keyDates: Array.isArray(init.keyDates) ? init.keyDates : [],
        programme: Array.isArray(init.programme) ? init.programme : [],
        axes: Array.isArray(init.axes) ? init.axes : [],
        why: Array.isArray(init.why) ? init.why : [],
        infoCards: Array.isArray(init.infoCards) ? init.infoCards : [],
        stats: Array.isArray(init.stats) ? init.stats : [],
        refAxes: Array.isArray(init.refAxes) ? init.refAxes : [],
        addFee() { this.fees.push({ label: '', amount: '', grades: [], default: false }); },
        removeFee(i) { this.fees.splice(i, 1); },
        // Grades couverts par une catégorie tarifaire : c'est ce rattachement
        // explicite qui permet de retrouver le tarif d'un participant, l'intitulé
        // de la catégorie étant rédigé librement.
        toggleFeeGrade(i, grade, on) {
            const fee = this.fees[i];
            if (!fee) return;
            if (!Array.isArray(fee.grades)) fee.grades = [];
            const at = fee.grades.indexOf(grade);
            if (on && at === -1) fee.grades.push(grade);
            if (!on && at !== -1) fee.grades.splice(at, 1);
        },
        addCommittee() { this.committees.push({ role: 'organisation', person_name: '', person_title: '', institution: '', photo_url: '' }); },
        removeCommittee(i) { this.committees.splice(i, 1); },
        addPartner() { this.partners.push(partnerRow()); },
        removePartner(i) { this.partners.splice(i, 1); },
        setPartners(list) { this.partners = (Array.isArray(list) ? list : []).map((p) => partnerRow(p)); },
        // L'ordre de la liste EST celui du bandeau public : le déplacement est donc
        // un réglage, pas un confort de saisie.
        movePartner(i, dir) {
            const to = i + dir;
            if (to < 0 || to >= this.partners.length) return;
            const [row] = this.partners.splice(i, 1);
            this.partners.splice(to, 0, row);
        },
        // Téléversement : l'aperçu passe à l'image locale sans attendre l'envoi.
        pickPartnerLogo(p, e) {
            const file = e.target.files && e.target.files[0];
            if (!file) return;
            p.preview = URL.createObjectURL(file);
            p.logo_ko = false;
        },
        // ⚠️ Saisir une URL ANNULE le fichier choisi : à l'enregistrement, c'est le
        // fichier qui l'emporte, et l'aperçu montrerait sinon une image que la
        // fiche n'allait pas retenir.
        typePartnerLogo(p, value, input) {
            p.logo_url = value;
            p.preview = value;
            p.logo_ko = false;
            if (input) input.value = '';
        },
        clearPartnerLogo(p, input) {
            p.logo_url = '';
            p.preview = '';
            p.logo_ko = false;
            if (input) input.value = '';
        },
        addFaq() { this.faq.push({ question: '', answer: '' }); },
        removeFaq(i) { this.faq.splice(i, 1); },
        addKeyDate() { this.keyDates.push({ date: '', label: '' }); },
        removeKeyDate(i) { this.keyDates.splice(i, 1); },
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
        previewUrl: '',
        pick(e) { this.set(e.target.files && e.target.files[0]); },
        drop(e) {
            this.over = false;
            const dt = e.dataTransfer;
            if (dt && dt.files && dt.files[0]) {
                this.$refs.input.files = dt.files;
                this.set(dt.files[0]);
            }
        },
        set(f) {
            this.revoke();
            this.file = f || null;
            this.size = f ? this.fmt(f.size) : '';
            this.previewUrl = (f && f.type && f.type.indexOf('image/') === 0) ? URL.createObjectURL(f) : '';
        },
        clear() { this.revoke(); this.file = null; this.size = ''; this.previewUrl = ''; this.$refs.input.value = ''; },
        revoke() { if (this.previewUrl && this.previewUrl.indexOf('blob:') === 0) URL.revokeObjectURL(this.previewUrl); },
        fmt(b) { return b > 1048576 ? (b / 1048576).toFixed(1) + ' Mo' : Math.max(1, Math.round(b / 1024)) + ' Ko'; },
    }));

    // Ligne « formateur » (ajout ou événement) : rapprochement à la saisie d'un
    // identifiant fort (e-mail / téléphone) avec un compte existant, comme pour les
    // co-auteurs. Quand un compte correspond, on pré-remplit les autres champs et on
    // verrouille le nom (l'identité vient du compte ; le serveur la force de toute
    // façon), ce qui évite les fautes de nom et l'usage de l'e-mail d'un tiers.
    window.Alpine.data('trainerLookup', (cfg = {}) => ({
        lookupUrl: cfg.lookupUrl || '',
        linked: !!cfg.linked,
        hint: cfg.linked ? 'Compte associé.' : '',
        hintType: cfg.linked ? 'ok' : '',
        busy: false,
        async lookup() {
            // Le composant peut être monté sur le <form> lui-même ou sur un wrapper.
            const form = this.$root.closest('form') || this.$root.querySelector('form') || this.$root;
            const email = (form.querySelector('[name="email"]')?.value || '').trim();
            const phone = (form.querySelector('[name="phone"]')?.value || '').trim();
            if (!email && !phone) { this.linked = false; this.hint = ''; this.hintType = ''; return; }
            this.busy = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.content
                    || form.querySelector('input[name="_token"]')?.value || '';
                const r = await fetch(this.lookupUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                    body: JSON.stringify({ email, phone }),
                });
                const d = await r.json();
                if (d.found) {
                    this.linked = true;
                    this.hintType = 'ok';
                    this.hint = 'Compte trouvé : ' + d.name + '.';
                    const set = (n, v) => { const el = form.querySelector('[name="' + n + '"]'); if (el && v) el.value = v; };
                    set('name', d.name);
                    set('email', d.email);
                    set('phone', d.phone);
                    // Grade et spécialité : seulement s'ils sont encore vides
                    // (ils appartiennent à la formation, pas au profil).
                    const fill = (n, v) => { const el = form.querySelector('[name="' + n + '"]'); if (el && v && !el.value.trim()) el.value = v; };
                    fill('title', d.title);
                    fill('specialty', d.specialty);
                } else {
                    this.linked = false;
                    this.hintType = 'info';
                    this.hint = 'Aucun compte : le formateur sera saisi librement.';
                }
            } catch (e) {
                this.linked = false;
                this.hintType = '';
                this.hint = '';
            }
            this.busy = false;
        },
    }));

    // Formateur d'une formation : upload de photo en AJAX (barre de progression sur
    // l'avatar) + bascule d'événement en ligne des informations.
    window.Alpine.data('formationTrainer', (cfg = {}) => ({
        photo: cfg.photo || '',
        uploadUrl: cfg.uploadUrl || '',
        updateUrl: cfg.updateUrl || '',
        displayName: cfg.displayName || '',
        sub: cfg.sub || '',
        moderator: !!cfg.moderator,
        signatureUrl: cfg.signatureUrl || '',
        editing: false,
        uploading: false,
        saving: false,
        signing: false,
        progress: 0,
        error: '',
        // Signature du formateur (apposée sur les attestations de formation).
        uploadSignature(e) {
            const file = e.target.files && e.target.files[0];
            if (!file) return;
            this.error = '';
            if (!/^image\/(png|jpe?g)$/.test(file.type)) { this.error = 'Signature : image PNG ou JPG uniquement.'; e.target.value = ''; return; }
            if (file.size > 4 * 1024 * 1024) { this.error = 'Signature trop lourde (max 4 Mo).'; e.target.value = ''; return; }
            const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const fd = new FormData();
            fd.append('signature', file);
            fd.append('_token', token);
            this.signing = true;
            fetch(this.signatureUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                body: fd,
            }).then((r) => r.json().then((d) => ({ status: r.status, d })))
                .then(({ status, d }) => {
                    this.signing = false;
                    e.target.value = '';
                    if (status >= 200 && status < 300 && d.ok) {
                        // Rechargement : la vignette est rendue côté serveur.
                        window.location.reload();
                    } else {
                        this.error = status === 422 ? 'Signature refusée (image PNG/JPG, max 4 Mo).' : 'Échec du téléversement.';
                    }
                }).catch(() => { this.signing = false; e.target.value = ''; this.error = 'Erreur réseau.'; });
        },
        saveInfo(e) {
            const form = e.target;
            if (!form.reportValidity()) return;
            this.error = '';
            this.saving = true;
            const token = document.querySelector('meta[name="csrf-token"]')?.content
                || form.querySelector('input[name="_token"]')?.value || '';
            fetch(this.updateUrl, {
                method: 'POST', // _method=PUT dans le FormData (spoofing Laravel)
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                body: new FormData(form),
            }).then((r) => r.json().then((d) => ({ status: r.status, d })))
                .then(({ status, d }) => {
                    this.saving = false;
                    if (status >= 200 && status < 300 && d.ok) {
                        this.displayName = d.display_name;
                        this.sub = d.sub;
                        this.moderator = !!d.is_moderator;
                        this.editing = false;
                        if (window.ukcToast) window.ukcToast('Formateur mis à jour.', 'ok');
                    } else if (status === 422) {
                        const errs = d.errors || {};
                        this.error = (Object.values(errs)[0] || ['Champs invalides.'])[0];
                    } else {
                        this.error = 'Échec de l’enregistrement.';
                    }
                }).catch(() => { this.saving = false; this.error = 'Erreur réseau.'; });
        },
        uploadPhoto(e) {
            const file = e.target.files && e.target.files[0];
            if (!file) return;
            this.error = '';
            // Contrôle d'anomalie côté client (type + taille) avant l'envoi.
            if (!/^image\/(png|jpe?g)$/.test(file.type)) {
                this.error = 'Image PNG ou JPG uniquement.';
                e.target.value = '';
                return;
            }
            if (file.size > 4 * 1024 * 1024) {
                this.error = 'Image trop lourde (max 4 Mo).';
                e.target.value = '';
                return;
            }
            const input = e.target;
            const token = document.querySelector('meta[name="csrf-token"]')?.content
                || document.querySelector('input[name="_token"]')?.value || '';
            const fd = new FormData();
            fd.append('photo', file);
            fd.append('_token', token);
            this.uploading = true;
            this.progress = 0;
            const xhr = new XMLHttpRequest();
            xhr.open('POST', this.uploadUrl);
            xhr.setRequestHeader('X-CSRF-TOKEN', token);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.upload.addEventListener('progress', (ev) => {
                if (ev.lengthComputable) this.progress = Math.round((ev.loaded / ev.total) * 100);
            });
            xhr.addEventListener('load', () => {
                this.uploading = false;
                input.value = '';
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const d = JSON.parse(xhr.responseText);
                        if (d.ok && d.url) { this.photo = d.url + (d.url.indexOf('?') === -1 ? '?' : '&') + 't=' + Date.now(); return; }
                    } catch (err) { /* ignore */ }
                    this.error = 'Réponse inattendue du serveur.';
                } else if (xhr.status === 422) {
                    this.error = 'Fichier refusé (image PNG/JPG, max 4 Mo).';
                } else {
                    this.error = 'Échec du téléversement (' + xhr.status + ').';
                }
            });
            xhr.addEventListener('error', () => { this.uploading = false; input.value = ''; this.error = 'Erreur réseau.'; });
            xhr.send(fd);
        },
    }));

    // Palette de commandes « Recherche rapide » (⌘K / Ctrl+K) - style Tailwise.
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
    // Éditeur du formulaire de soumission : état 100 % Alpine, enregistrement
    // AUTOMATIQUE (réordonnancement, ajout, événement, suppression d'un champ). Plus
    // de bouton « Enregistrer » : chaque changement est persité en AJAX. L'événement
    // d'un champ se fait dans un panneau latéral (comme « Nouvelle tâche » du projet).
    window.Alpine.data('formEditor', (cfg = {}) => ({
        standard: Array.isArray(cfg.standard) ? cfg.standard : [],
        custom: Array.isArray(cfg.custom) ? cfg.custom : [],
        saveUrl: cfg.saveUrl || '',
        csrf: cfg.csrf || '',
        saving: false,
        // Panneau d'événement
        editOpen: false,
        editKind: 'custom', // 'standard' | 'custom'
        editIndex: null,
        creating: false,
        draft: {},

        openEditStandard(i) {
            this.editKind = 'standard';
            this.editIndex = i;
            this.creating = false;
            this.draft = { ...this.standard[i] };
            this.editOpen = true;
        },
        openEditCustom(i) {
            this.editKind = 'custom';
            this.editIndex = i;
            this.creating = false;
            this.draft = { ...this.custom[i] };
            this.editOpen = true;
        },
        add(type = 'text') {
            this.editKind = 'custom';
            this.editIndex = null;
            this.creating = true;
            this.draft = {
                key: 'custom_' + Math.random().toString(36).slice(2, 8),
                label: '', type: type || 'text', required: false, help: '', options_text: '',
            };
            this.editOpen = true;
        },
        saveEdit() {
            if (!this.draft.label || !this.draft.label.trim()) {
                window.ukcToast && window.ukcToast('Le libellé est obligatoire.', 'err');
                return;
            }
            if (this.editKind === 'standard') {
                Object.assign(this.standard[this.editIndex], this.draft);
            } else if (this.creating) {
                this.custom.push({ ...this.draft });
            } else {
                Object.assign(this.custom[this.editIndex], this.draft);
            }
            this.editOpen = false;
            this.persist();
        },
        deleteField() {
            if (this.editKind !== 'custom' || this.creating) return;
            this.custom.splice(this.editIndex, 1);
            this.editOpen = false;
            this.persist();
        },
        move(i, dir) {
            const j = i + dir;
            if (j < 0 || j >= this.custom.length) return;
            [this.custom[i], this.custom[j]] = [this.custom[j], this.custom[i]];
            this.persist();
        },
        metaFor(f) {
            return (f.meta || '') + (f.canRequired ? ' - ' + (f.required ? 'obligatoire' : 'optionnel') : '')
                + (f.canEnabled && !f.enabled ? ' - masqué' : '');
        },
        customMeta(co) {
            const types = cfg.customTypes || {};
            return (types[co.type] || co.type) + (co.required ? ' - obligatoire' : ' - optionnel');
        },
        async persist() {
            this.saving = true;
            const body = new FormData();
            body.append('_token', this.csrf);
            body.append('_method', 'PUT');
            this.standard.forEach((f) => {
                body.append('fields[' + f.key + '][label]', f.label || '');
                body.append('fields[' + f.key + '][help]', f.help || '');
                if (f.key === 'keywords') body.append('fields[keywords][separator]', f.separator || 'comma');
                if (f.canEnabled && f.enabled) body.append('fields[' + f.key + '][enabled]', '1');
                if (f.canRequired && f.required) body.append('fields[' + f.key + '][required]', '1');
            });
            this.custom.forEach((c, i) => {
                body.append('custom[' + i + '][key]', c.key);
                body.append('custom[' + i + '][label]', c.label || '');
                body.append('custom[' + i + '][type]', c.type || 'text');
                if (c.required) body.append('custom[' + i + '][required]', '1');
                body.append('custom[' + i + '][help]', c.help || '');
                body.append('custom[' + i + '][options_text]', c.options_text || '');
            });
            try {
                const res = await fetch(this.saveUrl, {
                    method: 'POST',
                    body,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                });
                if (!res.ok) throw new Error('http ' + res.status);
                window.ukcToast && window.ukcToast('Formulaire enregistré.', 'ok');
            } catch (e) {
                window.ukcToast && window.ukcToast('Échec de l’enregistrement.', 'err');
            }
            this.saving = false;
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

    // Champ de mots-clés « tags » (style Select2, multi) - sans dépendance.
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

    // Éditeur de texte enrichi minimal (WYSIWYG) - gras/italique/souligné/listes.
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
                        event_id: this.cfg.event_id, kind: this.cfg.kind, type: this.cfg.type,
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

// ---------------------------------------------------------------------------
// Garde des surfaces modales : pose `body.ukc-overlay-open` dès qu'un slideover,
// une modale, une confirmation ou une visionneuse est ouvert. Deux effets, via
// la CSS : (1) la **page est verrouillée** (`overflow: hidden`) pour ne plus
// avoir deux barres de défilement - celle de la page ET celle du panneau - et
// pour qu'elle réapparaisse à la fermeture ; (2) la **bulle du chatbot** s'efface
// (elle est fixée en bas à droite, pile où ces surfaces posent leurs boutons).
// ⚠️ `html { scrollbar-gutter: stable }` réserve déjà la gouttière : verrouiller
// la page ne décale donc pas le contenu.
// ---------------------------------------------------------------------------
const UKC_OVERLAYS = '.ukc-slideover, .ukc-confirm-overlay, .ukc-modal, .modal-overlay, .lb-overlay';

function ukcOverlayVisible() {
    return [...document.querySelectorAll(UKC_OVERLAYS)].some((el) => {
        const cs = getComputedStyle(el);

        return cs.display !== 'none' && cs.visibility !== 'hidden' && cs.opacity !== '0';
    });
}

function initOverlayGuard() {
    let queued = false;
    const sync = () => {
        queued = false;
        document.body.classList.toggle('ukc-overlay-open', ukcOverlayVisible());
    };
    const schedule = () => {
        if (queued) {
            return;
        }
        queued = true;
        requestAnimationFrame(sync);
    };

    // Les surfaces sont pilotées par Alpine (`x-show` → style inline), par une
    // classe, ou créées à la volée (confirmation) : on écoute les trois.
    new MutationObserver(schedule).observe(document.body, {
        attributes: true, attributeFilter: ['style', 'class'], childList: true, subtree: true,
    });
    sync();
}

document.addEventListener('DOMContentLoaded', initOverlayGuard);

// Bulle « polie » : elle s'efface quand elle recouvre une action (bouton, lien,
// champ) et revient dès que le pointeur s'en approche. Le lanceur est fixé en
// bas à droite, pile où les tableaux du back-office posent leur colonne
// « Action » : sans cela, le bouton de la dernière ligne visible est
// inatteignable. Le panneau ouvert n'est jamais masqué.
function initChatbotPoliteness() {
    const root = document.querySelector('.ukc-cb-root');
    const fab = root?.querySelector('.ukc-cb-fab');
    if (!root || !fab) {
        return;
    }

    const ACTIONS = 'button, a[href], input, select, textarea, [role="button"]';
    let pointerNear = false;

    const coversAction = () => {
        // Panneau ouvert ou pointeur à proximité : la bulle reste pleinement là.
        if (pointerNear || root.querySelector('.ukc-cb-panel.open')) {
            return false;
        }
        const r = fab.getBoundingClientRect();
        if (!r.width) {
            return false;
        }

        return [
            [r.left + r.width / 2, r.top + r.height / 2],
            [r.left + 8, r.top + 8],
            [r.right - 8, r.bottom - 8],
        ].some(([x, y]) => document.elementsFromPoint(x, y)
            .filter((el) => !root.contains(el))
            .some((el) => el.closest(ACTIONS)));
    };

    let queued = false;
    const sync = () => {
        queued = false;
        root.classList.toggle('is-shy', coversAction());
    };
    const schedule = () => {
        if (queued) {
            return;
        }
        queued = true;
        requestAnimationFrame(sync);
    };

    document.addEventListener('mousemove', (e) => {
        const r = fab.getBoundingClientRect();
        // Zone de rattrapage autour de la bulle : on la ramène avant de l'atteindre.
        const near = e.clientX > r.left - 120 && e.clientY > r.top - 120;
        if (near !== pointerNear) {
            pointerNear = near;
            schedule();
        }
    }, { passive: true });

    window.addEventListener('scroll', schedule, { passive: true });
    window.addEventListener('resize', schedule, { passive: true });
    schedule();
}

document.addEventListener('DOMContentLoaded', initChatbotPoliteness);

// ---------------------------------------------------------------------------
// Voile de travail de l'IA
// ---------------------------------------------------------------------------
// Une analyse IA dure dix à soixante secondes, sans rien à l'écran : l'admin
// recliquait, ou quittait la page en croyant à un blocage. Le voile occupe donc
// l'écran en bleu de charte, annonce ce qui se fait, fait défiler les étapes et
// compte le temps écoulé. Il se ferme par `hide()` - jamais au clic : on ne
// propose pas d'annuler ce qu'on ne sait pas interrompre côté serveur.
const ukcAiVeil = (() => {
    let el = null;
    let timer = null;
    let tick = null;
    let depth = 0; // appels imbriqués : on ne ferme qu'au dernier

    const DEFAULT_STEPS = [
        'Lecture de vos documents…',
        'Extraction des informations…',
        'Recoupement avec l’événement en cours…',
        'Mise en forme du résultat…',
    ];

    function build(title, note) {
        const veil = document.createElement('div');
        veil.className = 'ukc-ai-veil';
        veil.setAttribute('role', 'status');
        veil.setAttribute('aria-live', 'polite');
        veil.innerHTML = '<div class="ukc-ai-veil__box">'
            + '<div class="ukc-ai-veil__halo" aria-hidden="true"></div>'
            + '<div class="ukc-ai-veil__title"></div>'
            + '<div class="ukc-ai-veil__step"></div>'
            + '<div class="ukc-ai-veil__bar" aria-hidden="true"><span></span></div>'
            + '<div class="ukc-ai-veil__foot"><span class="t">0 s</span><span class="n"></span></div>'
            + '</div>';
        veil.querySelector('.ukc-ai-veil__title').textContent = title;
        veil.querySelector('.ukc-ai-veil__foot .n').textContent = note;

        return veil;
    }

    return {
        /** Affiche le voile. Les étapes défilent tant que `hide()` n'est pas appelé. */
        show(opts = {}) {
            depth += 1;
            if (el) return;

            const steps = (opts.steps && opts.steps.length) ? opts.steps : DEFAULT_STEPS;
            el = build(opts.title || 'L’IA travaille…', opts.note || 'Ne fermez pas cette page.');
            document.body.appendChild(el);
            document.body.classList.add('ukc-ai-busy');

            const stepEl = el.querySelector('.ukc-ai-veil__step');
            const timeEl = el.querySelector('.ukc-ai-veil__foot .t');
            let i = 0;
            const started = Date.now();
            stepEl.textContent = steps[0];

            timer = setInterval(() => {
                // La dernière étape reste affichée : mieux vaut un libellé figé
                // qu'un cycle qui laisserait croire que ça recommence.
                i = Math.min(i + 1, steps.length - 1);
                stepEl.textContent = steps[i];
            }, 2600);

            tick = setInterval(() => {
                timeEl.textContent = Math.round((Date.now() - started) / 1000) + ' s';
            }, 1000);

            requestAnimationFrame(() => el?.classList.add('is-in'));

            // ⚠️ Soumission native : la page part et le voile s'en va avec elle,
            // mais un retour arrière restaure la page depuis le cache - voile
            // compris. On le retire alors de force.
            window.addEventListener('pageshow', (ev) => { if (ev.persisted) { depth = 1; this.hide(); } }, { once: true });
        },

        /** Message final affiché brièvement avant la fermeture (succès ou échec). */
        done(message, ok = true) {
            if (!el) return;
            const stepEl = el.querySelector('.ukc-ai-veil__step');
            clearInterval(timer);
            timer = null;
            stepEl.textContent = message;
            el.classList.toggle('is-err', !ok);
        },

        hide() {
            depth = Math.max(0, depth - 1);
            if (depth > 0 || !el) return;

            const dying = el;
            el = null;
            clearInterval(timer); timer = null;
            clearInterval(tick); tick = null;
            document.body.classList.remove('ukc-ai-busy');
            dying.classList.remove('is-in');
            setTimeout(() => dying.remove(), 280);
        },
    };
})();
window.ukcAiVeil = ukcAiVeil;

// Les formulaires IA classiques (soumission native ou data-ajax) déclenchent le
// voile par le seul attribut `data-ai`. `data-ai-title` / `data-ai-steps`
// (étapes séparées par « | ») personnalisent le texte.
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-ai')) return;

    // Confirmation ou AJAX : c'est le gestionnaire ci-dessus qui lève le voile,
    // au bon moment (après la popup, et avec les options du bouton cliqué).
    if (form.dataset.confirm || e.submitter?.dataset.confirm || form.hasAttribute('data-ajax')) return;

    ukcAiVeil.show(ukcAiVeilOptions(form));
}, true);

/* ------------------------------------------------------------------ *
 * Formulaire de paiement EMBARQUÉ (prestataire en ligne)
 *
 * ⚠️ **Le navigateur ne dit JAMAIS qu'un paiement a réussi.** Quand le
 * prestataire annonce la fin, on ne lit pas son verdict : on demande au serveur
 * de vérifier auprès de lui (`payment.verify`). Le navigateur ne fait que
 * donner l'heure - « regardez maintenant ». Et si cet appel n'arrive jamais
 * (onglet fermé, réseau coupé), `payments:reconcile` confirmera de son côté :
 * ce script accélère, il ne décide pas.
 * ------------------------------------------------------------------ */
const UKC_FEDAPAY_CHECKOUT = 'https://cdn.fedapay.com/checkout.js?v=1.1.7';

function ukcPayEmbedScript(src) {
    // Une seule balise par adresse : rouvrir le formulaire ne doit pas
    // réinjecter le script, qui redéfinirait l'objet global en pleine saisie.
    const deja = document.querySelector(`script[data-ukc-pay-embed="${src}"]`);
    if (deja) return deja.__ukcReady;

    const el = document.createElement('script');
    el.src = src;
    el.async = true;
    el.dataset.ukcPayEmbed = src;
    el.__ukcReady = new Promise((ok, ko) => {
        el.addEventListener('load', () => ok());
        el.addEventListener('error', () => ko(new Error('script indisponible')));
    });
    document.head.appendChild(el);

    return el.__ukcReady;
}

/**
 * ⚠️ **La fenêtre ne s'ouvre QUE lorsque le formulaire du prestataire est là.**
 * Elle s'ouvrait vide, et le payeur regardait un cadre blanc se remplir - une à
 * deux secondes, au moment du parcours où l'on doute le plus.
 *
 * ⚠️ **Trois filets, et ce n'est pas du zèle.** Le cadre peut arriver déjà
 * chargé (son `load` ne rejouera jamais), le prestataire peut changer la façon
 * dont il le pose, et le réseau peut traîner. Sans plancher, l'attente
 * tournerait à l'infini et la PORTE DE SORTIE - la page hébergée, en pied de
 * fenêtre - resterait cachée : un paiement perdu, pour avoir voulu épargner un
 * cadre blanc.
 */
function ukcPayEmbedReveal(wrap, box) {
    let fait = false;
    const montrer = () => {
        if (fait) return;
        fait = true;
        wrap.dataset.ready = '1';
    };

    // Plancher général : on montre la fenêtre quoi qu'il arrive.
    window.setTimeout(montrer, 12000);

    const guetter = () => {
        const cadre = box.querySelector('iframe');
        if (!cadre) return false;

        cadre.addEventListener('load', montrer);
        // Le cadre est là, donc le formulaire vient : on ne l'attend pas plus.
        window.setTimeout(montrer, 4000);

        return true;
    };

    if (guetter()) return;

    const obs = new MutationObserver(() => {
        if (guetter()) obs.disconnect();
    });
    obs.observe(box, { childList: true, subtree: true });
}

function initPayEmbed(root = document) {
    root.querySelectorAll('[data-pay-embed]:not([data-pay-embed-ready])').forEach((wrap) => {
        wrap.dataset.payEmbedReady = '1';

        let cfg;
        try {
            cfg = JSON.parse(wrap.dataset.payEmbed);
        } catch {
            return;
        }

        const box = wrap.querySelector('[data-pay-embed-box]');
        const fermer = () => {
            wrap.remove();
            document.body.classList.remove('ukc-pay-embed-open');
        };

        wrap.querySelector('[data-pay-embed-close]')?.addEventListener('click', fermer);
        // ⚠️ Échap ferme la fenêtre, comme toute surface modale du projet - mais
        // il n'annule rien : la transaction vit déjà chez le prestataire.
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && document.body.contains(wrap)) fermer();
        });

        document.body.classList.add('ukc-pay-embed-open');

        const verifier = () => fetch(cfg.verify_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ payment: cfg.payment }),
        }).catch(() => {}).then(() => window.location.reload());

        ukcPayEmbedScript(UKC_FEDAPAY_CHECKOUT).then(() => {
            // ⚠️ On LÈVE l'erreur au lieu de rendre la main en silence : sinon la
            // fenêtre resterait sur son attente, pour toujours, sans un mot.
            if (typeof window.FedaPay === 'undefined') throw new Error('objet absent');

            ukcPayEmbedReveal(wrap, box);

            window.FedaPay.init({
                public_key: cfg.public_key,
                environment: cfg.environment,
                container: box,
                // ⚠️ On reprend la transaction CRÉÉE CÔTÉ SERVEUR : son montant et
                // son objet sont les nôtres. Laisser le formulaire les composer
                // reviendrait à laisser le payeur fixer ce qu'il règle.
                transaction: { id: cfg.transaction_id },
                // ⚠️ **Sans pays, le formulaire propose les opérateurs du Bénin**,
                // quel que soit l'indicatif du numéro - mesuré en production sur
                // un numéro togolais. Ce bloc vient du serveur, et c'est le même
                // que celui de la transaction.
                ...(cfg.customer && Object.keys(cfg.customer).length ? { customer: cfg.customer } : {}),
                // L'opérateur choisi chez nous. ⚠️ La transaction le porte déjà ;
                // ce canal-ci n'est pas documenté, et s'il est ignoré rien ne
                // change. On ne l'envoie jamais vide : un `mode` vide vaudrait
                // peut-être « aucun opérateur acceptable ».
                ...(cfg.mode ? { mode: cfg.mode } : {}),
                onComplete(resp) {
                    if (resp?.reason === window.FedaPay.DIALOG_DISMISSED) return;
                    verifier();
                },
            });
        }).catch(() => {
            // Le script n'a pas chargé, ou n'a rien défini : la porte de sortie reste
            // à l'écran, et on le DIT - un cadre vide sans un mot se lit comme une
            // panne de GEVES.
            //
            // ⚠️ `data-failed` **montre la fenêtre** : c'est elle qui porte le message
            // et le lien vers la page hébergée.
            wrap.dataset.failed = '1';
        });
    });
}

document.addEventListener('DOMContentLoaded', () => initPayEmbed());
