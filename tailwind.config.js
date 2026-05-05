import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['DM Sans', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                school: {
                    primary: '#0f3d2e',
                    'primary-hover': '#164a3a',
                    accent: '#b8860b',
                    surface: '#f0f4f2',
                    muted: '#5c6d66',
                },
            },
        },
    },

    plugins: [forms],
};
