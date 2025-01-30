<?php

namespace de\xovatec\financeAnalyzer\Services\FinQuery;

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
            /** @var Condition $condition */
            $query .= $condition->getLogicalOperator() . ' ' . $condition->getField()->getColumn() . ' ' . $condition->getOperator()->getFinQueryOperator() . " '" . $condition->getValue() . "' ";
        }
        return trim($query);
    }
}
