<?php

namespace Techysavvy\DocToMarkdown\Converters;

use Smalot\PdfParser\Parser;

class PdfConverter implements DocumentConverter
{
    public function convert(string $filePath): string
    {
        $pdf = (new Parser())->parseFile($filePath);

        $pages = [];
        foreach ($pdf->getPages() as $page) {
            $pages[] = $this->pageToMarkdown($page->getText());
        }

        return implode("\n\n---\n\n", array_filter($pages, fn (string $p) => $p !== ''));
    }

    private function pageToMarkdown(string $text): string
    {
        $normalized = str_replace("\r\n", "\n", $text);
        $paragraphs = preg_split('/\n\s*\n/', $normalized);

        $paragraphs = array_map(function (string $paragraph) {
            $lines = array_map('trim', explode("\n", trim($paragraph)));

            return implode(' ', array_filter($lines, fn (string $line) => $line !== ''));
        }, $paragraphs);

        return implode("\n\n", array_filter($paragraphs, fn (string $p) => $p !== ''));
    }
}
