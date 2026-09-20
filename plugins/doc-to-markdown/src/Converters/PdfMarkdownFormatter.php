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

    // Lowercase words a Title Case heading may still contain (e.g. "Terms
    // and Conditions of Use") without failing the "every word capitalized"
    // check below.
    private const TITLE_CASE_CONNECTORS = ['and', 'of', 'the', 'in', 'for', 'to', '&'];

    // Balanced "(...)" groups are allowed inside a URL (e.g. Wikipedia
    // links); trailing sentence punctuation is trimmed off afterwards.
    private const URL_PATTERN = '/\bhttps?:\/\/(?:[^\s<>()\[\]]|\([^\s<>()]*\))+/i';

    // A line that is only a page number ("12", "Page 3", "3 of 10").
    private const PAGE_NUMBER_PATTERN = '/^(page\s+)?\d+(\s*(of|\/)\s*\d+)?$/iu';

    // Zero-width characters some PDF generators use as invisible separators
    // (e.g. between a bullet glyph and its text). They carry no visible
    // meaning and only interfere with the line-shape heuristics below.
    private const INVISIBLE_CHARS_PATTERN = '/[\x{200B}\x{200C}\x{200D}\x{FEFF}]/u';

    public function format(string $text): string
    {
        // Some PDF encodings yield invalid UTF-8, which makes every /u regex
        // below return null and would silently blank the page.
        $normalized = mb_scrub(str_replace("\r\n", "\n", $text));
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

        // A lone page number is furniture, not content.
        if (count($lines) === 1 && preg_match(self::PAGE_NUMBER_PATTERN, $lines[0]) === 1) {
            return '';
        }

        if (count($lines) === 1 && $this->looksLikeHeading($lines[0])) {
            return '## '.$this->linkify($lines[0]);
        }

        // A list rarely starts a block cleanly — it's often preceded, with no
        // blank line, by a line or two of context (e.g. a job title above
        // its bullet points), so the whole block is scanned for the first
        // marked line rather than requiring $lines[0] itself to be one.
        $bulletStart = $this->firstIndexMatching($lines, fn (string $line) => $this->matchBullet($line) !== null);
        $numberedStart = $this->firstIndexMatching($lines, fn (string $line) => preg_match(self::NUMBERED_PATTERN, $line) === 1);

        // Some documents run a section heading directly into its body with
        // no blank line between them (no paragraph break in the PDF's text
        // layer), so the block never reduces to a single line. An ALL-CAPS
        // or Title Case first line is a strong, low-risk heading signal in
        // that case — but only when the rest of the block isn't itself a
        // list, where $lines[0] is more likely leading context (e.g. a job
        // title above its bullets) than a heading.
        if (count($lines) > 1 && $bulletStart === null && $numberedStart === null
            && $this->looksLikeHeadingWithoutBreak($lines[0])) {
            $heading = '## '.$this->linkify($lines[0]);
            $rest = $this->formatBlock(implode("\n", array_slice($lines, 1)));

            return $rest === '' ? $heading : $heading."\n\n".$rest;
        }

        if ($bulletStart !== null) {
            return $this->withLeadingContext($lines, $bulletStart, implode("\n", array_map(
                fn (string $item) => '- '.$this->linkify($item),
                $this->groupBulletItems(array_slice($lines, $bulletStart))
            )));
        }

        if ($numberedStart !== null) {
            return $this->withLeadingContext($lines, $numberedStart, implode("\n", array_map(
                fn (array $item) => $item['number'].'. '.$this->linkify($item['text']),
                $this->groupNumberedItems(array_slice($lines, $numberedStart))
            )));
        }

        if (count($lines) >= 2 && ($table = $this->formatTable($lines)) !== null) {
            return $table;
        }

        return $this->escapeBlockStart($this->linkify($this->joinLines($lines)));
    }

    private function looksLikeHeading(string $line): bool
    {
        if (mb_strlen($line) > 80) {
            return false;
        }

        if ($this->matchBullet($line) !== null || preg_match(self::NUMBERED_PATTERN, $line) === 1) {
            return false;
        }

        // A run of 2+ spaces is the same column-alignment signal formatTable()
        // splits on — such a line is table data/header, not a heading.
        if (preg_match('/\s{2,}/u', $line) === 1) {
            return false;
        }

        // No letters means a page number, rule or stray symbol, not a heading.
        if (preg_match('/\p{L}/u', $line) !== 1) {
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
     * A Title Case line (every word capitalized, aside from a small set of
     * lowercase connector words) is as strong a heading signal as ALL-CAPS
     * for the "no blank line before body" case — e.g. "Work Experience" or
     * "Terms and Conditions of Use" running directly into body text.
     */
    private function looksLikeTitleCaseHeading(string $line): bool
    {
        if (! $this->looksLikeHeading($line)) {
            return false;
        }

        $words = preg_split('/\s+/u', trim($line));

        if ($words === false || count($words) < 2) {
            return false;
        }

        $capitalized = 0;

        foreach ($words as $word) {
            if (in_array(mb_strtolower($word), self::TITLE_CASE_CONNECTORS, true)) {
                continue;
            }

            if (preg_match('/^\p{Lu}/u', $word) === 1) {
                $capitalized++;
            } elseif (preg_match('/^[\p{Ll}]/u', $word) === 1) {
                return false;
            }
            // Words opening with a digit or symbol ("2024", "(Draft)") are neutral.
        }

        return $capitalized > 0;
    }

    private function looksLikeHeadingWithoutBreak(string $line): bool
    {
        return $this->looksLikeAllCapsHeading($line) || $this->looksLikeTitleCaseHeading($line);
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

        return $this->escapeBlockStart($this->linkify($this->joinLines($leading)))."\n\n".$list;
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
                $items[count($items) - 1] = $this->joinWrapped($items[count($items) - 1], $line);
            }
        }

        return $items;
    }

    /**
     * Joins a wrapped line onto the text above it. A word split by a line-end
     * hyphen ("inter-" / "national") is rejoined; a genuine compound split
     * the same way loses its hyphen, which is the lesser evil.
     */
    private function joinWrapped(string $text, string $next): string
    {
        if (preg_match('/\p{L}-$/u', $text) === 1 && preg_match('/^\p{Ll}/u', $next) === 1) {
            return substr($text, 0, -1).$next;
        }

        return $text.' '.$next;
    }

    /** @param string[] $lines */
    private function joinLines(array $lines): string
    {
        $text = array_shift($lines);

        foreach ($lines as $line) {
            $text = $this->joinWrapped($text, $line);
        }

        return $text;
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
                $items[count($items) - 1]['text'] = $this->joinWrapped($items[count($items) - 1]['text'], $line);
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

    /**
     * Turns bare URLs into Markdown links and escapes Markdown syntax in the
     * text around them, so PDF text like "snake_case" or "<tag>" renders as
     * written. URLs are cut out first so their own underscores survive.
     */
    private function linkify(string $text): string
    {
        preg_match_all(self::URL_PATTERN, $text, $found, PREG_OFFSET_CAPTURE);

        $out = '';
        $cursor = 0;

        foreach ($found[0] as [$match, $offset]) {
            $url = rtrim($match, '.,;:!?');
            $trailing = substr($match, strlen($url));

            $out .= $this->escapeInline(substr($text, $cursor, $offset - $cursor));
            $out .= '['.$url.']('.$url.')'.$trailing;
            $cursor = $offset + strlen($match);
        }

        return $out.$this->escapeInline(substr($text, $cursor));
    }

    private function escapeInline(string $text): string
    {
        return preg_replace('/([\\\\`*_\[\]<])/', '\\\\$1', $text);
    }

    /** Text that merely starts with "#", ">" or "+ " must not turn into a heading, quote or list. */
    private function escapeBlockStart(string $text): string
    {
        return preg_replace('/^(#{1,6}(?=\s|$)|>|\+(?=\s))/u', '\\\\$1', $text);
    }
}
