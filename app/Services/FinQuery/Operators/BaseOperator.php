<?php

namespace de\xovatec\financeAnalyzer\Services\FinQuery\Operators;

abstract class BaseOperator
{
    /**
     *
     * @return string
     */
    abstract public function getId(): string;

    /**
     *
     * @return string
     */
    abstract public function getFinQueryOperator(): string;

    /**
     *
     * @return string
     */
    public function getSqlOperator(): string
    {
        return $this->getFinQueryOperator();
    }

    /**
     *
     * @param string $value
     * @return string
     */
    public function getSqlValue(string $value): string
    {
        return $value;
    }
}
