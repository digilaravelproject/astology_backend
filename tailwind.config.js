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
          DEFAULT: '#c40000',
          light: '#e01818',
          dark: '#820000',
        },
        secondary: {
          DEFAULT: '#d99a00',
          light: '#f4bf2a',
          dark: '#a96e00',
        },
        accent: {
          DEFAULT: '#f4b41a',
          light: '#ffd45a',
          dark: '#b97800',
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
          DEFAULT: '#d99a00',
          light: '#f4bf2a',
          dark: '#a96e00',
        },
        info: {
          DEFAULT: '#339af0',
          light: '#5cadff',
          dark: '#1c7ed6',
        },
        dark: '#2b0a0a',
        gray: {
          DEFAULT: '#6b5750',
          light: '#9b8980',
          lighter: '#eadfd2',
        },
        light: '#fff8e8',
        sidebar: {
          bg: '#ffffff',
          hover: '#fff1cf',
          active: '#c40000',
        },
        text: {
          primary: '#2b0a0a',
          secondary: '#5c4239',
          muted: '#8f796e',
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
