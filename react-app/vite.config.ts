import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  build: {
    outDir: 'dist',
    manifest: true,         // generates .vite/manifest.json (PHP reads this)
    rollupOptions: {
      input: 'index.html',
    },
  },
  server: {
    port: 5173,
    cors: true,             // allow WordPress to load dev assets cross-origin
    origin: 'http://localhost:5173', // ensures asset URLs resolve correctly cross-origin
    allowedHosts: true,             // allow requests from the WordPress .local domain
    hmr: false,                     // disable HMR — refresh manually when developing via WordPress
  },
})
