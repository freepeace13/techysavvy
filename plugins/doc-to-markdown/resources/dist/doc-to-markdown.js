(function () {
    var md = window.markdownit({ html: false, linkify: true });

    window.docToMarkdownRender = function (markdown) {
        return md.render(markdown);
    };
})();
