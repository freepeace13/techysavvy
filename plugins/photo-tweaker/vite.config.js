import { defineConfig } from 'vite';
import { fileURLToPath } from 'node:url';

// Library mode, IIFE, no externals: one classic <script> plus one stylesheet,
// served by core from resources/dist. This build is the plugin's own —
// host/'s Vite build must never learn about this tool (see CLAUDE.md).
export default defineConfig({
    build: {
        outDir: 'resources/dist',
        emptyOutDir: true,
        lib: {
            entry: fileURLToPath(new URL('resources/js/photo-tweaker.js', import.meta.url)),
            name: 'photoTweakerBundle',
            formats: ['iife'],
            fileName: () => 'photo-tweaker.js',
            cssFileName: 'photo-tweaker',
        },
    },
});
