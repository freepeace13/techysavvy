<?php

namespace Techysavvy\DocToMarkdown\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Techysavvy\DocToMarkdown\Converters\ConverterResolver;
use Techysavvy\DocToMarkdown\Converters\DocxConverter;
use Techysavvy\DocToMarkdown\Converters\DocxMarkdownWriter;
use Techysavvy\DocToMarkdown\Converters\PdfConverter;
use Techysavvy\DocToMarkdown\Converters\PdfMarkdownFormatter;

class ConverterResolverTest extends TestCase
{
    public function test_it_resolves_docx_to_the_docx_converter(): void
    {
        $resolver = new ConverterResolver(new DocxConverter(new DocxMarkdownWriter), new PdfConverter(new PdfMarkdownFormatter));

        $this->assertInstanceOf(DocxConverter::class, $resolver->resolve('docx'));
        $this->assertInstanceOf(DocxConverter::class, $resolver->resolve('DOCX'));
    }

    public function test_it_resolves_pdf_to_the_pdf_converter(): void
    {
        $resolver = new ConverterResolver(new DocxConverter(new DocxMarkdownWriter), new PdfConverter(new PdfMarkdownFormatter));

        $this->assertInstanceOf(PdfConverter::class, $resolver->resolve('pdf'));
    }

    public function test_it_rejects_an_unsupported_extension(): void
    {
        $resolver = new ConverterResolver(new DocxConverter(new DocxMarkdownWriter), new PdfConverter(new PdfMarkdownFormatter));

        $this->expectException(\RuntimeException::class);

        $resolver->resolve('txt');
    }
}
