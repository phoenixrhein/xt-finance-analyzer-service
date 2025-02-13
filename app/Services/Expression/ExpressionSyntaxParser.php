<?php

namespace de\xovatec\financeAnalyzer\Services\Expression;

use de\xovatec\financeAnalyzer\Enums\ConditionType;
use de\xovatec\financeAnalyzer\Enums\LogicalOperator;
use de\xovatec\financeAnalyzer\Enums\ParserErrorType;
use de\xovatec\financeAnalyzer\Services\FinQuery\FieldConfig;
use de\xovatec\financeAnalyzer\Dto\FinQuery\Parser\ErrorReport;
use de\xovatec\financeAnalyzer\Dto\FinQuery\Parser\ElementPosition;

class ExpressionSyntaxParser
{
    /**
     *
     * @param ExpressionBaseValidator $expressionBaseValidator
     */
    public function __construct(
        private ExpressionBaseValidator $expressionBaseValidator,
        private ErrorReport $errorReport
    ) {
    }

    public function getErrorReport(): ErrorReport
    {
        return $this->errorReport;
    }

    /**
     *
     * @param string $expression
     * @return array|null
     */
    public function parse(string $expression): ?array
    {
        $this->errorReport->clear();
        $this->expressionBaseValidator->validate($expression, $this->errorReport);
        if ($this->errorReport->hasErrors()) {
            return null;
        }
        return $this->parseExpression($expression);
    }

    /**
     *
     * @param string $expressionTail
     * @param integer $sectionPositionFrom
     * @return array|null
     */
    private function parseExpression(string $expressionTail, $sectionPositionFrom = 0): ?array
    {
        if (strlen(trim($expressionTail)) === 0) {
            return null;
        }

        if (str_starts_with($expressionTail, '(')) {
            return $this->buildRuleset($expressionTail, $sectionPositionFrom);
        } else {
            return $this->buildRuleCondition($expressionTail, $sectionPositionFrom);
        }
    }

    /**
     *
     * @param string $expressionTail
     * @param int $sectionPositionFrom
     * @return array
     */
    private function buildRuleset(string $expressionTail, int $sectionPositionFrom): array
    {
        $linkTo = null;
        $countLeadingSpaces = strlen($expressionTail) - strlen(ltrim($expressionTail, " "));
        $expressionTail = trim($expressionTail);
        $innerExpression = $this->extractOuterParentheses($expressionTail, $sectionPositionFrom + $countLeadingSpaces);

        $followingExpression = trim(substr($expressionTail, strlen($innerExpression)));
        $logicOperator = $this->parseLogicOperator(
            $followingExpression,
            true,
            $sectionPositionFrom + strpos($expressionTail, $followingExpression)
        );

        //remove outer parentheses
        $innerExpression = substr($innerExpression, 1, strlen($innerExpression) - 2);

        $ruleset = $this->parseExpression(
            $innerExpression,
            $sectionPositionFrom + 1 + $countLeadingSpaces // 1 = open bracket
        );

        if ($logicOperator !== null) {
            $followingExpression = substr($followingExpression, strlen($logicOperator . ' '));
            $linkTo = $this->parseExpression(
                // following expression without logic operator
                $followingExpression,
                $sectionPositionFrom + strpos($expressionTail, $followingExpression)
            );
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
    private function parseLogicOperator(
        string $expressionTail,
        bool $isFollowing = false,
        int $sectionPositionFrom = 0
    ): ?string {
        $logicOperators = [
            LogicalOperator::OR->value,
            LogicalOperator::AND->value
        ];

        foreach ($logicOperators as $operator) {
            if (
                str_starts_with(strtolower($expressionTail), strtolower($operator . ' '))
                || str_starts_with(strtolower($expressionTail), strtolower(' ' . $operator . ' '))
            ) {
                return $operator;
            }
        }

        if ($isFollowing === true && strlen(trim(str_replace(['(', ')'], '', $expressionTail))) > 0) {
            $invalidOperator = strtok(trim($expressionTail), ' ');
            $this->errorReport->addError(
                new ElementPosition($expressionTail, $sectionPositionFrom, $invalidOperator),
                ParserErrorType::LOGICAL_OPERATOR,
                __(
                    'cli.fin_query.parser.error.invalid_logical_operator',
                    [
                        'operators' => implode(', ', $logicOperators)
                    ]
                )
            );
            return $invalidOperator;
        }

        return null;
    }

    /**
     *
     * @param string $expressionTail
     * @param integer $sectionPositionFrom
     * @return array|null
     */
    private function buildRuleCondition(string $expressionTail, int $sectionPositionFrom): ?array
    {
        if (strlen(trim($expressionTail)) === 0) {
            return null;
        }

        $ignoreValueString = false;
        for ($currentPos = 0; $currentPos < strlen($expressionTail); $currentPos++) {
            if ($expressionTail[$currentPos] === "'") {
                $ignoreValueString = !$ignoreValueString;
            }

            if ($ignoreValueString === true) {
                continue;
            }

            $logicOperator = $this->parseLogicOperator(substr($expressionTail, $currentPos));

            if ($logicOperator === null || $currentPos === 0) {
                continue; //skip to next logical operator
            }

            return [
                'condition' => $this->buildRule(substr($expressionTail, 0, $currentPos), $sectionPositionFrom),
                'conditionType' => ConditionType::rule,
                'logicOperator' => $logicOperator,
                'linkTo' => $this->parseExpression(
                    substr($expressionTail, $currentPos + strlen(' ' . $logicOperator . ' ')),
                    $sectionPositionFrom + $currentPos + strlen(' ' . $logicOperator . ' ')
                )
            ];
        }

        return [
            'condition' => $this->buildRule($expressionTail, $sectionPositionFrom),
            'conditionType' => ConditionType::rule,
            'logicOperator' => null,
            'linkTo' => null
        ];
    }

    /**
     *
     * @param string $condition
     * @param int $sectionPositionFrom
     * @return array
     */
    private function buildRule(string $condition, int $sectionPositionFrom): array
    {
        $trimmedCondition = trim($condition);

        $fields = array_keys(FieldConfig::getAvailableFieldsWithColumnKey());

        $pattern = "/^([^\s]+)\s*([^\s]+)\s*('([^']+)'|(\d+\.\d+|\d+))$/";
        $isValidSyntax = preg_match($pattern, $trimmedCondition, $matches);

        if ($isValidSyntax !== 1) {
            $this->errorReport->addError(
                new ElementPosition($condition, $sectionPositionFrom),
                ParserErrorType::SYNTAX,
                __('cli.fin_query.parser.error.invalid_syntax', ['trimmedCondition' => $trimmedCondition])
            );
        } else {
            $value = isset($matches[3]) ?  trim($matches[3], "'") : ($matches[5] ?? $matches[4]);

            if (isset($matches[1]) && in_array($matches[1], $fields) === false) {
                $this->errorReport->addError(
                    new ElementPosition($condition, $sectionPositionFrom, $matches[1]),
                    ParserErrorType::FIELD,
                    __('cli.fin_query.parser.error.invalid_field', ['fields' => implode(', ', $fields)])
                );
            } else {
                $field = FieldConfig::getFieldByColumnKey($matches[1]);

                if (in_array($matches[2], FieldConfig::getAvailableFieldOperator($field)) === false) {
                    $this->errorReport->addError(
                        new ElementPosition($condition, $sectionPositionFrom, $matches[2]),
                        ParserErrorType::OPERATOR,
                        __(
                            'cli.fin_query.parser.error.invalid_operator',
                            [
                                'operators' => implode(', ', FieldConfig::getAvailableFieldOperator($field))
                            ]
                        )
                    );
                }

                if ($field->validate($value) === false) {
                    $this->errorReport->addError(
                        new ElementPosition($condition, $sectionPositionFrom, $matches[2]),
                        ParserErrorType::VALUE,
                        __('cli.fin_query.parser.error.invalid_value', ['error_message' => $field->getError()])
                    );
                }
            }
        }



        return [
            'field' => $matches[1] ?? '',
            'comparer' => $matches[2] ?? '',
            'value' => $value ?? ''
        ];
    }

    /**
     *
     * @param string $expression
     * @param int $sectionPositionFrom
     * @return string
     */
    private function extractOuterParentheses(string $expression, int $sectionPositionFrom): string
    {
        $openPos = strpos($expression, '(');

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
            $this->errorReport->addError(
                new ElementPosition($expression, $sectionPositionFrom + $openPos),
                ParserErrorType::SYNTAX,
                __('cli.fin_query.parser.error.close_bracket_missing')
            );
        }
    }
}
