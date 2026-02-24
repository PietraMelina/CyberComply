import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            colors: {
                cyber: {
                    bg: '#0a0a0f',
                    secondary: '#12121a',
                    card: '#1a1a25',
                    hover: '#252535',
                    border: '#2a2a3a',
                    primary: '#00d4ff',
                    secondaryAccent: '#00a8cc',
                    success: '#00ff88',
                    warning: '#ffaa00',
                    danger: '#ff3366',
                    info: '#00ccff',
                    text: '#f0f0f5',
                    muted: '#a0a0b0',
                    soft: '#606070',
                },
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
                display: ['Space Grotesk', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                cyber: '0 0 20px rgba(0, 212, 255, 0.15)',
                'cyber-lg': '0 0 40px rgba(0, 212, 255, 0.2)',
            },
            animation: {
                glow: 'glow 2s ease-in-out infinite alternate',
                'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
            },
            keyframes: {
                glow: {
                    '0%': { boxShadow: '0 0 6px rgba(0, 212, 255, 0.25)' },
                    '100%': { boxShadow: '0 0 22px rgba(0, 212, 255, 0.45)' },
                },
            },
        },
    },

    plugins: [forms],
};
