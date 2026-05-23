/**
 * SIMAFTUNSUR — Tailwind CSS Design Tokens
 *
 * Berkas ini disiapkan untuk proyek Laravel 13 + Livewire 4.
 * Salin ke root proyek Laravel dan jalankan `npm install && npm run dev`.
 *
 * Identitas visual: institusional, biru Universitas Suryakancana,
 * fokus keterbacaan data, tidak playful, tidak "AI-banget".
 */

import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // Biru institusional UNSUR — palet utama
                primary: {
                    50:  '#EFF6FF',
                    100: '#DBEAFE',
                    200: '#BFDBFE',
                    300: '#93C5FD',
                    400: '#60A5FA',
                    500: '#3B82F6',
                    600: '#2563EB',
                    700: '#1D4ED8', // tombol & aksi utama
                    800: '#1E40AF',
                    900: '#1E3A8A',
                    950: '#0F2C5C', // navy untuk sidebar & header gelap
                },
                // Palet khusus untuk visualisasi cluster (colorblind-safe)
                cluster: {
                    1: '#1D4ED8',
                    2: '#16A34A',
                    3: '#F59E0B',
                    4: '#7C3AED',
                    5: '#DC2626',
                },
                // Status semantik
                success: '#16A34A',
                warning: '#F59E0B',
                danger:  '#DC2626',
                info:    '#0EA5E9',
            },
            fontSize: {
                // Token tipografi khusus
                'kpi':     ['2.25rem', { lineHeight: '1',   fontWeight: '700', letterSpacing: '-0.02em' }],
                'display': ['1.75rem', { lineHeight: '1.2', fontWeight: '700', letterSpacing: '-0.02em' }],
                'eyebrow': ['0.75rem', { lineHeight: '1',   fontWeight: '600', letterSpacing: '0.08em' }],
            },
            spacing: {
                'sidebar': '15rem', // 240px - lebar sidebar tetap
                'topbar':  '3.5rem', // 56px  - tinggi topbar
            },
            boxShadow: {
                // Bayangan halus — institusional, tidak heavy
                'card':       '0 1px 2px 0 rgb(15 44 92 / 0.04), 0 1px 3px 0 rgb(15 44 92 / 0.06)',
                'card-hover': '0 4px 6px -1px rgb(15 44 92 / 0.08), 0 2px 4px -2px rgb(15 44 92 / 0.06)',
            },
            borderRadius: {
                'card': '0.5rem', // 8px — institusional, bukan rounded-3xl yang playful
            },
            transitionDuration: {
                DEFAULT: '200ms',
            },
        },
    },
    plugins: [forms],
};
