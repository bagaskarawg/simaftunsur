import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

// Deteksi otomatis lingkungan GitHub Codespaces. Saat berjalan di sana,
// Vite perlu listen di 0.0.0.0 dan menerbitkan URL aset ke host
// forwarded (*.app.github.dev) lengkap dengan HMR via WebSocket Secure.
const namaCodespace = process.env.CODESPACE_NAME;
const domainCodespace =
    process.env.GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN ?? 'app.github.dev';

const PORT_VITE = 5173;

const konfigCodespaces = namaCodespace
    ? {
          host: '0.0.0.0',
          port: PORT_VITE,
          strictPort: true,
          origin: `https://${namaCodespace}-${PORT_VITE}.${domainCodespace}`,
          hmr: {
              host: `${namaCodespace}-${PORT_VITE}.${domainCodespace}`,
              protocol: 'wss',
              clientPort: 443,
          },
      }
    : {};

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        // Saat di Codespaces, halaman Laravel disajikan dari domain
        // *-8000.app.github.dev sementara aset Vite dari *-5173.app.github.dev
        // — beda origin → butuh izin CORS eksplisit. Regex di bawah cocok
        // dengan seluruh subdomain Codespaces (juga GitHub Codespaces
        // Preview/PR yang kadang memakai forwarded domain berbeda).
        cors: namaCodespace
            ? {
                  origin: [
                      new RegExp(`^https://${namaCodespace}-\\d+\\.${domainCodespace.replace(/\./g, '\\.')}$`),
                  ],
                  credentials: true,
              }
            : true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
        ...konfigCodespaces,
    },
});
