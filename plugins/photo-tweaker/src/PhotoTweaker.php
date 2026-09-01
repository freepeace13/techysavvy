<?php

namespace Techysavvy\PhotoTweaker;

use Techysavvy\Core\ToolContract;

class PhotoTweaker implements ToolContract
{
    public function icon(): string
    {
        return '🖼️';
    }

    public function name(): string
    {
        return 'Photo Tweaker';
    }

    public function description(): string
    {
        return 'Crop, rotate, resize, and flip an uploaded image, then export it in a different format.';
    }

    public function url(): string
    {
        return route('photo-tweaker.home');
    }
}
