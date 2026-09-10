<?php

namespace Techysavvy\DocToMarkdown\Converters;

use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\Image;
use PhpOffice\PhpWord\Element\Link;
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
        $elements = array_values($container->getElements());
        $rendered = [];

        foreach ($elements as $element) {
            if ($element instanceof Title) {
                $rendered[] = $this->writeTitle($element);
            } elseif ($element instanceof ListItemRun) {
                $rendered[] = $this->plainTextFromRun($element);
            } elseif ($element instanceof TextRun) {
                $rendered[] = $this->plainTextFromRun($element);
            } elseif ($element instanceof Table) {
                $rendered[] = $this->writeTable($element);
            } elseif ($element instanceof Image) {
                $rendered[] = '*[image omitted]*';
            } elseif ($element instanceof Link) {
                $rendered[] = $this->writeLink($element);
            } else {
                $rendered[] = '';
            }
        }

        return $this->mergeConsecutiveListItems($elements, $rendered);
    }

    private function writeTitle(Title $title): string
    {
        $depth = max(1, min(6, $title->getDepth() ?: 1));
        $text = $title->getText();
        $text = is_string($text) ? $text : $this->plainTextFromRun($text);

        return str_repeat('#', $depth).' '.trim($text);
    }

    private function plainTextFromRun(TextRun $run): string
    {
        $parts = [];

        foreach ($run->getElements() as $element) {
            if ($element instanceof Text) {
                $parts[] = $this->formatRunText($element);
            } elseif ($element instanceof Image) {
                $parts[] = '*[image omitted]*';
            } elseif ($element instanceof Link) {
                $parts[] = $this->writeLink($element);
            }
        }

        return implode('', $parts);
    }

    private function writeLink(Link $link): string
    {
        return '['.$link->getText().']('.$link->getSource().')';
    }

    private function formatRunText(Text $text): string
    {
        $value = $text->getText();
        $fontStyle = $text->getFontStyle();

        $bold = is_object($fontStyle) && $fontStyle->isBold();
        $italic = is_object($fontStyle) && $fontStyle->isItalic();

        return match (true) {
            $bold && $italic => '***'.$value.'***',
            $bold => '**'.$value.'**',
            $italic => '*'.$value.'*',
            default => $value,
        };
    }

    private function isOrdered(ListItemRun $item): bool
    {
        $numStyleObj = Style::getStyle($item->getStyle()->getNumStyle());

        if (!$numStyleObj instanceof Numbering) {
            return false;
        }

        $level = $numStyleObj->getLevels()[$item->getDepth()] ?? null;

        return $level !== null && $level->getFormat() !== 'bullet';
    }

    /**
     * @param array<int, mixed> $elements
     * @param string[] $rendered
     * @return string[]
     */
    private function mergeConsecutiveListItems(array $elements, array $rendered): array
    {
        $merged = [];
        $ordinal = [];

        foreach ($elements as $index => $element) {
            if (!$element instanceof ListItemRun) {
                $merged[] = $rendered[$index];

                continue;
            }

            $ordered = $this->isOrdered($element);
            $depth = $element->getDepth();
            $key = $depth.'-'.($ordered ? 'o' : 'u');

            $previousIsSameList = $index > 0
                && $elements[$index - 1] instanceof ListItemRun
                && $this->isOrdered($elements[$index - 1]) === $ordered
                && $elements[$index - 1]->getDepth() === $depth;

            $ordinal[$key] = $previousIsSameList ? $ordinal[$key] + 1 : 1;

            $line = str_repeat('  ', $depth).($ordered ? $ordinal[$key].'.' : '-').' '.$rendered[$index];

            if ($previousIsSameList) {
                $merged[count($merged) - 1] .= "\n".$line;
            } else {
                $merged[] = $line;
            }
        }

        return $merged;
    }

    private function writeTable(Table $table): string
    {
        $rows = [];

        foreach ($table->getRows() as $row) {
            $cells = [];
            foreach ($row->getCells() as $cell) {
                $cellText = [];
                foreach ($cell->getElements() as $cellElement) {
                    if ($cellElement instanceof TextRun) {
                        $cellText[] = $this->plainTextFromRun($cellElement);
                    }
                }
                $cells[] = str_replace('|', '\\|', trim(implode(' ', $cellText)));
            }
            $rows[] = $cells;
        }

        if ($rows === []) {
            return '';
        }

        $lines = ['| '.implode(' | ', $rows[0]).' |'];
        $lines[] = '| '.implode(' | ', array_fill(0, count($rows[0]), '---')).' |';
        for ($r = 1; $r < count($rows); $r++) {
            $lines[] = '| '.implode(' | ', $rows[$r]).' |';
        }

        return implode("\n", $lines);
    }
}
