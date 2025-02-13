<?php

namespace de\xovatec\financeAnalyzer\Services;

use de\xovatec\financeAnalyzer\Dto\FinQuery\Condition;
use de\xovatec\financeAnalyzer\Dto\FinQuery\ConditionList;
use de\xovatec\financeAnalyzer\Services\FinQuery\FieldConfig;

class RuleToConditionTransformer
{
    /**
     *
     * @param array $ruleCondition
     * @return ConditionList
     */
    public function transform(array $ruleCondition): ConditionList
    {
        $list = new ConditionList();
        $this->transformCondition($ruleCondition, $list);
        return $list;
    }

    /**
     *
     * @param array $rule
     * @param ConditionList $list
     * @param string $logicalOperator
     * @return void
     */
    private function transformCondition(array $rule, ConditionList $list, string $logicalOperator = ''): void
    {
        $field = FieldConfig::getFieldByColumnKey($rule['condition']['field']);
        $operator = FieldConfig::getOperatorClass($field, $rule['condition']['comparer']);
        $condition = new Condition($field, $operator, $rule['condition']['value'], $logicalOperator);
        $list->add($condition);
        if ($rule['linkTo'] !== null) {
            $this->transformCondition($rule['linkTo'], $list, $rule['logicOperator']);
        }
    }
}
