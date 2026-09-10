<?php

namespace Techysavvy\DocToMarkdown\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Techysavvy\DocToMarkdown\Converters\PdfConverter;
use Techysavvy\DocToMarkdown\Converters\PdfMarkdownFormatter;
use Techysavvy\DocToMarkdown\Tests\Support\MinimalPdfBuilder;

class PdfConverterTest extends TestCase
{
    public function test_it_reflows_page_text_into_paragraphs_separated_by_page_rules(): void
    {
        $pdf = MinimalPdfBuilder::build([
            ['First paragraph line one.', 'First paragraph line two.', '', 'Second paragraph on page one.'],
            ['Page two paragraph.'],
        ]);

        $path = tempnam(sys_get_temp_dir(), 'pdf-converter-test-').'.pdf';
        file_put_contents($path, $pdf);

        $markdown = (new PdfConverter(new PdfMarkdownFormatter()))->convert($path);

        unlink($path);

        $expected = <<<'MD'
        First paragraph line one. First paragraph line two.

        Second paragraph on page one.

        ---

        Page two paragraph.
        MD;

        $this->assertSame($expected, $markdown);
    }

    public function test_it_renders_bullet_lists_and_links_found_in_the_page_text(): void
    {
        $pdf = MinimalPdfBuilder::build([
            ['- First item', '- Second item', '', 'See https://example.com for more.'],
        ]);

        $path = tempnam(sys_get_temp_dir(), 'pdf-converter-test-').'.pdf';
        file_put_contents($path, $pdf);

        $markdown = (new PdfConverter(new PdfMarkdownFormatter()))->convert($path);

        unlink($path);

        $expected = <<<'MD'
        - First item
        - Second item

        See [https://example.com](https://example.com) for more.
        MD;

        $this->assertSame($expected, $markdown);
    }
}
