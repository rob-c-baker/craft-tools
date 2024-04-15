<?php declare(strict_types=1);

namespace alanrogers\tools\twig\perversion\extensions;

use Twig\Compiler;
use Twig\Node\Node;

class ReturnNode extends Node
{
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write('return ');

        if ($this->hasNode('expr')) {
            $compiler->subcompile($this->getNode('expr'));
        } else {
            $compiler->raw('""');
        }

        $compiler->raw(";\n");
    }
}