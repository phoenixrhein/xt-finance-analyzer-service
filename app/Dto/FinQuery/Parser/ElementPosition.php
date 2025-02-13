<?php

namespace de\xovatec\financeAnalyzer\Dto\FinQuery\Parser;

use Illuminate\Support\Str;

class ElementPosition
{
    /**
     *
     * @param string $sectionExpression
     * @param integer $position
     * @param string|null $element
     */
    public function __construct(private string $sectionExpression, private int $position, private ?string $element = null)
    {
        if ($element === null) {
            $this->element = $sectionExpression;
        }
    }

    /**
     *
     * @return integer
     */
    public function getFrom(): int
    {
        return Str::position($this->sectionExpression, $this->element) + $this->position;
    }

    /**
     *
     * @return integer
     */
    public function getTo(): int
    {
        return $this->getFrom() + Str::length($this->element);
    }
}
