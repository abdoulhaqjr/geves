import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
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
