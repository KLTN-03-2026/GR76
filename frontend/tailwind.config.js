/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: [
    './index.html',
    './src/**/*.{vue,js,ts,jsx,tsx}'
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          DEFAULT: '#18E299',
          light:   '#d4fae8',
          deep:    '#0fa76e',
        },
        surface: {
          DEFAULT: '#ffffff',
          50:      '#fafafa',
          100:     '#f5f5f5',
        },
        neutral: {
          50:  '#fafafa',
          100: '#f5f5f5',
          200: '#e5e5e5',
          400: '#888888',
          500: '#666666',
          700: '#333333',
          900: '#0d0d0d',
        },
        emergency: {
          DEFAULT: '#d45656',
          dark:    '#b94444',
          light:   '#fde8e8'
        },
        warn:  '#c37d0d',
        info:  '#3772cf',
      },
      fontFamily: {
        sans:  ['Inter', 'Inter Fallback', 'system-ui', '-apple-system', 'sans-serif'],
        mono:  ['Geist Mono', 'Geist Mono Fallback', 'ui-monospace', 'SFMono-Regular', 'monospace'],
      },
      borderRadius: {
        'sm':   '4px',
        'md':   '8px',
        'std':  '16px',
        'lg':   '24px',
        'pill': '9999px',
      },
      boxShadow: {
        'card':   '0 2px 4px rgba(0,0,0,0.03)',
        'button': '0 1px 2px rgba(0,0,0,0.06)',
        'dark-card': '0 2px 4px rgba(0,0,0,0.4)',
      },
      animation: {
        'shimmer':    'shimmer 2s infinite',
        'fade-in':    'fadeIn 0.3s ease-out',
        'slide-up':   'slideUp 0.3s ease-out',
        'pulse-ring': 'pulseRing 1.5s ease-out infinite'
      },
      keyframes: {
        shimmer: {
          '0%':   { backgroundPosition: '-1000px 0' },
          '100%': { backgroundPosition: '1000px 0' }
        },
        fadeIn: {
          '0%':   { opacity: 0 },
          '100%': { opacity: 1 }
        },
        slideUp: {
          '0%':   { opacity: 0, transform: 'translateY(20px)' },
          '100%': { opacity: 1, transform: 'translateY(0)' }
        },
        pulseRing: {
          '0%':   { transform: 'scale(0.8)', opacity: 1 },
          '100%': { transform: 'scale(2)', opacity: 0 }
        }
      },
    }
  },
  plugins: [
    require('@tailwindcss/forms')
  ]
}
