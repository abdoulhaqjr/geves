/**
 * Scanner de QR de badge (pointage à l'accueil). Charge html5-qrcode à la
 * demande, ouvre la caméra arrière et, à chaque lecture, émet un CustomEvent
 * « scan » (detail = texte décodé) capté côté Blade/Alpine → $wire.lookup().
 * Anti-rebond : ignore les lectures répétées trop rapprochées.
 */
export async function startBadgeScanner() {
    const el = document.getElementById('badge-scanner');
    if (!el || el.dataset.started === '1') return;
    el.dataset.started = '1';

    let Html5Qrcode;
    try {
        ({ Html5Qrcode } = await import('html5-qrcode'));
    } catch (e) {
        el.innerHTML = '<div class="scan-fallback">Scanner indisponible. Utilisez la saisie manuelle.</div>';
        return;
    }

    const scanner = new Html5Qrcode('badge-scanner', { verbose: false });
    let last = 0;
    let lastText = '';

    const onScan = (text) => {
        const now = Date.now();
        if (text === lastText && now - last < 3000) return;
        last = now;
        lastText = text;
        el.dispatchEvent(new CustomEvent('scan', { detail: text, bubbles: true }));
    };

    try {
        await scanner.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 240, height: 240 } },
            onScan,
            () => {},
        );
    } catch (e) {
        el.dataset.started = '';
        el.innerHTML = '<div class="scan-fallback">Caméra indisponible ou refusée. Utilisez la saisie manuelle ci-dessous.</div>';
    }
}
