import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    rollupOptions: {
      external: ['some-external-package'] // only add after verifying package exists
    }
  }
});
 
