<?php

namespace Techysavvy\DocToMarkdown\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Techysavvy\DocToMarkdown\Converters\PdfMarkdownFormatter;

class PdfMarkdownFormatterTest extends TestCase
{
    public function test_it_joins_wrapped_lines_of_a_plain_paragraph(): void
    {
        $markdown = (new PdfMarkdownFormatter)->format("First line.\nSecond line.");

        $this->assertSame('First line. Second line.', $markdown);
    }

    public function test_it_promotes_an_isolated_short_line_to_a_heading(): void
    {
        $markdown = (new PdfMarkdownFormatter)->format("Introduction\n\nThis is the body text of the section.");

        $this->assertSame("## Introduction\n\nThis is the body text of the section.", $markdown);
    }

    public function test_it_does_not_promote_a_short_sentence_ending_in_punctuation(): void
    {
        $markdown = (new PdfMarkdownFormatter)->format('This is short.');

        $this->assertSame('This is short.', $markdown);
    }

    public function test_it_converts_bullet_prefixed_lines_into_a_list(): void
    {
        $markdown = (new PdfMarkdownFormatter)->format("\u{2022} First item\n\u{2022} Second item");

        $this->assertSame("- First item\n- Second item", $markdown);
    }

    public function test_it_converts_numbered_lines_into_an_ordered_list(): void
    {
        $markdown = (new PdfMarkdownFormatter)->format("1. Step one\n2. Step two");

        $this->assertSame("1. Step one\n2. Step two", $markdown);
    }

    public function test_it_converts_aligned_columns_into_a_table(): void
    {
        $markdown = (new PdfMarkdownFormatter)->format("Name     Age\nAlice    30\nBob      25");

        $expected = <<<'MD'
        | Name | Age |
        | --- | --- |
        | Alice | 30 |
        | Bob | 25 |
        MD;

        $this->assertSame($expected, $markdown);
    }

    public function test_it_turns_bare_urls_into_markdown_links(): void
    {
        $markdown = (new PdfMarkdownFormatter)->format('Visit https://example.com/docs for details.');

        $this->assertSame('Visit [https://example.com/docs](https://example.com/docs) for details.', $markdown);
    }

    public function test_it_converts_bullets_separated_from_their_text_by_a_zero_width_space(): void
    {
        // Some PDF generators (Google Docs among them) place a zero-width
        // space, not a real space, between a bullet glyph and its text.
        $markdown = (new PdfMarkdownFormatter)->format("\u{25CF}\u{200B}First item\n\u{25CF}\u{200B}Second item");

        $this->assertSame("- First item\n- Second item", $markdown);
    }

    public function test_it_merges_a_bullet_items_wrapped_continuation_line(): void
    {
        $markdown = (new PdfMarkdownFormatter)->format(
            "• Built a feature that wraps\nonto a second physical line.\n• A second, shorter item."
        );

        $this->assertSame(
            "- Built a feature that wraps onto a second physical line.\n- A second, shorter item.",
            $markdown
        );
    }

    public function test_it_promotes_an_all_caps_heading_even_with_no_blank_line_before_its_body(): void
    {
        // Some documents run a section heading straight into its body with
        // no blank-line paragraph break in the PDF's text layer.
        $markdown = (new PdfMarkdownFormatter)->format("EXPERIENCE\nSenior Engineer at Example Corp.");

        $this->assertSame("## EXPERIENCE\n\nSenior Engineer at Example Corp.", $markdown);
    }

    public function test_it_renders_a_list_that_is_preceded_by_context_lines_in_the_same_block(): void
    {
        // A job title/company line commonly sits directly above its bullet
        // points with no blank line separating them.
        $markdown = (new PdfMarkdownFormatter)->format(
            "Acme Corp\nSenior Engineer · 2019 – 2023\n• Shipped the thing\n• Fixed the other thing"
        );

        $this->assertSame(
            "Acme Corp Senior Engineer · 2019 – 2023\n\n- Shipped the thing\n- Fixed the other thing",
            $markdown
        );
    }
}
