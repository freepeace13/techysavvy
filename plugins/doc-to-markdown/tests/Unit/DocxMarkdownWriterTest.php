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
        $phpWord = new PhpWord;
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

        $markdown = (new DocxMarkdownWriter)->write($this->roundTrip($phpWord));

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
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $section->addLink('https://example.com', 'Example Site');

        $run = $section->addTextRun();
        $run->addText('See ');
        $run->addLink('https://example.com/docs', 'the docs');
        $run->addText(' for more.');

        $markdown = (new DocxMarkdownWriter)->write($this->roundTrip($phpWord));

        $expected = <<<'MD'
        [Example Site](https://example.com)

        See [the docs](https://example.com/docs) for more.
        MD;

        $this->assertSame($expected, $markdown);
    }

    public function test_it_escapes_pipe_characters_in_table_cells(): void
    {
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();

        $table = $section->addTable();
        $table->addRow();
        $table->addCell(2000)->addText('A | B');
        $table->addCell(2000)->addText('Normal');

        $markdown = (new DocxMarkdownWriter)->write($this->roundTrip($phpWord));

        $expected = <<<'MD'
        | A \| B | Normal |
        | --- | --- |
        MD;

        $this->assertSame($expected, $markdown);
    }

    public function test_it_placeholders_embedded_images(): void
    {
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $section->addText('Before image.');
        // A 1x1 transparent PNG, inline base64 so the test needs no fixture file.
        $pngPath = tempnam(sys_get_temp_dir(), 'docx-writer-test-').'.png';
        file_put_contents($pngPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        $section->addImage($pngPath);
        $section->addText('After image.');

        $markdown = (new DocxMarkdownWriter)->write($this->roundTrip($phpWord));

        unlink($pngPath);

        $this->assertSame("Before image.\n\n*[image omitted]*\n\nAfter image.", $markdown);
    }

    public function test_it_merges_adjacent_runs_with_the_same_style_and_keeps_edge_whitespace_outside_markers(): void
    {
        $phpWord = new PhpWord;
        $run = $phpWord->addSection()->addTextRun();
        $run->addText('Hel', ['bold' => true]);
        $run->addText('lo ', ['bold' => true]);
        $run->addText('world');

        $markdown = (new DocxMarkdownWriter)->write($this->roundTrip($phpWord));

        $this->assertSame('**Hello** world', $markdown);
    }

    public function test_it_escapes_markdown_syntax_in_document_text(): void
    {
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $section->addText('2 * 3 = snake_case [x]');
        $section->addText('# not a heading');

        $markdown = (new DocxMarkdownWriter)->write($this->roundTrip($phpWord));

        $this->assertSame("2 \\* 3 = snake\\_case \\[x\\]\n\n\\# not a heading", $markdown);
    }

    public function test_it_keeps_nested_list_items_in_one_list_and_continues_numbering(): void
    {
        $phpWord = new PhpWord; // resets the static style registry, so it must come first

        Style::addNumberingStyle('docx-writer-nested', [
            'type' => 'hybridMultilevel',
            'levels' => [
                ['level' => 0, 'format' => 'decimal', 'text' => '%1.', 'left' => 720, 'hanging' => 360],
                ['level' => 1, 'format' => 'bullet', 'text' => '', 'left' => 1440, 'hanging' => 360],
            ],
        ]);

        $section = $phpWord->addSection();
        $section->addListItemRun(0, 'docx-writer-nested')->addText('One');
        $section->addListItemRun(1, 'docx-writer-nested')->addText('Nested');
        $section->addListItemRun(0, 'docx-writer-nested')->addText('Two');

        $markdown = (new DocxMarkdownWriter)->write($this->roundTrip($phpWord));

        $this->assertSame("1. One\n   - Nested\n2. Two", $markdown);
    }

    public function test_it_pads_merged_cells_so_table_rows_line_up(): void
    {
        $phpWord = new PhpWord;
        $table = $phpWord->addSection()->addTable();
        $table->addRow();
        $table->addCell(4000, ['gridSpan' => 2])->addText('Wide');
        $table->addRow();
        $table->addCell(2000)->addText('A');
        $table->addCell(2000)->addText('B');

        $markdown = (new DocxMarkdownWriter)->write($this->roundTrip($phpWord));

        $this->assertSame("| Wide |  |\n| --- | --- |\n| A | B |", $markdown);
    }

    public function test_it_flattens_multiple_paragraphs_in_a_table_cell_to_one_line(): void
    {
        $phpWord = new PhpWord;
        $table = $phpWord->addSection()->addTable();
        $table->addRow();
        $cell = $table->addCell(4000);
        $cell->addText('First');
        $cell->addText('Second');

        $markdown = (new DocxMarkdownWriter)->write($this->roundTrip($phpWord));

        $this->assertSame("| First Second |\n| --- |", $markdown);
    }

    public function test_it_percent_encodes_spaces_and_parentheses_in_link_targets(): void
    {
        $phpWord = new PhpWord;
        $phpWord->addSection()->addLink('https://example.com/a b(1)', 'Doc');

        $markdown = (new DocxMarkdownWriter)->write($this->roundTrip($phpWord));

        $this->assertSame('[Doc](https://example.com/a%20b%281%29)', $markdown);
    }
}
