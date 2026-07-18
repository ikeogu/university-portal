import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{js,jsx}',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
            },
            // Named (not bracket-arbitrary) so resources/js/lib/layout.js can
            // compose them into shared width strings reliably — Tailwind
            // 3.4.19 silently fails to generate a `variant:max-w-[Npx]`
            // combination (confirmed: bare max-w-[560px] and sm:max-w-md
            // each work alone, but sm:max-w-[560px] together does not,
            // via content-scanning or safelist).
            maxWidth: {
                shell: '480px',
                'shell-sm': '560px',
            },
            colors: {
                navy: {
                    DEFAULT: '#1c355f',
                    dark: '#16294f',
                    darker: '#111f3e',
                },
                primary: {
                    DEFAULT: '#22407e',
                    hover: '#1a3266',
                },
                // Public landing page only — scoped by usage, not by anything
                // structural. See cgpa-frontend redesign: rest of the app
                // (Admin/Lecturer/Score-entry) stays navy-only.
                accent: {
                    DEFAULT: '#6c4fd1',
                    hover: '#5b3fbd',
                },
                ink: '#17233f',
                body: '#44506e',
                muted: '#66708a',
                faint: '#8c95ab',
                faint2: '#9aa3b8',
                page: '#e7ebf3',
                shell: '#f7f8fb',
                'input-bg': '#fbfcfe',
                border: {
                    DEFAULT: '#e7ebf3',
                    soft: '#dde3ee',
                    input: '#d5dbe8',
                },
                'table-head': '#f2f5fa',
                tint: '#eef2fa',
                success: {
                    DEFAULT: '#0a7d4f',
                    bg: '#e7f4ed',
                },
                error: {
                    DEFAULT: '#b3261e',
                    bg: '#fbeeed',
                },
                grade: {
                    a: '#0a7d4f',
                    b: '#15697c',
                    c: '#22407e',
                    d: '#8a5b00',
                    e: '#a34c0a',
                    f: '#b3261e',
                },
            },
            keyframes: {
                toastin: {
                    from: { opacity: 0, transform: 'translate(-50%, 10px)' },
                    to: { opacity: 1, transform: 'translate(-50%, 0)' },
                },
                screenin: {
                    from: { opacity: 0, transform: 'translateY(10px)' },
                    to: { opacity: 1, transform: 'none' },
                },
                popin: {
                    from: { opacity: 0, transform: 'scale(.94)' },
                    to: { opacity: 1, transform: 'none' },
                },
                barin: {
                    from: { flexGrow: 0.0001 },
                },
            },
            animation: {
                toastin: 'toastin .25s ease',
                screenin: 'screenin .3s ease both',
                popin: 'popin .25s ease both',
                barin: 'barin .7s ease both',
            },
        },
    },

    plugins: [forms],
};
