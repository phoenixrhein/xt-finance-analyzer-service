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
        foreach ($conditions as $condition) {
            if ($condition instanceof ConditionList) {
                $query .= '(' . $this->build($condition) . ') ';
                continue;
            }
            if (Str::length($query) > 0) {
                $query .= $conditions->getLogicalOperator()->value . ' ';
            }
            /** @var Condition $condition */
            $query .= $condition->getField()->getColumn() . ' ' . $condition->getOperator()->getFinQueryOperator() .
                " '" . $condition->getValue() . "' ";
        }
        return trim($query);
    }
}
