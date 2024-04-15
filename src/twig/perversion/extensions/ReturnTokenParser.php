<?php declare(strict_types=1);

namespace alanrogers\tools\twig\perversion\extensions;

use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class ReturnTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): ReturnNode
    {
        $stream = $this->parser->getStream(); // entire stream of tokens
        $nodes = array();

        if (!$stream->test(Token::BLOCK_END_TYPE)) {
            $nodes['expr'] = $this->parser->getExpressionParser()->parseExpression();
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new ReturnNode($nodes, array(), $token->getLine(), $this->getTag());
    }

    public function getTag(): string
    {
        return 'return';
    }
}