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
     */
    public function __construct(
        private BaseField $field,
        private BaseOperator $operator,
        private string $value
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
}
