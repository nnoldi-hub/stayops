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
            colors: {
                brand: {
                    orange: {
                        50: '#FFF7ED',
                        100: '#FFEDD5',
                        200: '#FED7AA',
                        300: '#FDBA74',
                        400: '#FB923C',
                        500: '#F97316',
                        600: '#EA580C',
                        700: '#C2410C',
                        DEFAULT: '#F97316',
                    },
                    blue: {
                        50: '#EFF6FF',
                        100: '#DBEAFE',
                        200: '#BFDBFE',
                        400: '#60A5FA',
                        500: '#3B82F6',
                        600: '#2563EB',
                        700: '#1D4ED8',
                        DEFAULT: '#2563EB',
                    },
                    navy: {
                        700: '#334155',
                        800: '#1E293B',
                        900: '#0F172A',
                        DEFAULT: '#0F172A',
                    },
                    slate: {
                        400: '#94A3B8',
                        500: '#64748B',
                        DEFAULT: '#64748B',
                    },
                },
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                card: '0 1px 2px rgba(15, 23, 42, 0.04), 0 8px 24px -12px rgba(15, 23, 42, 0.12)',
                'card-hover': '0 2px 4px rgba(15, 23, 42, 0.06), 0 16px 40px -16px rgba(15, 23, 42, 0.22)',
            },
        },
    },

    plugins: [forms],
};
