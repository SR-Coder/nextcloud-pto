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
        rollupOptions: {
            input: {
                main: resolve(__dirname, 'src/main.js'),
                'admin-settings': resolve(__dirname, 'src/admin-settings.js'),
            },
            output: {
                entryFileNames: 'pto-[name].js',
                assetFileNames: (assetInfo) => {
                    // Ensure CSS files use the correct naming pattern
                    if (assetInfo.name && assetInfo.name.endsWith('.css')) {
                        return 'pto-[name].css'
                    }
                    return 'pto-[name].[ext]'
                },
            },
        },
    },
})
