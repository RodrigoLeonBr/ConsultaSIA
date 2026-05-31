import type { Config } from 'tailwindcss';

export default {
  content: ['./index.html', './src/**/*.{ts,tsx}'],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#0F52BA',
          hover: '#0a3d8f',
          light: '#dbeafe',
        },
        success: '#16a34a',
        warning: '#ca8a04',
        danger: '#dc2626',
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        mono: ['JetBrains Mono', 'ui-monospace', 'Courier New', 'monospace'],
      },
    },
  },
} satisfies Config;
