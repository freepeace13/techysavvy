<?php

namespace Techysavvy\DocToMarkdown\Converters;

/**
 * Turns the flat, positionless text a PDF text layer yields into Markdown.
 * PDFs carry no semantic structure, so headings/lists/tables are inferred
 * from line shape rather than read from real document structure.
 */
class PdfMarkdownFormatter
{
    // Real unicode bullet glyphs are unambiguous markers on their own, so no
    // following whitespace is required — some PDF generators (Google Docs
    // among them) place a zero-width space, not a real space, after the
    // glyph, which \s never matches.
    private const UNICODE_BULLET_PATTERN = '/^[•●▪]\s*(.*)$/u';

    // ASCII "-"/"*" need a real following space, otherwise a hyphenated word
    // or an emphasis marker would be misread as a list item.
    private const ASCII_BULLET_PATTERN = '/^[\-\*]\s+(.*)$/u';

    private const NUMBERED_PATTERN = '/^(\d+)[\.\)]\s+(.*)$/u';

    private const URL_PATTERN = '/\bhttps?:\/\/[^\s<>()]+[^\s<>().,;:!?]/i';

    // Zero-width characters some PDF generators use as invisible separators
    // (e.g. between a bullet glyph and its text). They carry no visible
    // meaning and only interfere with the line-shape heuristics below.
    private const INVISIBLE_CHARS_PATTERN = '/[\x{200B}\x{200C}\x{200D}\x{FEFF}]/u';

    public function format(string $text): string
    {
        $normalized = str_replace("\r\n", "\n", $text);
        $normalized = preg_replace(self::INVISIBLE_CHARS_PATTERN, '', $normalized);
        $blocks = preg_split('/\n\s*\n/', $normalized);

        $rendered = array_map(fn (string $block) => $this->formatBlock($block), $blocks);

        return implode("\n\n", array_filter($rendered, fn (string $block) => $block !== ''));
    }

    private function formatBlock(string $block): string
    {
        $lines = array_values(array_filter(
            array_map('trim', explode("\n", trim($block))),
            fn (string $line) => $line !== ''
        ));

        if ($lines === []) {
            return '';
        }

        if (count($lines) === 1 && $this->looksLikeHeading($lines[0])) {
            return '## '.$this->linkify($lines[0]);
        }

        // Some documents run a section heading directly into its body with
        // no blank line between them (no paragraph break in the PDF's text
        // layer), so the block never reduces to a single line. An ALL-CAPS
        // first line is still a strong, low-risk heading signal in that case.
        if (count($lines) > 1 && $this->looksLikeAllCapsHeading($lines[0])) {
            $heading = '## '.$this->linkify($lines[0]);
            $rest = $this->formatBlock(implode("\n", array_slice($lines, 1)));

            return $rest === '' ? $heading : $heading."\n\n".$rest;
        }

        // A list rarely starts a block cleanly — it's often preceded, with no
        // blank line, by a line or two of context (e.g. a job title above
        // its bullet points), so the whole block is scanned for the first
        // marked line rather than requiring $lines[0] itself to be one.
        $bulletStart = $this->firstIndexMatching($lines, fn (string $line) => $this->matchBullet($line) !== null);

        if ($bulletStart !== null) {
            return $this->withLeadingContext($lines, $bulletStart, implode("\n", array_map(
                fn (string $item) => '- '.$this->linkify($item),
                $this->groupBulletItems(array_slice($lines, $bulletStart))
            )));
        }

        $numberedStart = $this->firstIndexMatching($lines, fn (string $line) => preg_match(self::NUMBERED_PATTERN, $line) === 1);

        if ($numberedStart !== null) {
            return $this->withLeadingContext($lines, $numberedStart, implode("\n", array_map(
                fn (array $item) => $item['number'].'. '.$this->linkify($item['text']),
                $this->groupNumberedItems(array_slice($lines, $numberedStart))
            )));
        }

        if (count($lines) >= 2 && ($table = $this->formatTable($lines)) !== null) {
            return $table;
        }

        return $this->linkify(implode(' ', $lines));
    }

    private function looksLikeHeading(string $line): bool
    {
        if (mb_strlen($line) > 80) {
            return false;
        }

        if ($this->matchBullet($line) !== null || preg_match(self::NUMBERED_PATTERN, $line) === 1) {
            return false;
        }

        return preg_match('/[.,;:!?]$/', $line) !== 1;
    }

    private function looksLikeAllCapsHeading(string $line): bool
    {
        if (! $this->looksLikeHeading($line)) {
            return false;
        }

        return preg_match('/\p{Ll}/u', $line) !== 1 && preg_match('/\p{Lu}/u', $line) === 1;
    }

    /**
     * @param  string[]  $lines
     * @param  callable(string): bool  $matches
     */
    private function firstIndexMatching(array $lines, callable $matches): ?int
    {
        foreach ($lines as $index => $line) {
            if ($matches($line)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Renders whatever preceded the list (context lines with no marker of
     * their own, e.g. a job title above its bullets) as a plain line above it.
     *
     * @param  string[]  $lines
     */
    private function withLeadingContext(array $lines, int $listStart, string $list): string
    {
        $leading = array_slice($lines, 0, $listStart);

        if ($leading === []) {
            return $list;
        }

        return $this->linkify(implode(' ', $leading))."\n\n".$list;
    }

    /**
     * Matches a line against either bullet glyph pattern and returns the
     * item text, or null if the line isn't a bullet line at all.
     */
    private function matchBullet(string $line): ?string
    {
        if (preg_match(self::UNICODE_BULLET_PATTERN, $line, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match(self::ASCII_BULLET_PATTERN, $line, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    /**
     * A bullet's text often wraps onto following physical lines that carry
     * no marker of their own — those are continuations of the item above,
     * not separate items, so they're merged back in rather than dropped.
     *
     * @param  string[]  $lines
     * @return string[]
     */
    private function groupBulletItems(array $lines): array
    {
        $items = [];

        foreach ($lines as $line) {
            $text = $this->matchBullet($line);

            if ($text !== null) {
                $items[] = $text;
            } elseif ($items !== []) {
                $items[count($items) - 1] .= ' '.$line;
            }
        }

        return $items;
    }

    /**
     * @param  string[]  $lines
     * @return array<int, array{number: string, text: string}>
     */
    private function groupNumberedItems(array $lines): array
    {
        $items = [];

        foreach ($lines as $line) {
            if (preg_match(self::NUMBERED_PATTERN, $line, $matches) === 1) {
                $items[] = ['number' => $matches[1], 'text' => $matches[2]];
            } elseif ($items !== []) {
                $items[count($items) - 1]['text'] .= ' '.$line;
            }
        }

        return $items;
    }

    /** @param string[] $lines */
    private function formatTable(array $lines): ?string
    {
        $rows = array_map(fn (string $line) => preg_split('/\s{2,}/', $line), $lines);

        $columnCount = count($rows[0]);
        if ($columnCount < 2) {
            return null;
        }

        foreach ($rows as $row) {
            if (count($row) !== $columnCount) {
                return null;
            }
        }

        $formatRow = fn (array $row) => '| '.implode(' | ', array_map(
            fn (string $cell) => str_replace('|', '\\|', $this->linkify($cell)),
            $row
        )).' |';

        $lines = [$formatRow($rows[0])];
        $lines[] = '| '.implode(' | ', array_fill(0, $columnCount, '---')).' |';
        for ($r = 1; $r < count($rows); $r++) {
            $lines[] = $formatRow($rows[$r]);
        }

        return implode("\n", $lines);
    }

    private function linkify(string $text): string
    {
        return preg_replace_callback(
            self::URL_PATTERN,
            fn (array $matches) => '['.$matches[0].']('.$matches[0].')',
            $text
        );
    }
}
