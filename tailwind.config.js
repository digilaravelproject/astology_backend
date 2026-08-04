/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Poppins', 'Arial', 'sans-serif'],
      },
      colors: {
        primary: {
          DEFAULT: '#8B0D31',
          light: '#A72A4F',
          dark: '#650923',
        },
        secondary: {
          DEFAULT: '#D1A167',
          light: '#E4C399',
          dark: '#AF7B41',
        },
        accent: {
          DEFAULT: '#D1A167',
          light: '#E4C399',
          dark: '#AF7B41',
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
          DEFAULT: '#D1A167',
          light: '#E4C399',
          dark: '#AF7B41',
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
          hover: '#F8EFE5',
          active: '#8B0D31',
        },
        text: {
          primary: '#2b0a0a',
          secondary: '#5c4239',
          muted: '#8f796e',
        },
        amber: {
          50: '#FCF9F5', 100: '#F7EEE4', 200: '#ECD8C0', 300: '#DFC099',
          400: '#D1A167', 500: '#D1A167', 600: '#B9874E', 700: '#96683C',
          800: '#714A2D', 900: '#4D301E', 950: '#2E1B10',
        },
        yellow: {
          50: '#FCF9F5', 100: '#F7EEE4', 200: '#ECD8C0', 300: '#DFC099',
          400: '#D1A167', 500: '#D1A167', 600: '#B9874E', 700: '#96683C',
          800: '#714A2D', 900: '#4D301E', 950: '#2E1B10',
        },
        red: {
          50: '#FFF4F7', 100: '#FCE7EE', 200: '#F8CAD8', 300: '#EFA1B8',
          400: '#C74469', 500: '#8B0D31', 600: '#7A0B2B', 700: '#650923',
          800: '#4E071B', 900: '#3D0717', 950: '#26030D',
        },
        rose: {
          50: '#FFF4F7', 100: '#FCE7EE', 200: '#F8CAD8', 300: '#EFA1B8',
          400: '#C74469', 500: '#8B0D31', 600: '#7A0B2B', 700: '#650923',
          800: '#4E071B', 900: '#3D0717', 950: '#26030D',
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
