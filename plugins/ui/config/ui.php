<?php

return [
    // Vite entrypoints the shared layout should emit. The ui plugin has no
    // build pipeline of its own — only host/ does — so the consuming app
    // owns these paths and can override them by publishing this config.
    'vite_entries' => [
        'resources/css/app.css',
        'resources/js/app.js',
    ],
];
