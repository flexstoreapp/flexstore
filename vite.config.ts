import babel from '@rolldown/plugin-babel';
import cloudflared from '@aerni/vite-plugin-laravel-cloudflared';
import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react, { reactCompilerPreset } from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import adminThemes from './resources/js/vite-plugins/admin-themes.ts';
import translationTypes from './resources/js/vite-plugins/translation-types.ts';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/admin.css',
                'resources/css/storefront.css',
                'resources/js/admin.tsx',
                'resources/js/storefront.tsx',
                'resources/js/installer.tsx',
            ],
        }),
        inertia(),
        wayfinder({
            routes: false,
            formVariants: true,
        }),
        translationTypes(),
        adminThemes(),
        react(),
        babel({
            presets: [reactCompilerPreset()],
        }),
        tailwindcss(),
        cloudflared(),
    ],
    server: {
        watch: {
            ignored: ['**/.env', '**/database/*.sqlite', '**/docs/**'],
        },
    },
    oxc: {
        jsx: {
            runtime: 'automatic',
        },
    },
    optimizeDeps: {
        include: ['@stripe/react-stripe-js', '@stripe/stripe-js'],
    },
    build: {
        chunkSizeWarningLimit: 500,
    },
    ssr: {
        noExternal: ['@atlaskit/pragmatic-drag-and-drop'],
    },
});
