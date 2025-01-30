<?php

namespace de\xovatec\financeAnalyzer\Services\FinQuery\Operators;

class LessThan extends BaseOperator
{
    /**
     *
     * @return string
     */
    public function getId(): string
    {
        return 'less_than';
    }

    /**
     *
     * @return string
     */
    public function getFinQueryOperator(): string
    {
        return '<';
    }
}
