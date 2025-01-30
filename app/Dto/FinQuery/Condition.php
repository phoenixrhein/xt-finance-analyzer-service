<?php

namespace de\xovatec\financeAnalyzer\Dto\FinQuery;

use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\BaseField;
use de\xovatec\financeAnalyzer\Services\FinQuery\Operators\BaseOperator;

class Condition
{
    /**
     * @param BaseField $field
     * @param BaseOperator $operator
     * @param string $value
     * @param string $logicalOperator
     */
    public function __construct(
        private BaseField $field,
        private BaseOperator $operator,
        private string $value,
        private string $logicalOperator
    ) {
    }

    /**
     * @return BaseField
     */
    public function getField(): BaseField
    {
        return $this->field;
    }

    /**
     * @return BaseOperator
     */
    public function getOperator(): BaseOperator
    {
        return $this->operator;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getLogicalOperator(): string
    {
        return $this->logicalOperator;
    }
}
