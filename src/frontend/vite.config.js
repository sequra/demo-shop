import { defineConfig } from 'vite';
import { cpSync } from 'fs';

function copyPublicAssets() {
  return {
    name: 'copy-public-assets',
    closeBundle() {
      cpSync('public', '../backend/public/dist', { recursive: true });
    }
  };
}

export default defineConfig({
  build: {
    outDir: '../backend/public/dist',
    emptyOutDir: true,
    cssCodeSplit: false,
    lib: {
      entry: 'src/index.js',
      name: 'SeQuraCheckout',
      fileName: () => 'sequra-checkout.js',
      formats: ['iife']
    }
  },
  plugins: [copyPublicAssets()],
  server: {
    port: 3000,
    host: true,
    cors: true,
    allowedHosts: 'all',
    fs: {
      allow: ['.']
    }
  }
});
