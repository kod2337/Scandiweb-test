/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        'primary': '#5ECE7B',
        'text': '#1D1F22',
        'light-text': '#737680',
        'overlay': 'rgba(57, 55, 72, 0.22)',
        'error': '#D12727',
        'out-of-stock': 'rgba(168, 172, 176, 0.4)',
      },
      spacing: {
        'xs': '4px',
        'sm': '8px',
        'md': '16px',
        'lg': '24px',
        'xl': '32px',
      },
      fontFamily: {
        'sans': ['Raleway', 'Arial', 'sans-serif'],
      },
      fontSize: {
        'small': '14px',
        'base': '16px',
        'large': '18px',
        'xl': '24px',
        '2xl': '30px',
      },
      maxWidth: {
        'container': '1440px',
      },
    },
  },
  plugins: [],
}
