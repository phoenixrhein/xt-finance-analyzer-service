<?php

namespace de\xovatec\financeAnalyzer\Services\Expression;

use de\xovatec\financeAnalyzer\Models\Rule;
use de\xovatec\financeAnalyzer\Models\Action;
use de\xovatec\financeAnalyzer\Models\Ruleset;
use de\xovatec\financeAnalyzer\Models\Category;
use de\xovatec\financeAnalyzer\Enums\ConditionType;
use de\xovatec\financeAnalyzer\Exceptions\ExpressionSyntaxException;
use de\xovatec\financeAnalyzer\Models\ConditionLink;

class ExpressionService
{
    /**
     *
     * @param array $conditionData
     * @return integer
     */
    private function saveCondition(array $conditionData): int
    {
        if ($conditionData['conditionType'] === ConditionType::rule) {
            $rule = Rule::create([
                'field_identifier' => $conditionData['condition']['field'],
                'compare_operator' => $conditionData['condition']['comparer'],
                'value' => $conditionData['condition']['value']
            ]);
            $id = $rule->id;
        } elseif ($conditionData['conditionType'] === ConditionType::ruleset) {
            $id = $this->saveSubRuleset($conditionData['condition']);
        } else {
            throw new ExpressionSyntaxException('No valid condition type given: ' . $conditionData['conditionType']);
        }

        $conditionId = null;
        if (is_array($conditionData['linkTo'])) {
            $conditionId = $this->saveCondition($conditionData['linkTo']);
        }

        $condition = ConditionLink::create([
            'condition_foreign_id' => $id,
            'condition_type' => $conditionData['conditionType']->name,
            'linked_condition_id' => $conditionId,
            'link_operator' => $conditionData['logicOperator']
        ]);
        return $condition->id;
    }

    /**
     *
     * @param array $conditionData
     * @return integer
     */
    private function saveSubRuleset(array $conditionData): int
    {
        $id = $this->saveCondition($conditionData);
        $ruleset = Ruleset::create([
            'name' => '',
            'type' => 'sub',
            'condition_id' => $id
        ]);
        return $ruleset->id;
    }

    /**
     *
     * @param string $name
     * @param integer $categoryId
     * @param array $expressionData
     * @return int
     */
    public function saveRulesetExpression(string $name, int $categoryId, array $expressionData): int
    {
        Category::findOrFail($categoryId);
        $id = $this->saveCondition($expressionData);

        $ruleset = Ruleset::create([
            'name' => $name,
            'type' => 'main',
            'condition_id' => $id
        ]);

        Action::create([
            'ruleset_id' => $ruleset->id,
            'category_id' => $categoryId
        ]);

        return $ruleset->id;
    }
}
