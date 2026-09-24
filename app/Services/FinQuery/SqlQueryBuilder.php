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
        $hasJoins = $query->getQuery()->joins !== null;
        $query->where(function ($query) use ($conditions, $hasJoins) {
            self::buildConditions($query, $conditions, $hasJoins);
        });
    }

    /**
     * @param Builder $query
     * @param ConditionList $conditions
     * @param bool $hasJoins
     * @return void
     */
    private static function buildConditions(Builder $query, ConditionList $conditions, bool $hasJoins): void
    {
        $column = static function (string $field) use ($hasJoins): string {
            return $hasJoins ? 'transactions.' . $field : $field;
        };

        foreach ($conditions as $condition) {
            if ($condition instanceof ConditionList) {
                if ($conditions->getLogicalOperator()->value === 'OR') {
                    $query->orWhere(function ($query) use ($condition, $hasJoins) {
                        self::buildConditions($query, $condition, $hasJoins);
                    });
                } else {
                    $query->where(function ($query) use ($condition, $hasJoins) {
                        self::buildConditions($query, $condition, $hasJoins);
                    });
                }
                continue;
            }
            /** @var Condition $condition */
            if ($conditions->getLogicalOperator()->value !== 'OR') {
                $query->where(
                    $column($condition->getField()->getColumn()),
                    $condition->getOperator()->getSqlOperator(),
                    $condition->getOperator()->getSqlValue($condition->getValue())
                );
            } else {
                $query->orWhere(
                    $column($condition->getField()->getColumn()),
                    $condition->getOperator()->getSqlOperator(),
                    $condition->getOperator()->getSqlValue($condition->getValue())
                );
            }
        }
    }
}
