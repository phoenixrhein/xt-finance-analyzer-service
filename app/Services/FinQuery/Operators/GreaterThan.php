<?php

namespace de\xovatec\financeAnalyzer\Services\FinQuery\Operators;

class GreaterThan extends BaseOperator
{
    /**
     *
     * @return string
     */
    public function getId(): string
    {
        return 'greater_than';
    }

    /**
     *
     * @return string
     */
    public function getFinQueryOperator(): string
    {
        return '>';
    }
}
