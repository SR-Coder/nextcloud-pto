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
        emptyOutDir: true,
        lib: {
            entry: resolve(__dirname, 'src/main.js'),
            name: 'PTO',
            formats: ['iife'],
            fileName: () => 'pto-main.js',
        },
        rollupOptions: {
            output: {
                assetFileNames: 'pto-main.css',
            },
        },
    },
})
