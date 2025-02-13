<?php

namespace de\xovatec\financeAnalyzer\Dto\FinQuery\Parser;

use de\xovatec\financeAnalyzer\Dto\FinQuery\Parser\Error;
use de\xovatec\financeAnalyzer\Enums\ParserErrorType;

class ErrorReport
{
    /**
     *
     * @var Error[]
     */
    private array $errors = [];

    /**
     *
     * @param ElementPosition $position
     * @param ParserErrorType $type
     * @param string $message
     * @return void
     */
    public function addError(ElementPosition $position, ParserErrorType $type, string $message): void
    {
        $this->errors[] = new Error(
            $message,
            $type,
            $position
        );
    }

    /**
     *
     * @return Error[]
     */
    public function getErrors(): array
    {
        usort($this->errors, function (Error $a, Error $b) {
            return $a->getPosition()->getFrom() <=> $b->getPosition()->getFrom();
        });
        return $this->errors;
    }

    /**
     *
     * @return boolean
     */
    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    /**
     *
     * @return void
     */
    public function clear(): void
    {
        $this->errors = [];
    }
}
