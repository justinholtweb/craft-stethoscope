<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Twig;

use Illuminate\Support\Facades\App;
use JustinHoltWeb\Stethoscope\Recorders\Timings;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;

#[YieldReady]
class StethoscopeNode extends Node
{
    public function __construct(AbstractExpression $label, Node $body, int $lineno)
    {
        parent::__construct(['label' => $label, 'body' => $body], [], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        $timings = '\\'.App::class.'::make(\\'.Timings::class.'::class)';

        $compiler
            ->addDebugInfo($this)
            ->write($timings.'->start(')
            ->subcompile($this->getNode('label'))
            ->raw(");\n")
            ->write("try {\n")
            ->indent()
            ->subcompile($this->getNode('body'))
            ->outdent()
            ->write("} finally {\n")
            ->indent()
            ->write($timings."->stop();\n")
            ->outdent()
            ->write("}\n");
    }
}
