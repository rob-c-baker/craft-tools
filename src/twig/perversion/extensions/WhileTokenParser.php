<?php declare(strict_types=1);

namespace alanrogers\tools\twig\perversion\extensions;

use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class WhileTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): WhileNode
    {
        $lineno = $token->getLine();
        $parser = $this->parser;
        $stream = $parser->getStream();
        $condition = $parser->getExpressionParser()->parseExpression();
        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $parser->subparse([ $this, 'decideWhileEnd' ]);

        $stream->next();

        $stream->expect(Token::BLOCK_END_TYPE);

        return new WhileNode($condition, $body, $lineno, $this->getTag());
    }

    public function decideWhileEnd(Token $token): bool
    {
        return $token->test([ 'endwhile' ]);
    }

    public function getTag(): string
    {
        return 'while';
    }
}