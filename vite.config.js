import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/passkeys.js',
                'resources/css/site.css',
                'resources/js/site.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
                bunny('Bricolage Grotesque', {
                    weights: [500, 700, 800],
                    preload: [{ weight: 800 }],
                    fallbacks: ['Arial Narrow', 'system-ui', 'sans-serif'],
                }),
                bunny('Figtree', {
                    weights: [400, 500, 600],
                    preload: [{ weight: 400 }],
                    fallbacks: ['Segoe UI', 'system-ui', 'sans-serif'],
                }),
                bunny('JetBrains Mono', {
                    weights: [400, 600],
                    preload: false,
                    fallbacks: ['ui-monospace', 'Cascadia Mono', 'Consolas', 'monospace'],
                }),
            ],
        }),
        tailwindcss(),
    ]),
    server: {
        cors: true,
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/storage/framework/views/**',
                '**/vendor/**',
            ],
        },
    },
});
