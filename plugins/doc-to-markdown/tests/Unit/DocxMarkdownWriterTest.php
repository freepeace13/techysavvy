<?php

namespace Techysavvy\DocToMarkdown\Tests\Unit;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style;
use PHPUnit\Framework\TestCase;
use Techysavvy\DocToMarkdown\Converters\DocxMarkdownWriter;

class DocxMarkdownWriterTest extends TestCase
{
    /**
     * PHPWord only wraps plain paragraphs/list items/tables in TextRun (etc.)
     * when a document is read back with IOFactory::load() — a freshly
     * in-memory-built PhpWord object stores bare Text elements instead. Since
     * DocxConverter always calls IOFactory::load() on a real upload, these
     * tests round-trip through a temp file so they exercise the exact tree
     * shape the writer sees in production.
     */
    private function roundTrip(PhpWord $phpWord): PhpWord
    {
        $path = tempnam(sys_get_temp_dir(), 'docx-writer-test-').'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);
        $reloaded = IOFactory::load($path);
        unlink($path);

        return $reloaded;
    }

    public function test_it_converts_headings_paragraphs_runs_lists_and_a_table(): void
    {
        $phpWord = new PhpWord();
        $phpWord->addTitleStyle(1, ['bold' => true, 'size' => 20]);
        $phpWord->addTitleStyle(2, ['bold' => true, 'size' => 16]);

        Style::addNumberingStyle('docx-writer-test-bullets', [
            'type' => 'hybridMultilevel',
            'levels' => [
                ['level' => 0, 'format' => 'bullet', 'text' => '', 'left' => 720, 'hanging' => 360],
            ],
        ]);
        Style::addNumberingStyle('docx-writer-test-numbers', [
            'type' => 'hybridMultilevel',
            'levels' => [
                ['level' => 0, 'format' => 'decimal', 'text' => '%1.', 'left' => 720, 'hanging' => 360],
            ],
        ]);

        $section = $phpWord->addSection();
        $section->addTitle('Main Heading', 1);
        $section->addText('This is a plain paragraph.');

        $run = $section->addTextRun();
        $run->addText('This has ');
        $run->addText('bold', ['bold' => true]);
        $run->addText(' and ');
        $run->addText('italic', ['italic' => true]);
        $run->addText(' words.');

        $section->addTitle('Sub Heading', 2);

        $section->addListItemRun(0, 'docx-writer-test-bullets')->addText('First bullet');
        $section->addListItemRun(0, 'docx-writer-test-bullets')->addText('Second bullet');
        $section->addListItemRun(0, 'docx-writer-test-numbers')->addText('First numbered');
        $section->addListItemRun(0, 'docx-writer-test-numbers')->addText('Second numbered');

        $table = $section->addTable();
        $table->addRow();
        $table->addCell(2000)->addText('Header A');
        $table->addCell(2000)->addText('Header B');
        $table->addRow();
        $table->addCell(2000)->addText('Cell 1');
        $table->addCell(2000)->addText('Cell 2');

        $markdown = (new DocxMarkdownWriter())->write($this->roundTrip($phpWord));

        $expected = <<<'MD'
        # Main Heading

        This is a plain paragraph.

        This has **bold** and *italic* words.

        ## Sub Heading

        - First bullet
        - Second bullet

        1. First numbered
        2. Second numbered

        | Header A | Header B |
        | --- | --- |
        | Cell 1 | Cell 2 |
        MD;

        $this->assertSame($expected, $markdown);
    }

    public function test_it_converts_hyperlinks_standalone_and_inside_a_text_run(): void
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addLink('https://example.com', 'Example Site');

        $run = $section->addTextRun();
        $run->addText('See ');
        $run->addLink('https://example.com/docs', 'the docs');
        $run->addText(' for more.');

        $markdown = (new DocxMarkdownWriter())->write($this->roundTrip($phpWord));

        $expected = <<<'MD'
        [Example Site](https://example.com)

        See [the docs](https://example.com/docs) for more.
        MD;

        $this->assertSame($expected, $markdown);
    }

    public function test_it_escapes_pipe_characters_in_table_cells(): void
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $table = $section->addTable();
        $table->addRow();
        $table->addCell(2000)->addText('A | B');
        $table->addCell(2000)->addText('Normal');

        $markdown = (new DocxMarkdownWriter())->write($this->roundTrip($phpWord));

        $expected = <<<'MD'
        | A \| B | Normal |
        | --- | --- |
        MD;

        $this->assertSame($expected, $markdown);
    }

    public function test_it_placeholders_embedded_images(): void
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addText('Before image.');
        // A 1x1 transparent PNG, inline base64 so the test needs no fixture file.
        $pngPath = tempnam(sys_get_temp_dir(), 'docx-writer-test-').'.png';
        file_put_contents($pngPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        $section->addImage($pngPath);
        $section->addText('After image.');

        $markdown = (new DocxMarkdownWriter())->write($this->roundTrip($phpWord));

        unlink($pngPath);

        $this->assertSame("Before image.\n\n*[image omitted]*\n\nAfter image.", $markdown);
    }
}
