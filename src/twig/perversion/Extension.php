<?php declare(strict_types=1);

namespace alanrogers\tools\twig\perversion;

use alanrogers\tools\twig\perversion\extensions\ArrayTest;
use alanrogers\tools\twig\perversion\extensions\BinaryEquivalentExpression;
use alanrogers\tools\twig\perversion\extensions\BinaryNotEquivalentExpression;
use alanrogers\tools\twig\perversion\extensions\BinarySpaceshipExpression;
use alanrogers\tools\twig\perversion\extensions\BreakTokenParser;
use alanrogers\tools\twig\perversion\extensions\ContinueTokenParser;
use alanrogers\tools\twig\perversion\extensions\NumericTest;
use alanrogers\tools\twig\perversion\extensions\ReturnTokenParser;
use alanrogers\tools\twig\perversion\extensions\StringTest;
use alanrogers\tools\twig\perversion\extensions\WhileTokenParser;
use Twig\ExpressionParser;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

class Extension extends AbstractExtension
{
    public function getTokenParsers(): array
    {
        return [
            new BreakTokenParser(),
            new WhileTokenParser(),
            new ContinueTokenParser(),
            new ReturnTokenParser(),
        ];
    }

    public function getTests(): array
    {
        return [
            new TwigTest('numeric', null, [ 'node_class' => NumericTest::class ]),
            new TwigTest('string', null, [ 'node_class' => StringTest::class ]),
            new TwigTest('array', null, [ 'node_class' => ArrayTest::class ]),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('array_splice', function(array $input, int $offset, int $length = null, $replacement = null) {
                if (is_null($length)) {
                    $length = count($input);
                }
                if (is_null($replacement)) {
                    $replacement = [];
                }
                $extracted = array_splice($input, $offset, $length, $replacement);
                return $input;
            }),

            new TwigFilter('string', [ $this, 'string' ]),
            new TwigFilter('float',  [ $this, 'float' ]),
            new TwigFilter('int',    [ $this, 'int' ]),
            new TwigFilter('bool',   [ $this, 'bool' ]),
        ];
    }

    public function getOperators(): array
    {
        return [
            [],
            [
                '===' => [
                    'precedence' => 20,
                    'class' => BinaryEquivalentExpression::class,
                    'associativity' => ExpressionParser::OPERATOR_LEFT,
                ],
                '!==' => [
                    'precedence' => 20,
                    'class' => BinaryNotEquivalentExpression::class,
                    'associativity' => ExpressionParser::OPERATOR_LEFT,
                ],
                '<=>' => [
                    'precedence' => 20,
                    'class' => BinarySpaceshipExpression::class,
                    'associativity' => ExpressionParser::OPERATOR_LEFT,
                ],
            ],
        ];
    }

    public function string($subject): string
    {
        return (string) $subject;
    }

    public function float($subject): float
    {
        return (float) $subject;
    }

    public function int($subject): int
    {
        return (int) $subject;
    }

    public function bool($subject): bool
    {
        return (bool) $subject;
    }
}