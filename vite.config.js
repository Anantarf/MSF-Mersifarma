import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/layout.css',
                'resources/js/layout.js',
                'resources/js/pages/call-actuals.js',
                'resources/js/pages/call-lists.js',
                'resources/js/pages/call-plans.js',
                'resources/js/pages/login.js',
                'resources/css/pages/call-actuals.css',
                'resources/css/pages/call-lists.css',
                'resources/css/pages/call-plans.css',
                'resources/css/pages/login.css',
            ],
            refresh: true,
        }),
    ],
});
