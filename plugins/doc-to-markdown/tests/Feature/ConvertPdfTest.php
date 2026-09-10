<?php

namespace Techysavvy\DocToMarkdown\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Techysavvy\DocToMarkdown\Tests\Support\MinimalPdfBuilder;
use Techysavvy\DocToMarkdown\Tests\TestCase;

class ConvertPdfTest extends TestCase
{
    public function test_it_converts_an_uploaded_pdf_to_markdown(): void
    {
        $pdf = MinimalPdfBuilder::build([['A single line of text.']]);

        $path = tempnam(sys_get_temp_dir(), 'convert-pdf-test-').'.pdf';
        file_put_contents($path, $pdf);

        $file = new UploadedFile($path, 'notes.pdf', 'application/pdf', null, true);

        $response = $this->post(route('doc-to-markdown.convert'), ['file' => $file], ['Accept' => 'application/json']);

        unlink($path);

        $response->assertOk();
        $response->assertJson([
            'markdown' => 'A single line of text.',
            'filename' => 'notes.md',
        ]);
    }
}
