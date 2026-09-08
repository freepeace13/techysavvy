<?php

namespace Techysavvy\DocToMarkdown\Converters;

class ConverterResolver
{
    public function __construct(
        private readonly DocxConverter $docxConverter,
        private readonly PdfConverter $pdfConverter,
    ) {
    }

    public function resolve(string $extension): DocumentConverter
    {
        return match (strtolower($extension)) {
            'docx' => $this->docxConverter,
            'pdf' => $this->pdfConverter,
            default => throw new \RuntimeException("Unsupported file extension: {$extension}"),
        };
    }
}
