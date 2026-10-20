import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/badge-designer.css',
                'resources/js/badge-designer.js',
                'resources/css/template-designer.css',
                'resources/js/template-designer.js',
                'resources/css/designer-tailwise.css',
                // Rédaction à plusieurs d'un document de tâche : entrée À PART.
                // ⚠️ L'éditeur et le CRDT pèsent plusieurs centaines de kilo-octets ;
                // dans `app.js`, tout le back-office et tout l'espace participant
                // les téléchargeraient pour une page qu'ils n'ouvrent pas.
                'resources/css/task-document.css',
                'resources/js/task-document.js',
            ],
            refresh: true,
            fonts: [
                bunny('Jost', {
                    weights: [400, 500, 600, 700, 800, 900],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        // Bind explicite en IPv4 : évite un fichier `hot` en http://[::1]:5173
        // (IPv6) injoignable par certains navigateurs → CSS/HMR non chargés.
        host: '127.0.0.1',
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
