import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';
// https://vitejs.dev/config/
export default defineConfig({
    plugins: [react()],
    resolve: {
        alias: {
            '@': resolve(__dirname, './src'),
            '@components': resolve(__dirname, './src/components'),
            '@pages': resolve(__dirname, './src/pages'),
            '@hooks': resolve(__dirname, './src/hooks'),
            '@stores': resolve(__dirname, './src/stores'),
            '@services': resolve(__dirname, './src/services'),
            '@utils': resolve(__dirname, './src/utils'),
            '@types': resolve(__dirname, './src/types'),
            '@i18n': resolve(__dirname, './src/i18n'),
            '@assets': resolve(__dirname, './src/assets'),
        },
    },
    server: {
        port: 5173,
        host: true,
        open: true,
        cors: true,
        proxy: {
            '/api': {
                target: 'http://localhost:8000',
                changeOrigin: true,
                secure: false,
            },
        },
    },
    build: {
        outDir: 'dist',
        sourcemap: true,
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['react', 'react-dom'],
                    router: ['react-router-dom'],
                    ui: ['@headlessui/react', '@heroicons/react', 'lucide-react'],
                    forms: ['react-hook-form', 'react-select', 'react-datepicker'],
                    charts: ['recharts'],
                    utils: ['date-fns', 'clsx', 'tailwind-merge'],
                },
            },
        },
    },
    optimizeDeps: {
        include: [
            'react',
            'react-dom',
            'react-router-dom',
            'zustand',
            'axios',
            'i18next',
            'react-i18next',
        ],
    },
    css: {
        postcss: './postcss.config.js',
    },
    test: {
        globals: true,
        environment: 'jsdom',
        setupFiles: ['./src/test/setup.ts'],
        css: true,
    },
});
