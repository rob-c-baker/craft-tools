<?php declare(strict_types=1);

namespace alanrogers\tools\twig\perversion\extensions;

use Twig\Compiler;

class BinarySpaceshipExpression extends \Twig\Node\Expression\Binary\AbstractBinary
{
    public function operator(Compiler $compiler): Compiler
    {
        return $compiler->raw('<=>');
    }
}