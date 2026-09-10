<?php

namespace Techysavvy\DocToMarkdown\Tests\Feature;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Techysavvy\DocToMarkdown\Tests\TestCase;

class ConvertDocxTest extends TestCase
{
    public function test_it_converts_an_uploaded_docx_to_markdown(): void
    {
        $phpWord = new PhpWord();
        $phpWord->addTitleStyle(1, ['bold' => true, 'size' => 20]);
        $phpWord->addSection()->addTitle('Report', 1);
        $phpWord->getSections()[0]->addText('A short paragraph.');

        $path = tempnam(sys_get_temp_dir(), 'convert-docx-test-').'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        $file = new UploadedFile($path, 'report.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        $response = $this->post(route('doc-to-markdown.convert'), ['file' => $file], ['Accept' => 'application/json']);

        unlink($path);

        $response->assertOk();
        $response->assertJson([
            'markdown' => "# Report\n\nA short paragraph.",
            'filename' => 'report.md',
        ]);
    }
}
