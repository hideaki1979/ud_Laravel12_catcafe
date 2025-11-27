import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react-swc'
import tailwindcss from '@tailwindcss/vite'

// https://vite.dev/config/
export default defineConfig({
    plugins: [react(), tailwindcss(),],
    server: {
        port: 3000,
        host: true, // Docker外からアクセス可能にする
        proxy: {
            // APIリクエストをExpress Backendにプロキシ
            '/api': {
                target: 'http://localhost:3001',
                changeOrigin: true,
            },
            '/saml': {
                target: 'http://localhost:3001',
                changeOrigin: true,
            },
        },
    },
});
