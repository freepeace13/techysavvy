import markdownit from 'markdown-it';
import { docToMarkdown } from './component.js';

// html: false — the Markdown we render is derived from user-uploaded
// documents, so raw HTML passthrough stays off.
const md = markdownit({ html: false, linkify: true });

window.docToMarkdownRender = function (markdown) {
    return md.render(markdown);
};

// Referenced by the page's x-data="docToMarkdown({...})".
window.docToMarkdown = docToMarkdown;
