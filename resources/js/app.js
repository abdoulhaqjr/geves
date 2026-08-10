// Alpine est fourni par Livewire 4. On enregistre nos composants au démarrage.
document.addEventListener('alpine:init', () => {
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
