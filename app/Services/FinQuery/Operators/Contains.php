<?php

namespace de\xovatec\financeAnalyzer\Services\FinQuery\Operators;

class Contains extends BaseOperator
{
    /**
     *
     * @return string
     */
    public function getId(): string
    {
        return 'contains';
    }

    /**
     *
     * @return string
     */
    public function getFinQueryOperator(): string
    {
        return '~';
    }

    /**
     *
     * @return string
     */
    public function getSqlOperator(): string
    {
        return 'LIKE';
    }

    /**
     *
     * @param string $value
     * @return string
     */
    public function getSqlValue(string $value): string
    {
        return "%" . $value . "%";
    }
}
