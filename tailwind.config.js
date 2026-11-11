import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './app/Http/Controllers/**/*.php',
    ],

    theme: {
        extend: {
            colors: {
                // Design tokens from the product blueprint.
                brand: {
                    50: '#EEF0FF',
                    100: '#E0E3FF',
                    200: '#C7CCFE',
                    300: '#A5ABFC',
                    400: '#8A8DF8',
                    500: '#6F6DF0',
                    600: '#5B50E3',
                    700: '#4338CA',
                    800: '#372FA3',
                    900: '#2F2B81',
                    950: '#1D1A4E',
                },
                accent: {
                    400: '#8B9BFF',
                    500: '#667EEA',
                    600: '#5068D8',
                },
                ink: {
                    50: '#F6F7FB',
                    100: '#ECEEF5',
                    200: '#D8DCE8',
                    300: '#B4BACB',
                    400: '#8890A8',
                    500: '#636B85',
                    600: '#4A5068',
                    700: '#343A50',
                    800: '#22263A',
                    900: '#14142B',
                    950: '#0C0C1C',
                },
                success: '#059669',
                warning: '#D97706',
                danger: '#DC2626',
            },

            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Poppins', 'Inter', ...defaultTheme.fontFamily.sans],
            },

            borderRadius: {
                card: '0.75rem',
            },

            boxShadow: {
                card: '0 1px 2px rgba(20, 20, 43, 0.04), 0 8px 24px -12px rgba(20, 20, 43, 0.12)',
                lift: '0 12px 32px -12px rgba(20, 20, 43, 0.28)',
            },

            spacing: {
                18: '4.5rem',
            },

            maxWidth: {
                '8xl': '90rem',
            },

            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(8px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                shimmer: {
                    '100%': { transform: 'translateX(100%)' },
                },
            },

            animation: {
                'fade-up': 'fade-up 0.35s ease-out both',
            },
        },
    },

    plugins: [forms],
};
