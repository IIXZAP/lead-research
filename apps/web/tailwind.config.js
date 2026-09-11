import defaultTheme from 'tailwindcss/defaultTheme';
import flowbitePlugin from 'flowbite/plugin';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './node_modules/flowbite/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                canvas: '#F4F7FC',
                surface: '#FFFFFF',
                ink: {
                    DEFAULT: '#0B1220',
                    muted: '#5B6B8C',
                },
                brand: {
                    DEFAULT: '#155EEF',
                    deep: '#0B3FC4',
                    light: '#EAF1FF',
                },
                sky: '#5EA1FF',
                line: '#E2E8F5',
            },
            fontFamily: {
                display: ['"Space Grotesk"', ...defaultTheme.fontFamily.sans],
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            boxShadow: {
                card: '0 1px 2px rgba(11, 18, 32, 0.04), 0 8px 24px -12px rgba(21, 94, 239, 0.18)',
                'card-hover': '0 4px 12px rgba(11, 18, 32, 0.06), 0 16px 32px -12px rgba(21, 94, 239, 0.24)',
            },
            keyframes: {
                'scan-sweep': {
                    '0%': { transform: 'translateX(-100%)' },
                    '100%': { transform: 'translateX(250%)' },
                },
                'fade-in-up': {
                    '0%': { opacity: '0', transform: 'translateY(6px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'pulse-ring': {
                    '0%, 100%': { opacity: '0.6' },
                    '50%': { opacity: '1' },
                },
            },
            animation: {
                'scan-sweep': 'scan-sweep 1.8s ease-in-out infinite',
                'fade-in-up': 'fade-in-up 0.35s ease-out both',
                'pulse-ring': 'pulse-ring 2s ease-in-out infinite',
            },
        },
    },
    plugins: [
        flowbitePlugin,
    ],
};
