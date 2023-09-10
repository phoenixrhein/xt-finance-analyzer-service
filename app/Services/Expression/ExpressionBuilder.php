<?php

namespace de\xovatec\financeAnalyzer\Services\Expression;

class ExpressionBuilder
{
    /**
     *
     * @param array $expressionData
     * @return string
     */
    public function build(array $expressionData): string
    {
        $expression = '';
        if ($expressionData['condition_type'] == 'rule') {
            $expression = $this->buildComparison($expressionData['rule']);
        } elseif ($expressionData['condition_type'] == 'ruleset') {
            $expression = $this->buildInnerExpression($expressionData['ruleset']);
        }

        if (strlen($expressionData['link_operator']) === 0) {
            return $expression;
        }

        return $expression .= ' ' . $expressionData['link_operator'] . ' '
            . $this->build($expressionData['linked_condition']);
    }

    /**
     *
     * @param array $ruleset
     * @return string
     */
    private function buildInnerExpression(array $ruleset): string
    {
        return '(' . $this->build($ruleset['condition']) . ')';
    }

    /**
     *
     * @param array $condition
     * @return string
     */
    private function buildComparison(array $condition): string
    {
        $conditionValue = $condition['value'];
        if (is_numeric($conditionValue) === false) {
            $conditionValue = "'" . $condition['value'] . "'";
        }
        return $condition['field_identifier'] . ' ' . $condition['compare_operator'] . ' ' . $conditionValue;
    }
}
