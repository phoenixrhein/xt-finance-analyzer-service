<?php

namespace de\xovatec\financeAnalyzer\Services\FinQuery\Operators;

class NotEqual extends BaseOperator
{
    /**
     *
     * @return string
     */
    public function getId(): string
    {
        return 'not_equal';
    }

    /**
     *
     * @return string
     */
    public function getFinQueryOperator(): string
    {
        return '!=';
    }
}
