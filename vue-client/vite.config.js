import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    host: '0.0.0.0',
    port: 5173,
    watch: {
      usePolling: true,
    },
    hmr: {
      host: 'localhost',
      port: 5173,
    },
    proxy: {
      '/api': {
        target: 'http://nginx:80',
        changeOrigin: true,
      },
      '/broadcasting/auth': {
        target: 'http://nginx:80',
        changeOrigin: true,
      },
    },
  },
})
