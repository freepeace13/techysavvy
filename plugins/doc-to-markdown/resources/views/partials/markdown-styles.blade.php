<style>
    [x-cloak] { display: none !important; }

    /*
     * Tailwind's preflight resets headings to font-size/font-weight
     * inherit and links to color/text-decoration inherit, so markdown-it
     * output renders as undifferentiated plain text until these rules
     * put the document semantics back. markdown-it ships no CSS of its
     * own — it only emits HTML — so this block is the whole stylesheet
     * for a rendered document.
     */
    .markdown-render h1, .markdown-render h2, .markdown-render h3,
    .markdown-render h4, .markdown-render h5, .markdown-render h6 {
        font-family: var(--font-display, inherit);
        font-weight: 600;
        line-height: 1.25;
        margin: 1.4em 0 0.5em;
        text-wrap: balance;
    }
    .markdown-render h1 { font-size: 1.85em; letter-spacing: -0.02em; }
    .markdown-render h2 { font-size: 1.45em; letter-spacing: -0.01em; }
    .markdown-render h3 { font-size: 1.2em; }
    .markdown-render h4 { font-size: 1.05em; }
    .markdown-render h5 { font-size: 1em; }
    .markdown-render h6 { font-size: 0.9em; color: var(--color-ink-muted, #5B6664); }

    .markdown-render h1:first-child, .markdown-render h2:first-child,
    .markdown-render h3:first-child { margin-top: 0; }

    .markdown-render h1, .markdown-render h2 {
        padding-bottom: 0.25em;
        border-bottom: 1px solid var(--color-steel-200, #e4e7ec);
    }

    .markdown-render p { margin: 0.75em 0; }
    .markdown-render :is(p, li, blockquote) { overflow-wrap: anywhere; }

    .markdown-render a {
        color: var(--color-signal-600, #B93D0C);
        text-decoration: underline;
        text-underline-offset: 2px;
    }
    .markdown-render a:hover { color: var(--color-signal-700, #92300A); }

    .markdown-render strong { font-weight: 600; }
    .markdown-render em { font-style: italic; }

    .markdown-render blockquote {
        margin: 0.75em 0;
        padding: 0.1em 0 0.1em 1em;
        border-left: 3px solid var(--color-steel-300, #d0d5dd);
        color: var(--color-ink-muted, #5B6664);
    }

    .markdown-render code {
        font-family: var(--font-mono, ui-monospace, monospace);
        font-size: 0.9em;
        background: var(--color-surface, #fff);
        border: 1px solid var(--color-steel-200, #e4e7ec);
        border-radius: 0.25rem;
        padding: 0.1em 0.35em;
    }
    .markdown-render pre {
        margin: 0.75em 0;
        padding: 0.75em 1em;
        background: var(--color-surface, #fff);
        border: 1px solid var(--color-steel-200, #e4e7ec);
        border-radius: 0.5rem;
        overflow-x: auto;
    }
    /* A fenced block is <pre><code>; the inline chrome must not repeat. */
    .markdown-render pre code {
        background: none;
        border: 0;
        border-radius: 0;
        padding: 0;
        font-size: 0.875em;
    }

    .markdown-render img { max-width: 100%; height: auto; }
    .markdown-render ul, .markdown-render ol { margin: 0.75em 0; padding-left: 1.5em; }
    .markdown-render li { margin: 0.25em 0; }
    .markdown-render hr { margin: 1.5em 0; border: none; border-top: 1px dashed var(--color-steel-300, #d0d5dd); }
    .markdown-render table { border-collapse: collapse; margin: 0.75em 0; width: 100%; }
    .markdown-render th, .markdown-render td {
        border: 1px solid var(--color-steel-200, #e4e7ec);
        padding: 0.4em 0.6em;
        text-align: left;
    }
    .markdown-render th { background: var(--color-surface, #fff); font-weight: 600; }
</style>
