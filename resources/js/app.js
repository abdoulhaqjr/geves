// Interactions du site public (menu mobile, galerie/lightbox, filtres).
import './public.js';

// Visio : charge le lecteur LiveKit (SDK livekit-client) uniquement sur la page
// « Rejoindre » — l'import dynamique code-splitte le SDK dans son propre chunk.
if (document.getElementById('lk-config')) {
    import('./meeting.js').then((m) => m.startFromConfig()).catch(() => {});
}

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
});
