import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  base: '/spa/dist/',
  resolve: {
    alias: { '@': import.meta.dirname + '/src' }
  },
  build: {
    outDir: 'dist',
    assetsDir: 'assets',
    sourcemap: false,
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('node_modules/vue') || id.includes('node_modules/pinia') || id.includes('node_modules/vue-router')) return 'vendor-vue'
          if (id.includes('node_modules/axios'))      return 'vendor-http'
          if (id.includes('node_modules/@vueuse'))    return 'vendor-utils'
        }
      }
    }
  },
  server: {
    proxy: {
      '/php_action': { target: 'http://localhost', changeOrigin: true },
      '/assests':    { target: 'http://localhost', changeOrigin: true },
    }
  }
})
