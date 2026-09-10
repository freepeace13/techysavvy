import { defineConfig } from 'vite';
import { fileURLToPath } from 'node:url';

// Library mode, IIFE, no externals: markdown-it is bundled into the output
// so the page loads exactly one classic <script> tag with no import map and
// no module graph. This build is the plugin's own — host/'s Vite build must
// never learn about this tool (see CLAUDE.md).
export default defineConfig({
    build: {
        outDir: 'resources/dist',
        // The output directory holds exactly one generated file, so there is
        // nothing to clear — and emptying it would blow away sibling files.
        emptyOutDir: false,
        lib: {
            entry: fileURLToPath(new URL('resources/js/doc-to-markdown.js', import.meta.url)),
            name: 'docToMarkdownBundle',
            formats: ['iife'],
            fileName: () => 'doc-to-markdown.js',
        },
    },
});
