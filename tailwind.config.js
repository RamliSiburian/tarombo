import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50:  '#fdf4f4',
                    100: '#fbe8e8',
                    200: '#f5c6c6',
                    300: '#e89090',
                    400: '#d45555',
                    // primary-500 = medium red, dipakai untuk border/icon aksen
                    500: '#BC0202',
                    // primary-600 = dark red, dipakai untuk bg button utama
                    600: '#830000',
                    // primary-700 = very dark maroon, dipakai hover & bg section
                    700: '#6C1A1A',
                    800: '#4a1212',
                    900: '#2d0a0a',
                    950: '#1a0404',
                },
            },
        },
    },

    plugins: [forms],
};
