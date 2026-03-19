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
        minify: false, // Disable minification to debug
        rollupOptions: {
            input: resolve(__dirname, 'src/main.js'),
            output: {
                format: 'iife',
                entryFileNames: 'pto-main.js',
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name && assetInfo.name.endsWith('.css')) {
                        return 'pto-main.css'
                    }
                    return 'pto-main.[ext]'
                },
            },
        },
    },
})
