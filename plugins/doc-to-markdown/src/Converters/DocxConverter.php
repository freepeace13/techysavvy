<?php

namespace Techysavvy\DocToMarkdown\Converters;

class DocxConverter implements DocumentConverter
{
    public function __construct(private readonly DocxMarkdownWriter $writer)
    {
    }

    public function convert(string $filePath): string
    {
        throw new \RuntimeException('Not yet implemented.');
    }
}
