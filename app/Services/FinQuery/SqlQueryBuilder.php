<?php

namespace de\xovatec\financeAnalyzer\Services\FinQuery;

use Illuminate\Database\Eloquent\Builder;
use de\xovatec\financeAnalyzer\Dto\FinQuery\Condition;
use de\xovatec\financeAnalyzer\Dto\FinQuery\ConditionList;

class SqlQueryBuilder
{
    /**
     * @param Builder $query
     * @param ConditionList $conditions
     * @return Builder
     */
    public static function build(Builder $query, ConditionList $conditions): Builder
    {
        foreach ($conditions as $condition) {
            /** @var Condition $condition */
            if ($condition->getLogicalOperator() !== 'OR') {
                $query->where($condition->getField()->getColumn(), $condition->getOperator()->getSqlOperator(), $condition->getOperator()->getSqlValue($condition->getValue()));
            } else {
                $query->orWhere($condition->getField()->getColumn(), $condition->getOperator()->getSqlOperator(), $condition->getValue());
            }
        }

        return $query;
    }
}
