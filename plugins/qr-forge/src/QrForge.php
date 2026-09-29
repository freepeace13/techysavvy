<?php

namespace Techysavvy\QrForge;

use Techysavvy\Core\ToolContract;

class QrForge implements ToolContract
{
    public function icon(): string
    {
        return '🔳';
    }

    public function name(): string
    {
        return 'QR Forge';
    }

    public function description(): string
    {
        return 'Turn any text or URL into a QR code you can download as PNG or SVG.';
    }

    public function url(): string
    {
        return route('qr-forge.home');
    }
}
