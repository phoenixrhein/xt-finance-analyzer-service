<?php

namespace de\xovatec\financeAnalyzer\Services\Rule;

use de\xovatec\financeAnalyzer\Enums\ConditionType;
use de\xovatec\financeAnalyzer\Enums\LogicalOperator;
use de\xovatec\financeAnalyzer\Dto\FinQuery\Condition;
use de\xovatec\financeAnalyzer\Dto\FinQuery\ConditionList;
use de\xovatec\financeAnalyzer\Services\FinQuery\FieldConfig;
use de\xovatec\financeAnalyzer\Services\FinQuery\FinQueryBuilder;
use de\xovatec\financeAnalyzer\Exceptions\ExpressionSyntaxException;
use de\xovatec\financeAnalyzer\Services\Rule\Expression\ExpressionSyntaxParser;

class RuleToConditionTransformer
{
    /**
     *
     * @param ExpressionSyntaxParser $expressionSyntaxParser
     * @param FinQueryBuilder $finQueryBuilder
     */
    public function __construct(
        private ExpressionSyntaxParser $expressionSyntaxParser,
        private FinQueryBuilder $finQueryBuilder,
    ) {
    }

    /**
     *
     * @param ConditionList $conditionList
     * @return array
     */
    public function transformToArray(ConditionList $conditionList): array
    {
        //todo Was wenn Parse einen Fehler liefert?
        return $this->expressionSyntaxParser->parse(
            $this->finQueryBuilder->build($conditionList)
        );
    }

    /**
     *
     * @param array $ruleCondition
     * @return ConditionList
     */
    public function transformToConditionList(array $conditionLink): ConditionList
    {
        $list = new ConditionList(LogicalOperator::tryFrom($conditionLink['logicOperator']));
        $this->transformConditionLink($conditionLink, $list);
        return $list;
    }

    /**
     *
     * @param array $rule
     * @param ConditionList $list
     * @param string $logicalOperator
     * @throws ExpressionSyntaxException
     * @return void
     */
    private function transformConditionLink(array $conditionLink, ConditionList $list): void
    {
        if ($conditionLink['conditionType'] === ConditionType::group) {
            $this->transformGroup($conditionLink, $list);
        } elseif ($conditionLink['conditionType'] === ConditionType::condition) {
            $this->transformCondition($conditionLink, $list);
        } else {
            throw new ExpressionSyntaxException('Invalid condition type');
        }
    }

    /**
     *
     * @param array $conditionLink
     * @param ConditionList $list
     * @return void
     */
    private function transformGroup(array $conditionLink, ConditionList $list): void
    {
        $groupList = new ConditionList(LogicalOperator::tryFrom($conditionLink['condition']['logicOperator']));
        $this->transformConditionLink($conditionLink['condition'], $groupList);
        $list->add($groupList);
        if ($conditionLink['linkTo'] !== null) {
            $this->transformConditionLink($conditionLink['linkTo'], $list);
        }
    }

    /**
     *
     * @param array $condition
     * @param ConditionList $list
     * @return void
     */
    private function transformCondition(array $condition, ConditionList $list): void
    {
        $field = FieldConfig::getFieldByColumnKey($condition['condition']['field']);
        $operator = FieldConfig::getOperatorClass($field, $condition['condition']['comparer']);
        $list->add(new Condition($field, $operator, $condition['condition']['value']));
        if ($condition['linkTo'] !== null) {
            $this->transformConditionLink($condition['linkTo'], $list);
        }
    }
}
