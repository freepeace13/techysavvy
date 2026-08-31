<?php

namespace Techysavvy\DropShare\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Techysavvy\DropShare\Services\PhraseGenerator;

class PhraseGeneratorTest extends TestCase
{
    public function test_it_generates_a_four_word_lowercase_hyphenated_phrase(): void
    {
        $phrase = (new PhraseGenerator())->generate();

        $this->assertMatchesRegularExpression('/^[a-z]+(-[a-z]+){3}$/', $phrase);
    }

    public function test_it_generates_different_phrases_across_calls(): void
    {
        $generator = new PhraseGenerator();

        $phrases = array_map(fn () => $generator->generate(), range(1, 5));

        $this->assertGreaterThan(1, count(array_unique($phrases)));
    }
}
