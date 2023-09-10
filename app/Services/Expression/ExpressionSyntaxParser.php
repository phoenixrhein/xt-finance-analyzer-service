<?php

namespace de\xovatec\financeAnalyzer\Services\Expression;

use de\xovatec\financeAnalyzer\Enums\ConditionType;
use de\xovatec\financeAnalyzer\Exceptions\ExpressionSyntaxException;

class ExpressionSyntaxParser
{
    /**
     *
     * @param ExpressionBaseValidator $expressionBaseValidator
     */
    public function __construct(private ExpressionBaseValidator $expressionBaseValidator)
    {
    }

    /**
     *
     * @param string $expression
     * @return array|null
     */
    public function parse(string $expression): ?array
    {
        $this->expressionBaseValidator->validate($expression);
        return $this->parseCondition($expression);
    }

    /**
     *
     * @param string $expressionTail
     * @param integer $startPos
     * @return array|null
     */
    private function parseCondition(string $expressionTail, $startPos = 0): ?array
    {
        $expressionTail = trim($expressionTail);
        if (strlen($expressionTail) === 0) {
            return null;
        }

        if (str_starts_with($expressionTail, '(')) {
            return $this->buildRuleset($expressionTail);
        } else {
            return $this->buildRuleCondition($expressionTail, $startPos);
        }
    }

    /**
     *
     * @param string $expressionTail
     * @return array
     */
    private function buildRuleset(string $expressionTail): array
    {
        $expressionTail = trim($expressionTail);
        $innerExpression = $this->extractOuterParentheses($expressionTail);

        $expressionTail = trim(substr($expressionTail, strlen($innerExpression)));
        $logicOperator = $this->parseLogicOperator($expressionTail);

        $innerExpression = substr($innerExpression, 1, strlen($innerExpression) - 2);
        $ruleset = $this->parseCondition($innerExpression, 0);
        $linkTo = null;
        if ($logicOperator !== null) {
            $linkTo = $this->parseCondition(substr($expressionTail, 0 + strlen($logicOperator . ' ')));
        }

        return [
            'condition' => $ruleset,
            'conditionType' => ConditionType::ruleset,
            'logicOperator' => $logicOperator,
            'linkTo' => $linkTo
        ];
    }

    /**
     *
     * @param string $expressionTail
     * @return string|null
     */
    private function parseLogicOperator(string $expressionTail): ?string
    {
        //todo fill dynamicly
        $logicOperators = [
            'or',
            'and'
        ];

        foreach ($logicOperators as $operator) {
            if (
                str_starts_with($expressionTail, $operator . ' ')
                || str_starts_with($expressionTail, ' ' . $operator . ' ')
            ) {
                return $operator;
            }
        }

        return null;
    }

    /**
     *
     * @param string $expressionTail
     * @param integer $startPos
     * @return array|null
     */
    private function buildRuleCondition(string $expressionTail, int $startPos = 0): ?array
    {
        $expressionTail = trim($expressionTail);
        if (strlen($expressionTail) === 0) {
            return null;
        }
        $ignoreString = false;
        for ($currentPos = $startPos; $currentPos < strlen($expressionTail); $currentPos++) {
            if ($expressionTail[$currentPos] === "'") {
                $ignoreString = !$ignoreString;
            }

            if ($ignoreString === true) {
                continue;
            }

            $logicOperator = $this->parseLogicOperator(substr($expressionTail, $currentPos));

            if ($logicOperator !== null) {
                return [
                    'condition' => $this->buildRule(substr($expressionTail, $startPos, $currentPos)),
                    'conditionType' => ConditionType::rule,
                    'logicOperator' => $logicOperator,
                    'linkTo' => static::parseCondition(
                        substr($expressionTail, $currentPos + strlen(' ' . $logicOperator . ' '))
                    )
                ];
            }
        }

        return [
            'condition' => $this->buildRule($expressionTail),
            'conditionType' => ConditionType::rule,
            'logicOperator' => null,
            'linkTo' => null
        ];
    }

    /**
     *
     * @param string $condition
     * @return array
     */
    private function buildRule(string $condition): array
    {
        //todo fill dynamicly
        $fields = ['field1', 'field2', 'field3', 'field4', 'field5'];
        $comparser = ['==', '<=', '>=', '<', '>', '!='];
        $pattern = "/^(" . implode('|', $fields) . ")\s*"
            . "(" . implode('|', $comparser) . ")\s*('([^']+)'|(\d+\.\d+|\d+))$/";
        if (!preg_match($pattern, $condition, $matches)) {
            throw new ExpressionSyntaxException('Invalid condition syntax: ' . $condition);
        }

        if (in_array($matches[1], $fields) === false) {
            throw new ExpressionSyntaxException('Invalid field: ' . $matches[1]);
        }

        if (in_array($matches[2], $comparser) === false) {
            throw new ExpressionSyntaxException('Invalid comparser: ' . $matches[2]);
        }

        return [
            'field' => $matches[1],
            'comparer' => $matches[2],
            'value' => isset($matches[3]) ?  trim($matches[3], "'") : $matches[5]
        ];
    }

    /**
     *
     * @param string $expression
     * @param integer $startPos
     * @return string|null
     */
    private function extractOuterParentheses(string $expression, int $startPos = 0): ?string
    {
        $openPos = strpos($expression, '(', $startPos);

        if ($openPos === false) {
            return null;
        }

        $openCount = 1;
        $closeCount = 0;
        $currentPos = $openPos + 1;

        while ($openCount !== $closeCount && $currentPos < strlen($expression)) {
            if ($expression[$currentPos] === '(') {
                $openCount++;
            } elseif ($expression[$currentPos] === ')') {
                $closeCount++;
            }
            $currentPos++;
        }

        if ($openCount === $closeCount) {
            return substr($expression, $openPos, $currentPos - $openPos);
        } else {
            throw new ExpressionSyntaxException('No close bracket found');
        }
    }
}
