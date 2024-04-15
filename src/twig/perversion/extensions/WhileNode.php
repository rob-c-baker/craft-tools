<?php declare(strict_types=1);

namespace alanrogers\tools\twig\perversion\extensions;

use Twig\Compiler;
use Twig\Node\ForLoopNode;
use Twig\Node\Node;

class WhileNode extends Node
{
    private ForLoopNode $loop;

    public function __construct(Node $condition, Node $body, int $lineno, string $tag = null)
    {
        $body = new Node([
            $body,
            $this->loop = new ForLoopNode($lineno, $tag)
        ]);
        $nodes = [
            'body' => $body,
            'condition' => $condition
        ];
        parent::__construct($nodes, [ 'with_loop' => true ], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write("\$context['_parent'] = \$context;\n");

        if ($this->getAttribute('with_loop')) {
            $compiler
                ->write("\$context['loop'] = [\n")
                ->write("  'parent' => \$context['_parent'],\n")
                ->write("  'index0' => 0,\n")
                ->write("  'index'  => 1,\n")
                ->write("  'first'  => true,\n")
                ->write("];\n")
            ;
        }

        $this->loop->setAttribute('with_loop', $this->getAttribute('with_loop'));

        $compiler
            ->write('while (')
            ->subcompile($this->getNode('condition'))
            ->raw(") {\n")
            ->indent()
                // this is a ForLoopNode, so it updates the loop stuff
                ->subcompile($this->getNode('body'))
            ->outdent()
            ->write("}\n")
        ;

        $compiler->write("\$_parent = \$context['_parent'];\n");

        // remove some "private" loop variables (needed for nested loops)
        $compiler->write('unset($context[\'_parent\'], $context[\'loop\']);'."\n");

        // keep the values set in the inner context for variables defined in the outer context
        $compiler->write("\$context = array_intersect_key(\$context, \$_parent) + \$_parent;\n");
    }
}