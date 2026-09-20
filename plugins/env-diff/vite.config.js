import { defineConfig } from 'vite';
import { fileURLToPath } from 'node:url';

// Library mode, IIFE, no externals: one classic <script>, served by core from
// resources/dist. This build is the plugin's own — host/'s Vite build must
// never learn about this tool (see CLAUDE.md).
export default defineConfig({
    build: {
        outDir: 'resources/dist',
        emptyOutDir: true,
        lib: {
            entry: fileURLToPath(new URL('resources/js/env-diff.js', import.meta.url)),
            name: 'envDiffBundle',
            formats: ['iife'],
            fileName: () => 'env-diff.js',
        },
    },
});
