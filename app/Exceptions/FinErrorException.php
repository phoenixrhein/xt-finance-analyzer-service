<?php

namespace de\xovatec\financeAnalyzer\Exceptions;

use de\xovatec\financeAnalyzer\Helpers\ExceptionMessageHelper;
use Exception;

class FinErrorException extends Exception
{
    /**
     *
     * @param string $text
     * @param string $key
     * @param array $replaceParams
     */
    public function __construct(string $text = '', string $key = '', array $replaceParams = [])
    {
        parent::__construct(ExceptionMessageHelper::generateMessageString(
            $text,
            $key,
            $replaceParams
        ));
    }
}
