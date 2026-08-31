<?php

namespace Techysavvy\DropShare\Services;

use Drenso\GenPhrase\Password;

class PhraseGenerator
{
    /**
     * 40 bits of entropy against GenPhrase's ~4096-word (12 bits/word)
     * default english wordlist reliably yields exactly 4 words
     * (ceil(40 / 12) = 4), matching this tool's phrase-strength decision.
     */
    private const ENTROPY_BITS = 40.0;

    public function generate(): string
    {
        $generator = new Password();
        $generator->disableWordModifier(true);
        $generator->setSeparators('-');
        $generator->alwaysUseSeparators(true);

        return $generator->generate(self::ENTROPY_BITS);
    }
}
