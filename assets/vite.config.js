import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
  plugins: [vue()],
  base: './',
  build: {
    outDir: 'build',
    emptyOutDir: true,
    modulePreload: false,
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'src/main.js'),
      },
      output: {
        entryFileNames: 'main.js',
        chunkFileNames: 'chunk-[name]-[hash].js',
        assetFileNames: 'assets/[name]-[hash].[ext]',
        format: 'es',
        manualChunks: (id) => {
          // Split vendor chunks
          if (id.includes('node_modules')) {
            if (id.includes('vue')) {
              return 'vendor-vue';
            }
            if (id.includes('pinia')) {
              return 'vendor-pinia';
            }
            if (id.includes('axios')) {
              return 'vendor-axios';
            }
            if (id.includes('@coreui/vue') || id.includes('@coreui/icons')) {
              return 'vendor-coreui';
            }
            if (id.includes('chart.js') || id.includes('@coreui/chartjs')) {
              return 'vendor-charts';
            }
            return 'vendor';
          }
          
          // Split route chunks for lazy loading
          if (id.includes('views/')) {
            const viewName = id.split('views/')[1].split('.')[0];
            return `view-${viewName}`;
          }
          
          // Split component chunks
          if (id.includes('components/')) {
            const componentName = id.split('components/')[1].split('/')[1]?.split('.')[0];
            if (componentName) {
              return `component-${componentName}`;
            }
          }
        },
      },
    },
    treeshake: {
      moduleSideEffects: false,
    },
    chunkSizeWarningLimit: 1000,
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
      '@coreui/icons': '@coreui/icons/dist/esm/free'
    },
  },
  css: {
    preprocessorOptions: {
      scss: {
        additionalData: `@import "@coreui/coreui/scss/coreui.scss";`
      }
    }
  },
  optimizeDeps: {
    include: ['vue', 'pinia', 'axios', '@coreui/vue', '@coreui/icons-vue'],
  },
});
