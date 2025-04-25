<?php

namespace de\xovatec\financeAnalyzer\Services\FinQuery\Operators;

class NotEndsWith extends BaseOperator
{
    /**
     *
     * @return string
     */
    public function getId(): string
    {
        return 'not_ends_with';
    }

    /**
     *
     * @return string
     */
    public function getFinQueryOperator(): string
    {
        return '!~?';
    }

    /**
     *
     * @return string
     */
    public function getSqlOperator(): string
    {
        return 'NOT LIKE';
    }

    /**
     *
     * @param string $value
     * @return string
     */
    public function getSqlValue(string $value): string
    {
        return "%" . $value;
    }
}
