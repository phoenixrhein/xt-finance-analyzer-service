<?php

namespace de\xovatec\financeAnalyzer\Services\Rule;

use ArrayIterator;
use de\xovatec\financeAnalyzer\Enums\ConditionType;
use de\xovatec\financeAnalyzer\Enums\LogicalOperator;
use de\xovatec\financeAnalyzer\Dto\FinQuery\Condition;
use de\xovatec\financeAnalyzer\Dto\FinQuery\ConditionList;
use de\xovatec\financeAnalyzer\Exceptions\ExpressionSyntaxException;
use de\xovatec\financeAnalyzer\Services\Rule\Expression\ExpressionBuilder;
use de\xovatec\financeAnalyzer\Services\Rule\Expression\ExpressionSyntaxParser;

class RuleToConditionTransformer
{
    /**
     *
     * @param ExpressionSyntaxParser $expressionSyntaxParser
     * @param ExpressionBuilder $expressionBuilder
     */
    public function __construct(
        private ExpressionSyntaxParser $expressionSyntaxParser,
        private ExpressionBuilder $expressionBuilder
    ) {
    }

    /**
     *
     * @param ConditionList $conditionList
     * @return array
     */
    public function transformToArray(ConditionList $conditionList): array
    {
        return $this->transformByArrayIterator($conditionList->getIterator(), $conditionList->getLogicalOperator());
    }

    /**
     *
     * @param ArrayIterator $conditionIterator
     * @param LogicalOperator|null $logicalOperator
     * @return array|null
     */
    private function transformByArrayIterator(
        ArrayIterator $conditionIterator,
        ?LogicalOperator $logicalOperator
    ): ?array {
        if ($conditionIterator->valid() === false) {
            return null;
        }
        $condition = $conditionIterator->current();

        if ($condition instanceof Condition) {
            $conditionData = [
                'conditionType' => ConditionType::condition,
                'condition' => [
                    'field' => $condition->getField()->getColumn(),
                    'comparer' => $condition->getOperator()->getFinQueryOperator(),
                    'value' => $condition->getValue()
                ],
                'logicOperator' => $logicalOperator?->value,
                'linkTo' => null
            ];
        } elseif ($condition instanceof ConditionList) {
            $conditionData = [
                'conditionType' => ConditionType::group,
                'condition' => $this->transformToArray($condition),
                'logicOperator' => $logicalOperator?->value,
                'linkTo' => null
            ];
        } else {
            throw new ExpressionSyntaxException('No valid condition type given: ' . get_class($condition));
        }

        if ($logicalOperator !== null) {
            $conditionIterator->next();
            $conditionData['linkTo'] = $this->transformByArrayIterator($conditionIterator, $logicalOperator);
        }

        if ($conditionData['linkTo'] === null) {
            $conditionData['logicOperator'] = null;
        }

        return $conditionData;
    }

        /**
     *
     * @param array $conditionLink
     * @return ConditionList
     */
    public function transformToConditionList(array $conditionLink): ConditionList
    {
        return $this->expressionSyntaxParser->parse(
            $this->expressionBuilder->build($conditionLink)
        );
    }
}
