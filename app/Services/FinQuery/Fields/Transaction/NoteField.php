<?php

namespace de\xovatec\financeAnalyzer\Services\FinQuery\Fields\Transaction;

use de\xovatec\financeAnalyzer\Services\FinQuery\Operators\Equal;
use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\BaseField;
use de\xovatec\financeAnalyzer\Services\FinQuery\Operators\Contains;
use de\xovatec\financeAnalyzer\Services\FinQuery\Operators\EndsWith;
use de\xovatec\financeAnalyzer\Services\FinQuery\Operators\NotEqual;
use de\xovatec\financeAnalyzer\Services\FinQuery\Operators\StartsWith;
use de\xovatec\financeAnalyzer\Services\FinQuery\Operators\NotContains;
use de\xovatec\financeAnalyzer\Services\FinQuery\Operators\NotEndsWith;
use de\xovatec\financeAnalyzer\Services\FinQuery\Operators\NotStartsWith;

class NoteField extends BaseField
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('note');
    }

    /**
     * @return string[]
     */
    public function getOperators(): array
    {
        return [
            Equal::class,
            NotEqual::class,
            Contains::class,
            NotContains::class,
            StartsWith::class,
            EndsWith::class,
            NotStartsWith::class,
            NotEndsWith::class
        ];
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function validate(mixed $value): bool
    {
        return $this->validateByRules($value, [
            'string'
        ]);
    }
}
