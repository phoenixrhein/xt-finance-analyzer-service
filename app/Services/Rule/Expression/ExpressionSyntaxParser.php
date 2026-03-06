<?php

namespace de\xovatec\financeAnalyzer\Services\Rule\Expression;

use de\xovatec\financeAnalyzer\Enums\LogicalOperator;
use de\xovatec\financeAnalyzer\Enums\ParserErrorType;
use de\xovatec\financeAnalyzer\Dto\FinQuery\Condition;
use de\xovatec\financeAnalyzer\Dto\FinQuery\ConditionList;
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

    /**
     *
     * @return ErrorReport
     */
    public function getErrorReport(): ErrorReport
    {
        return $this->errorReport;
    }

    /**
     *
     * @param string $expression
     * @return ConditionList|null
     */
    public function parse(string $expression): ConditionList|null
    {
        $this->errorReport->clear();
        $this->expressionBaseValidator->validate($expression, $this->errorReport);
        if ($this->errorReport->hasErrors()) {
            return null;
        }
        $list = new ConditionList();
        $this->parseExpression($expression, 0, $list);
        return $list;
    }

    /**
     *
     * @param string $expressionTail
     * @param int $sectionPositionFrom
     * @param ConditionList $list
     * @return void
     */
    private function parseExpression(string $expressionTail, int $sectionPositionFrom, ConditionList $list): void
    {
        if (strlen(trim($expressionTail)) === 0) {
            return;
        }

        if (str_starts_with($expressionTail, '(')) {
            $this->buildConditionGroup($expressionTail, $sectionPositionFrom, $list);
        } else {
            $this->buildRule($expressionTail, $sectionPositionFrom, $list);
        }
    }

    /**
     *
     * @param string $expressionTail
     * @param int $sectionPositionFrom
     * @param ConditionList $parentList
     * @return void
     */
    private function buildConditionGroup(
        string $expressionTail,
        int $sectionPositionFrom,
        ConditionList $parentList
    ): void {
        $countLeadingSpaces = strlen($expressionTail) - strlen(ltrim($expressionTail, " "));
        $expressionTail = trim($expressionTail);
        $innerExpression = $this->extractOuterParentheses($expressionTail, $sectionPositionFrom + $countLeadingSpaces);

        $followingExpression = trim(substr($expressionTail, strlen($innerExpression)));
        $logicOperatorRaw = $this->parseLogicOperator(
            $followingExpression,
            true,
            $sectionPositionFrom + strpos($expressionTail, $followingExpression)
        );

        $list = new ConditionList();

        //remove outer parentheses
        $innerExpression = substr($innerExpression, 1, strlen($innerExpression) - 2);

        $this->parseExpression(
            $innerExpression,
            $sectionPositionFrom + 1 + $countLeadingSpaces, // 1 = open bracket
            $list
        );
        $parentList->add($list);

        if ($logicOperatorRaw !== null) {
            $logicOperator = LogicalOperator::tryFrom(strtoupper($logicOperatorRaw)) ?? LogicalOperator::AND;
            if (
                $parentList->getLogicalOperator(true) !== null
                && $parentList->getLogicalOperator()->value !== $logicOperator->value
            ) {
                $this->errorReport->addError(
                    new ElementPosition($expressionTail, $sectionPositionFrom, $logicOperatorRaw),
                    ParserErrorType::LOGICAL_OPERATOR,
                    __(
                        'cli.fin_query.parser.error.invalid_logical_operator',
                        [
                            'operators' => implode(', ', [LogicalOperator::AND->value, LogicalOperator::OR->value])
                        ]
                    )
                );
            }
            $parentList->setLogicalOperator($logicOperator);
            $followingExpression = trim(substr($followingExpression, strlen($logicOperatorRaw)));
            $this->parseExpression(
                // following expression without logic operator
                $followingExpression,
                $sectionPositionFrom + strpos($expressionTail, $followingExpression),
                $parentList
            );
        }
    }

    /**
     *
     * @param string $expressionTail
     * @param bool $isFollowing
     * @param int $sectionPositionFrom
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
     * @param int $sectionPositionFrom
     * @param ConditionList $list
     * @return void
     */
    private function buildRule(string $expressionTail, int $sectionPositionFrom, ConditionList $list): void
    {
        if (strlen(trim($expressionTail)) === 0) {
            return;
        }

        $ignoreValueString = false;
        for ($currentPos = 0; $currentPos < strlen($expressionTail); $currentPos++) {
            if ($expressionTail[$currentPos] === "'") {
                $ignoreValueString = !$ignoreValueString;
            }

            if ($ignoreValueString === true) {
                continue;
            }

            $logicOperatorRaw = $this->parseLogicOperator(substr($expressionTail, $currentPos));

            if ($logicOperatorRaw === null || $currentPos === 0) {
                continue; //skip to next logical operator
            }
            $logicOperator = LogicalOperator::tryFrom(strtoupper($logicOperatorRaw)) ?? LogicalOperator::AND;
            if (
                $list->getLogicalOperator(true) !== null
                && $list->getLogicalOperator()->value !== $logicOperator->value
            ) {
                $this->errorReport->addError(
                    new ElementPosition($expressionTail, $sectionPositionFrom, $logicOperatorRaw),
                    ParserErrorType::LOGICAL_OPERATOR,
                    __(
                        'cli.fin_query.parser.error.invalid_logical_operator',
                        [
                            'operators' => implode(', ', [LogicalOperator::AND->value, LogicalOperator::OR->value])
                        ]
                    )
                );
            }

            $list->setLogicalOperator(
                $logicOperator
            );

            $condition = $this->buildCondition(substr($expressionTail, 0, $currentPos), $sectionPositionFrom);

            if ($condition === null) {
                return;
            }

            $list->add($condition);

            $this->parseExpression(
                substr($expressionTail, $currentPos + strlen(' ' . $logicOperatorRaw . ' ')),
                $sectionPositionFrom + $currentPos + strlen(' ' . $logicOperatorRaw . ' '),
                $list
            );
            return;
        }

        $condition = $this->buildCondition($expressionTail, $sectionPositionFrom);
        if ($condition === null) {
            return;
        }
        $list->add($condition);
    }

    /**
     *
     * @param string $condition
     * @param int $sectionPositionFrom
     * @return Condition|null
     */
    private function buildCondition(string $condition, int $sectionPositionFrom): ?Condition
    {
        $trimmedCondition = trim($condition);
        $value = null;

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
            $value = isset($matches[3]) ? trim($matches[3], "'") : ($matches[5] ?? $matches[4]);

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

        if (empty($matches[1]) || empty($matches[2])) {
            return null;
        }

        $field = FieldConfig::getFieldByColumnKey($matches[1]);
        $operator = FieldConfig::getOperatorClass($field, $matches[2]);
        return new Condition(
            $field,
            $operator,
            $value ?? ''
        );
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

        return '';
    }
}
