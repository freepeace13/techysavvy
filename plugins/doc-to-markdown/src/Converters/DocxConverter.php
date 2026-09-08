<?php

namespace Techysavvy\DocToMarkdown\Converters;

use PhpOffice\PhpWord\IOFactory;

class DocxConverter implements DocumentConverter
{
    public function __construct(private readonly DocxMarkdownWriter $writer)
    {
    }

    public function convert(string $filePath): string
    {
        return $this->writer->write(IOFactory::load($filePath));
    }
}
