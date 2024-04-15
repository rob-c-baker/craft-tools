<?php declare(strict_types=1);

namespace alanrogers\tools\twig\perversion\extensions;

use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class BreakTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): BreakNode
    {
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new BreakNode(array(), array(), $token->getLine(), $this->getTag());
    }

    public function getTag(): string
    {
        return 'break';
    }
}