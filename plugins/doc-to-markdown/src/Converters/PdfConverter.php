<?php

namespace Techysavvy\DocToMarkdown\Converters;

class PdfConverter implements DocumentConverter
{
    public function convert(string $filePath): string
    {
        throw new \RuntimeException('Not yet implemented.');
    }
}
