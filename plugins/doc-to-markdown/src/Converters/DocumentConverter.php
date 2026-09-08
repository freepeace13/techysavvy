<?php

namespace Techysavvy\DocToMarkdown\Converters;

interface DocumentConverter
{
    public function convert(string $filePath): string;
}
