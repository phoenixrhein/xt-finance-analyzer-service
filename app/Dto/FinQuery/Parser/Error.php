<?php

namespace de\xovatec\financeAnalyzer\Dto\FinQuery\Parser;

use de\xovatec\financeAnalyzer\Enums\ParserErrorType;
use de\xovatec\financeAnalyzer\Dto\FinQuery\Parser\ElementPosition;

class Error
{
    /**
     *
     * @param string $message
     * @param ParserErrorType $type
     * @param ElementPosition $position
     */
    public function __construct(
        private string $message,
        private ParserErrorType $type,
        private ElementPosition $position
    ) {
    }

    /**
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     *
     * @return ParserErrorType
     */
    public function getType(): ParserErrorType
    {
        return $this->type;
    }

    /**
     *
     * @return ElementPosition
     */
    public function getPosition(): ElementPosition
    {
        return $this->position;
    }
}
