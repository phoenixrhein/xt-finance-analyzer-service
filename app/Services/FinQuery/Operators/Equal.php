<?php

namespace de\xovatec\financeAnalyzer\Services\FinQuery\Operators;

class Equal extends BaseOperator
{
    /**
     *
     * @return string
     */
    public function getId(): string
    {
        return 'equal';
    }

    /**
     *
     * @return string
     */
    public function getFinQueryOperator(): string
    {
        return '=';
    }
}
