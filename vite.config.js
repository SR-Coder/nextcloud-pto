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
        rollupOptions: {
            input: resolve(__dirname, 'src/main.js'),
            output: {
                format: 'iife',
                entryFileNames: 'pto-main.js',
                assetFileNames: 'pto-main.[ext]',
            },
        },
    },
})
