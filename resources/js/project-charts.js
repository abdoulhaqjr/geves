// Diagrammes de la gestion de projet (Chart.js, chargé à la demande). Pilotés
// par l'état client (Alpine) : `data` est fourni par le composant et `update()`
// repeint après chaque changement. Trois vues : tâches par statut (donut),
// avancement par phase (barres empilées terminé/restant), charge par responsable.
export async function createProjectCharts(data) {
    const { Chart, registerables } = await import('chart.js');
    Chart.register(...registerables);
    Chart.defaults.color = '#475569';
    Chart.defaults.font.family = "'Jost', system-ui, sans-serif";
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.boxWidth = 8;

    const blue = '#223ea4', green = '#16a34a', warn = '#d97706', review = '#7c3aed', slate = '#94a3b8', grid = '#e2e8f0';
    const charts = {};

    function build(id, config) {
        const el = document.getElementById(id);
        if (!el) return;
        const prev = Chart.getChart(el);
        if (prev) prev.destroy();
        charts[id] = new Chart(el.getContext('2d'), config);
    }

    function render(d) {
        build('projChartStatus', {
            type: 'doughnut',
            data: { labels: d.status.labels, datasets: [{ data: d.status.data, backgroundColor: [slate, blue, review, green], borderWidth: 0, hoverOffset: 6 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom' } } },
        });
        build('projChartPhase', {
            type: 'bar',
            data: {
                labels: d.phase.labels,
                datasets: [
                    { label: 'Terminées', data: d.phase.done, backgroundColor: green, stack: 's' },
                    { label: 'Restantes', data: d.phase.remaining, backgroundColor: warn, stack: 's' },
                ],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 }, grid: { color: grid } } },
                plugins: { legend: { position: 'bottom' } },
            },
        });
        build('projChartAssignee', {
            type: 'bar',
            data: { labels: d.assignee.labels, datasets: [{ label: 'Tâches', data: d.assignee.data, backgroundColor: blue, borderRadius: 4 }] },
            options: {
                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                scales: { x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: grid } }, y: { grid: { display: false } } },
                plugins: { legend: { display: false } },
            },
        });
    }

    render(data);
    return { update: render };
}
