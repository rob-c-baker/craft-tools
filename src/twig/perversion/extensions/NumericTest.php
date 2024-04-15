<?php declare(strict_types=1);

namespace alanrogers\tools\twig\perversion\extensions;

use Twig\Compiler;
use Twig\Node\Expression\TestExpression;

class NumericTest extends TestExpression
{
    public function compile(Compiler $compiler): void
    {
        $compiler->raw('is_numeric(')
            ->subcompile($this->getNode('node'))
            ->raw(')');
    }
}