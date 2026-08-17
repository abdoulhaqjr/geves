/**
 * Graphiques du tableau de bord admin (Chart.js, chargé en code-split). Lit les
 * données réelles depuis <script type="application/json" id="dashboardData"> :
 * statuts (donut), soumissions/mois (barres), paiements cumulés (courbe double
 * axe), axes thématiques (barres horizontales).
 */
export async function startDashboardCharts() {
    const dataEl = document.getElementById('dashboardData');
    if (!dataEl) return;

    let cfg;
    try { cfg = JSON.parse(dataEl.textContent || '{}'); } catch (e) { return; }

    const { Chart, registerables } = await import('chart.js');
    Chart.register(...registerables);
    Chart.defaults.color = '#475569';
    Chart.defaults.font.family = "'Jost', system-ui, sans-serif";
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.boxWidth = 8;

    const blue = '#223ea4', green = '#009933', warn = '#d97706', err = '#dc2626', review = '#7c3aed', grid = '#e2e8f0';
    const mk = (id) => { const el = document.getElementById(id); return el ? el.getContext('2d') : null; };

    if (mk('chartStatus') && cfg.status) {
        new Chart(mk('chartStatus'), {
            type: 'doughnut',
            data: { labels: cfg.status.labels, datasets: [{ data: cfg.status.data, backgroundColor: [blue, review, warn, green, err], borderWidth: 0, hoverOffset: 6 }] },
            options: {
                responsive: true, maintainAspectRatio: true, cutout: '62%',
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (ctx) => {
                        const total = ctx.dataset.data.reduce((a, b) => a + b, 0) || 1;
                        return ' ' + ctx.label + ' : ' + ctx.raw + ' (' + Math.round((ctx.raw / total) * 100) + ' %)';
                    } } },
                },
            },
        });
    }

    if (mk('chartSubsMonth') && cfg.months) {
        new Chart(mk('chartSubsMonth'), {
            type: 'bar',
            data: { labels: cfg.months.labels, datasets: [{ label: 'Soumissions', data: cfg.months.data, backgroundColor: blue, borderRadius: 6, maxBarThickness: 28 }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => ' ' + ctx.raw + ' soumissions' } } },
                scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: grid } } },
            },
        });
    }

    if (mk('chartPayments') && cfg.payments) {
        new Chart(mk('chartPayments'), {
            type: 'line',
            data: { labels: cfg.payments.labels, datasets: [
                { label: 'Recettes cumulées (k FCFA)', data: cfg.payments.revenue, borderColor: green, backgroundColor: 'rgba(0,153,51,.12)', fill: true, tension: 0.35, pointRadius: 3, pointHoverRadius: 6 },
                { label: 'Paiements validés', data: cfg.payments.count, borderColor: blue, backgroundColor: 'transparent', tension: 0.35, pointRadius: 3, pointHoverRadius: 5, yAxisID: 'y1' },
            ] },
            options: {
                responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, title: { display: true, text: 'k FCFA', font: { size: 11 } }, grid: { color: grid } },
                    y1: { beginAtZero: true, position: 'right', title: { display: true, text: 'Nb paiements', font: { size: 11 } }, grid: { display: false } },
                },
            },
        });
    }

    if (mk('chartAxes') && cfg.axes) {
        new Chart(mk('chartAxes'), {
            type: 'bar',
            data: { labels: cfg.axes.labels, datasets: [{ label: 'Soumissions', data: cfg.axes.data, backgroundColor: [blue, review, green, warn, '#0d9488', '#64748b'], borderRadius: 6, maxBarThickness: 32 }] },
            options: {
                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => ' ' + ctx.raw + ' soumissions' } } },
                scales: { x: { beginAtZero: true, grid: { color: grid } }, y: { grid: { display: false } } },
            },
        });
    }
}
