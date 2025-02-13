<?php

namespace de\xovatec\financeAnalyzer\Services\Expression;

use de\xovatec\financeAnalyzer\Enums\ParserErrorType;
use de\xovatec\financeAnalyzer\Dto\FinQuery\Parser\ErrorReport;
use de\xovatec\financeAnalyzer\Dto\FinQuery\Parser\ElementPosition;

class ExpressionBaseValidator
{
    private ErrorReport $errorReport;

    /**
     *
     * @param string $expression
     * @param ErrorReport $errorReport
     * @return void
     */
    public function validate(string $expression, ErrorReport $errorReport): void
    {
        $this->errorReport = $errorReport;
        $this->validateSingleQuoute($expression);
        $this->validateParentheses($expression);
    }

    /**
     *
     * @param string $expression
     * @return void
     */
    private function validateSingleQuoute(string $expression): void
    {
        if (substr_count($expression, "'") % 2 !== 0) {
            $this->errorReport->addError(
                new ElementPosition($expression, 0),
                ParserErrorType::SYNTAX,
                __('cli.expression_parser.error.invalid_single_quote')
            );
        }
    }

    /**
     *
     * @param string $expression
     * @return void
     */
    private function validateParentheses(string $expression): void
    {
        $stack = [];

        for ($i = 0; $i < strlen($expression); $i++) {
            $char = $expression[$i];

            if ($char === '(') {
                array_push($stack, $char);
            } elseif ($char === ')' &&  !empty($stack)) {
                array_pop($stack);
            }
        }

        if (!empty($stack)) {
            $this->errorReport->addError(
                new ElementPosition($expression, 0),
                ParserErrorType::SYNTAX,
                __('cli.expression_parser.error.invalid_bracket')
            );
        }
    }
}
