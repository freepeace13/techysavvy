<?php

namespace Techysavvy\DocToMarkdown\Converters;

use Smalot\PdfParser\Parser;

class PdfConverter implements DocumentConverter
{
    public function __construct(private readonly PdfMarkdownFormatter $formatter) {}

    public function convert(string $filePath): string
    {
        $pdf = (new Parser)->parseFile($filePath);

        $pages = [];
        foreach ($pdf->getPages() as $page) {
            $pages[] = $this->formatter->format($page->getText());
        }

        return implode("\n\n---\n\n", array_filter($pages, fn (string $p) => $p !== ''));
    }
}
