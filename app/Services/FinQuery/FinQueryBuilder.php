<?php

namespace de\xovatec\financeAnalyzer\Services\FinQuery;

use Illuminate\Support\Str;
use de\xovatec\financeAnalyzer\Dto\FinQuery\Condition;
use de\xovatec\financeAnalyzer\Dto\FinQuery\ConditionList;

class FinQueryBuilder
{
    /**
     * @param ConditionList $conditions
     * @return string
     */
    public function build(ConditionList $conditions): string
    {
        $query = '';
        foreach ($conditions as $index => $condition) {
            if ($condition instanceof ConditionList) {
                $query .= '(' . $this->build($condition) . ') ';
            } else {
                /** @var Condition $condition */
                $query .= $condition->getField()->getColumn() . ' ' . $condition->getOperator()->getFinQueryOperator() .
                    " '" . $condition->getValue() . "' ";
            }
            if (Str::length($query) > 0 && $index !== array_key_last($conditions->all())) {
                $query .= $conditions->getLogicalOperator()->value . ' ';
            }

        }
        return trim($query);
    }
}
