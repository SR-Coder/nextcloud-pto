import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
    plugins: [vue()],
    define: {
        'process.env.NODE_ENV': JSON.stringify('production'),
    },
    build: {
        outDir: 'js',
        emptyOutDir: false,
        minify: false,
        rollupOptions: {
            input: resolve(__dirname, 'src/admin-settings.js'),
            output: {
                format: 'iife',
                entryFileNames: 'pto-admin-settings.js',
            },
        },
    },
})
