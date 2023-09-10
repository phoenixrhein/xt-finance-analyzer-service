<?php

namespace de\xovatec\financeAnalyzer\Services\Expression;

use Symfony\Component\CssSelector\Exception\ExpressionErrorException;

class ExpressionBaseValidator
{
    /**
     *
     * @param string $expression
     * @return void
     */
    public function validate(string $expression): void
    {
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
            throw new ExpressionErrorException("Invalid single qoutes");
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
            throw new ExpressionErrorException("Invalid parentheses");
        }
    }
}
