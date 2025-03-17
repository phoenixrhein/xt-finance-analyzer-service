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
     * @return void
     */
    public static function build(Builder $query, ConditionList $conditions): void
    {
        $query->where(function ($query) use ($conditions) {
            self::buildConditions($query, $conditions);
        });
    }

    /**
     * @param Builder $query
     * @param ConditionList $conditions
     * @return void
     */
    private static function buildConditions(Builder $query, ConditionList $conditions): void
    {
        foreach ($conditions as $condition) {
            if ($condition instanceof ConditionList) {
                if ($conditions->getLogicalOperator()->value === 'OR') {
                    $query->orWhere(function ($query) use ($condition) {
                        self::buildConditions($query, $condition);
                    });
                } else {
                    $query->where(function ($query) use ($condition) {
                        self::buildConditions($query, $condition);
                    });
                }
                continue;
            }
            /** @var Condition $condition */
            if ($conditions->getLogicalOperator()->value !== 'OR') {
                $query->where(
                    $condition->getField()->getColumn(),
                    $condition->getOperator()->getSqlOperator(),
                    $condition->getOperator()->getSqlValue($condition->getValue())
                );
            } else {
                $query->orWhere(
                    $condition->getField()->getColumn(),
                    $condition->getOperator()->getSqlOperator(),
                    $condition->getOperator()->getSqlValue($condition->getValue())
                );
            }
        }
    }
}
