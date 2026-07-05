import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/admin/bookings-realtime.js',
                'resources/js/user/realtime-user.js',
            ],
            refresh: true,
        }),
    ],
});
