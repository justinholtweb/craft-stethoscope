<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Twig;

use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * {% stethoscope 'related entries' %} … {% endstethoscope %}
 */
class StethoscopeTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $stream = $this->parser->getStream();
        $label = $this->parser->parseExpression();
        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse(fn (Token $token): bool => $token->test('endstethoscope'), true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new StethoscopeNode($label, $body, $token->getLine());
    }

    public function getTag(): string
    {
        return 'stethoscope';
    }
}
