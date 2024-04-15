<?php declare(strict_types=1);

namespace alanrogers\tools\twig\perversion\extensions;

use Twig\Compiler;
use Twig\Node\Node;

class ContinueNode extends Node
{
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write("if (array_key_exists('loop', \$context)) {\n")
            ->indent()
                ->write("++\$context['loop']['index0'];\n")
                ->write("++\$context['loop']['index'];\n")
                ->write("\$context['loop']['first'] = false;\n")
                ->write("if (isset(\$context['loop']['length'])) {\n")
                ->indent()
                    ->write("--\$context['loop']['revindex0'];\n")
                    ->write("--\$context['loop']['revindex'];\n")
                    ->write("\$context['loop']['last'] = 0 === \$context['loop']['revindex0'];\n")
                ->outdent()
                ->write("}\n")
            ->outdent()
            ->write("}\n")
            ->write("continue;\n");
    }
}