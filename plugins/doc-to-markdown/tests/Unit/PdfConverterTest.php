<?php

namespace Techysavvy\DocToMarkdown\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Techysavvy\DocToMarkdown\Converters\PdfConverter;
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

        $markdown = (new PdfConverter())->convert($path);

        unlink($path);

        $expected = <<<'MD'
        First paragraph line one. First paragraph line two.

        Second paragraph on page one.

        ---

        Page two paragraph.
        MD;

        $this->assertSame($expected, $markdown);
    }
}
