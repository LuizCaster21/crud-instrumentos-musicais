/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx,html}",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        brand: {
          50: '#fbf7ee',
          100: '#f5ecd6',
          200: '#edd8ac',
          300: '#e2be79',
          400: '#d7a24a',
          500: '#c8872b',
          600: '#ab6921',
          700: '#894e1d',
          800: '#713f1d',
          900: '#5e351c',
          950: '#351b0d',
        },
        dark: {
          900: '#0f1117',
          800: '#161b26',
          700: '#202838',
          600: '#2d374d',
        }
      },
      fontFamily: {
        sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
      }
    },
  },
  plugins: [],
}
