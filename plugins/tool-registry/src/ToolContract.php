<?php

namespace Techysavvy\ToolRegistry;

interface ToolContract
{
    public function icon(): string;

    public function name(): string;

    public function description(): string;

    public function url(): string;
}
