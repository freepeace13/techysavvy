<?php

namespace Techysavvy\DocToMarkdown\Converters;

use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\Image;
use PhpOffice\PhpWord\Element\Link;
use PhpOffice\PhpWord\Element\ListItem;
use PhpOffice\PhpWord\Element\ListItemRun;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Title;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style;
use PhpOffice\PhpWord\Style\Numbering;

class DocxMarkdownWriter
{
    private const IMAGE_PLACEHOLDER = '*[image omitted]*';

    // Nested list content must sit under the parent's marker; three columns
    // clears both "- " and "1. ".
    private const LIST_INDENT = '   ';

    public function write(PhpWord $phpWord): string
    {
        $blocks = [];

        foreach ($phpWord->getSections() as $section) {
            $blocks = array_merge($blocks, $this->writeContainer($section));
        }

        return implode("\n\n", array_filter($blocks, fn (string $block) => $block !== ''));
    }

    /** @return string[] */
    private function writeContainer(AbstractContainer $container): array
    {
        $blocks = [];
        $listGroup = [];

        foreach (array_values($container->getElements()) as $element) {
            if ($element instanceof ListItem || $element instanceof ListItemRun) {
                $listGroup[] = $element;

                continue;
            }

            if ($listGroup !== []) {
                $blocks[] = $this->writeList($listGroup);
                $listGroup = [];
            }

            $blocks[] = $this->writeBlock($element);
        }

        if ($listGroup !== []) {
            $blocks[] = $this->writeList($listGroup);
        }

        return $blocks;
    }

    private function writeBlock(mixed $element): string
    {
        return match (true) {
            $element instanceof Title => $this->writeTitle($element),
            $element instanceof TextRun => $this->escapeBlockStart($this->inlineMarkdown($element)),
            $element instanceof Text => $this->escapeBlockStart($this->inlineMarkdown([$element])),
            $element instanceof Table => $this->writeTable($element),
            $element instanceof Image => self::IMAGE_PLACEHOLDER,
            $element instanceof Link => $this->writeLink($element),
            default => '',
        };
    }

    private function writeTitle(Title $title): string
    {
        $depth = max(1, min(6, $title->getDepth() ?: 1));
        $text = $title->getText();
        $text = is_string($text) ? $this->escapeInline($text) : $this->inlineMarkdown($text);

        return str_repeat('#', $depth).' '.trim($text);
    }

    /**
     * Renders a run (or a list of inline elements) as Markdown. Word splits
     * one visual phrase into many runs, so adjacent runs sharing a style are
     * merged first — otherwise "**a****b**" comes out, which Markdown does
     * not treat as one bold span.
     *
     * @param  TextRun|array<int, mixed>  $source
     */
    private function inlineMarkdown(TextRun|array $source): string
    {
        $elements = $source instanceof TextRun ? $source->getElements() : $source;
        $segments = [];

        foreach ($elements as $element) {
            if ($element instanceof Text) {
                [$bold, $italic] = $this->emphasisOf($element);
                $last = count($segments) - 1;

                if ($last >= 0 && $segments[$last]['bold'] === $bold && $segments[$last]['italic'] === $italic) {
                    $segments[$last]['text'] .= $element->getText();
                } else {
                    $segments[] = ['text' => $element->getText(), 'bold' => $bold, 'italic' => $italic, 'raw' => false];
                }
            } elseif ($element instanceof Image) {
                $segments[] = ['text' => self::IMAGE_PLACEHOLDER, 'bold' => false, 'italic' => false, 'raw' => true];
            } elseif ($element instanceof Link) {
                $segments[] = ['text' => $this->writeLink($element), 'bold' => false, 'italic' => false, 'raw' => true];
            }
        }

        return implode('', array_map(fn (array $segment) => $this->renderSegment($segment), $segments));
    }

    /** @param array{text: string, bold: bool, italic: bool, raw: bool} $segment */
    private function renderSegment(array $segment): string
    {
        if ($segment['raw']) {
            return $segment['text'];
        }

        $text = $this->escapeInline($segment['text']);

        if (! $segment['bold'] && ! $segment['italic']) {
            return $text;
        }

        // Emphasis markers must hug the text: "** foo **" is not bold. Keep
        // any edge whitespace outside the markers.
        if (trim($text) === '') {
            return $text;
        }

        $marker = match (true) {
            $segment['bold'] && $segment['italic'] => '***',
            $segment['bold'] => '**',
            default => '*',
        };

        preg_match('/^(\s*)(.*?)(\s*)$/su', $text, $parts);

        return $parts[1].$marker.$parts[2].$marker.$parts[3];
    }

    /** @return array{bool, bool} */
    private function emphasisOf(Text $text): array
    {
        $fontStyle = $text->getFontStyle();

        if (is_string($fontStyle)) {
            $fontStyle = Style::getStyle($fontStyle);
        }

        if (! is_object($fontStyle)) {
            return [false, false];
        }

        return [(bool) $fontStyle->isBold(), (bool) $fontStyle->isItalic()];
    }

    private function writeLink(Link $link): string
    {
        $text = $link->getText();
        $label = $this->escapeInline(is_string($text) && $text !== '' ? $text : $link->getSource());
        $url = str_replace([' ', '(', ')'], ['%20', '%28', '%29'], $link->getSource());

        return '['.$label.']('.$url.')';
    }

    private function escapeInline(string $text): string
    {
        return preg_replace('/([\\\\`*_\[\]<])/', '\\\\$1', $text);
    }

    /** A paragraph that merely starts with "#", "1." or "- " must not become a heading or list. */
    private function escapeBlockStart(string $text): string
    {
        return preg_replace('/^(\s*)(#{1,6}(?=\s|$)|>|[-+](?=\s)|\d+(?=[.)]\s))/u', '$1\\\\$2', $text);
    }

    private function isOrdered(ListItem|ListItemRun $item): bool
    {
        $name = $item->getStyle()->getNumStyle();
        $numStyle = $name === null ? null : Style::getStyle($name);

        if (! $numStyle instanceof Numbering) {
            return false;
        }

        $level = $numStyle->getLevels()[$item->getDepth()] ?? null;

        return $level !== null && $level->getFormat() !== 'bullet';
    }

    private function listItemText(ListItem|ListItemRun $item): string
    {
        if ($item instanceof ListItemRun) {
            return $this->inlineMarkdown($item);
        }

        $text = $item->getTextObject()?->getText() ?? '';

        return $this->escapeInline(is_string($text) ? $text : '');
    }

    /**
     * One block per uninterrupted run of list items, so nested items stay
     * inside their parent list and ordered numbering continues after a
     * nested list ends rather than restarting.
     *
     * @param  array<int, ListItem|ListItemRun>  $items
     */
    private function writeList(array $items): string
    {
        $lines = [];
        $counters = []; // depth => ['ordered' => bool, 'n' => int]

        foreach ($items as $item) {
            $depth = $item->getDepth();
            $ordered = $this->isOrdered($item);

            // Coming back up closes every deeper list.
            foreach (array_keys($counters) as $open) {
                if ($open > $depth) {
                    unset($counters[$open]);
                }
            }

            if (isset($counters[$depth]) && $counters[$depth]['ordered'] === $ordered) {
                $counters[$depth]['n']++;
            } else {
                // Switching between bullets and numbers starts a new list,
                // which Markdown needs a blank line to see.
                if (isset($counters[$depth]) && $lines !== []) {
                    $lines[] = '';
                }

                $counters[$depth] = ['ordered' => $ordered, 'n' => 1];
            }

            $marker = $ordered ? $counters[$depth]['n'].'.' : '-';
            $lines[] = str_repeat(self::LIST_INDENT, $depth).$marker.' '.$this->listItemText($item);
        }

        return implode("\n", $lines);
    }

    private function writeTable(Table $table): string
    {
        $rows = [];

        foreach ($table->getRows() as $row) {
            $cells = [];

            foreach ($row->getCells() as $cell) {
                $cells[] = str_replace('|', '\\|', $this->cellText($cell));

                // A merged cell spans several columns; pad so the rows line up.
                $span = (int) ($cell->getStyle()?->getGridSpan() ?? 1);
                for ($i = 1; $i < $span; $i++) {
                    $cells[] = '';
                }
            }

            $rows[] = $cells;
        }

        $columns = $rows === [] ? 0 : max(array_map('count', $rows));

        if ($columns === 0) {
            return '';
        }

        $rows = array_map(fn (array $cells) => array_pad($cells, $columns, ''), $rows);

        $lines = ['| '.implode(' | ', $rows[0]).' |'];
        $lines[] = '| '.implode(' | ', array_fill(0, $columns, '---')).' |';
        for ($r = 1; $r < count($rows); $r++) {
            $lines[] = '| '.implode(' | ', $rows[$r]).' |';
        }

        return implode("\n", $lines);
    }

    /** A cell is one Markdown line, so its paragraphs are flattened to a single string. */
    private function cellText(AbstractContainer $cell): string
    {
        $parts = [];

        foreach ($cell->getElements() as $element) {
            $text = match (true) {
                $element instanceof ListItem, $element instanceof ListItemRun => $this->listItemText($element),
                $element instanceof TextRun, $element instanceof Text, $element instanceof Link, $element instanceof Image => $this->writeBlock($element),
                $element instanceof Table => $this->flattenTable($element),
                default => '',
            };

            if (trim($text) !== '') {
                $parts[] = trim($text);
            }
        }

        return trim(preg_replace('/\s+/u', ' ', implode(' ', $parts)));
    }

    private function flattenTable(Table $table): string
    {
        $parts = [];

        foreach ($table->getRows() as $row) {
            foreach ($row->getCells() as $cell) {
                $parts[] = $this->cellText($cell);
            }
        }

        return implode(' ', array_filter($parts, fn (string $part) => $part !== ''));
    }
}
