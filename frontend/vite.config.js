import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    }
  },
  server: {
    proxy: {
      '/api': {
        target: 'http://localhost/sos-backend/public',
        changeOrigin: true,
        secure: false
      },
      '/storage': {
        target: 'http://localhost/sos-backend/public',
        changeOrigin: true,
        secure: false
      }
    }
  }
})
