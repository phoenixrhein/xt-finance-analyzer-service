<?php

namespace de\xovatec\financeAnalyzer\Services\Rule;

use de\xovatec\financeAnalyzer\Models\Rule;
use de\xovatec\financeAnalyzer\Models\Action;
use de\xovatec\financeAnalyzer\Models\Category;
use de\xovatec\financeAnalyzer\Models\Condition;
use de\xovatec\financeAnalyzer\Enums\ConditionType;
use de\xovatec\financeAnalyzer\Models\ConditionLink;
use de\xovatec\financeAnalyzer\Dto\FinQuery\ConditionList;
use de\xovatec\financeAnalyzer\Exceptions\ExpressionSyntaxException;

class RuleDataManager
{
    /**
     *
     * @param RuleToConditionTransformer $transformer
     */
    public function __construct(private RuleToConditionTransformer $transformer)
    {
    }

    /**
     *
     * @param array $conditionData
     * @return integer
     */
    private function saveCondition(array $conditionData): int
    {
        if ($conditionData['conditionType'] === ConditionType::condition) {
            $condition = Condition::create([
                'field_identifier' => $conditionData['condition']['field'],
                'compare_operator' => $conditionData['condition']['comparer'],
                'value' => $conditionData['condition']['value']
            ]);
            $id = $condition->id;
        } elseif ($conditionData['conditionType'] === ConditionType::group) {
            $id = $this->saveCondition($conditionData['condition']);
        } else {
            throw new ExpressionSyntaxException('No valid condition type given: ' . $conditionData['conditionType']);
        }

        $conditionLinkId = null;
        if (is_array($conditionData['linkTo'])) {
            $conditionLinkId = $this->saveCondition($conditionData['linkTo']);
        }

        $conditionLink = ConditionLink::create([
            'foreign_id' => $id,
            'condition_type' => $conditionData['conditionType']->name,
            'condition_link_id' => $conditionLinkId,
            'link_operator' => $conditionData['logicOperator']
        ]);
        return $conditionLink->id;
    }

    /**
     *
     * @param string $name
     * @param integer $categoryId
     * @param ConditionList $conditionList
     * @param integer $bankAccountId
     * @throws ExpressionSyntaxException
     * @return int
     */
    public function saveRule(string $name, int $categoryId, ConditionList $conditionList, int $bankAccountId): int
    {
        Category::findOrFail($categoryId);
        $conditionLinkId = $this->saveCondition($this->transformer->transformToArray($conditionList));

        $rule = Rule::create([
            'name' => $name,
            'condition_link_id' => $conditionLinkId,
            'bank_account_id' => $bankAccountId
        ]);

        Action::create([
            'rule_id' => $rule->id,
            'category_id' => $categoryId
        ]);

        return $rule->id;
    }
}
