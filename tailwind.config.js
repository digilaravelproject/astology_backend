/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#d63384',
          light: '#f95a8f',
          dark: '#b02a6f',
        },
        secondary: {
          DEFAULT: '#4ecdc4',
          light: '#6ee7df',
          dark: '#3ab5ad',
        },
        accent: {
          DEFAULT: '#ffa500',
          light: '#ffb733',
          dark: '#cc8400',
        },
        success: {
          DEFAULT: '#51cf66',
          light: '#69d77a',
          dark: '#40a352',
        },
        danger: {
          DEFAULT: '#ff6b6b',
          light: '#ff8585',
          dark: '#ee5a52',
        },
        warning: {
          DEFAULT: '#ffa500',
          light: '#ffb733',
          dark: '#cc8400',
        },
        info: {
          DEFAULT: '#339af0',
          light: '#5cadff',
          dark: '#1c7ed6',
        },
        dark: '#222222',
        gray: {
          DEFAULT: '#666666',
          light: '#999999',
          lighter: '#e0e0e0',
        },
        light: '#f5f7fa',
        sidebar: {
          bg: '#ffffff',
          hover: '#fdf2f6',
          active: '#d63384',
        },
        text: {
          primary: '#222222',
          secondary: '#555555',
          muted: '#999999',
        }
      },
      borderRadius: {
        'sm': '6px',
        'md': '8px',
        'lg': '12px',
        'xl': '20px',
      },
      boxShadow: {
        'sm': '0 2px 10px rgba(0, 0, 0, 0.05)',
        'md': '0 2px 10px rgba(0, 0, 0, 0.08)',
        'lg': '0 5px 20px rgba(0, 0, 0, 0.12)',
        'header': '0 2px 10px rgba(0, 0, 0, 0.1)',
      }
    },
  },
  plugins: [],
}
