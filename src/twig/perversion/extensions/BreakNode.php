<?php

namespace alanrogers\tools\twig\perversion\extensions;

use Twig\Compiler;
use Twig\Node\Node;

class BreakNode extends Node
{
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write("break;\n");
    }
}