/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./admin/src/**/*.{js,jsx,ts,tsx}",
    "./admin/views/**/*.php",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#eff6ff',
          100: '#dbeafe',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
        },
        trigger: '#10b981',
        action: '#3b82f6',
        agent: '#8b5cf6',
        condition: '#f59e0b',
      },
    },
  },
  plugins: [],
}

