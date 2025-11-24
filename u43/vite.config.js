import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
  plugins: [react()],
  build: {
    outDir: 'admin/assets/dist',
    emptyOutDir: true,
    rollupOptions: {
      input: path.resolve(__dirname, 'admin/src/workflow-builder.jsx'),
      output: {
        entryFileNames: 'workflowBuilder.js',
        chunkFileNames: 'chunks/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name === 'index.css') {
            return 'workflowBuilder.css';
          }
          return 'assets/[name]-[hash].[ext]';
        },
      },
    },
    cssCodeSplit: false,
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'admin/src'),
    },
  },
  server: {
    port: 3000,
    open: false,
  },
});

