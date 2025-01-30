<?php

namespace de\xovatec\financeAnalyzer\Services\FinQuery\Fields\Transaction;

use de\xovatec\financeAnalyzer\Services\FinQuery\Operators\Equal;
use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\BaseField;
use de\xovatec\financeAnalyzer\Services\FinQuery\Operators\LessThan;
use de\xovatec\financeAnalyzer\Services\FinQuery\Operators\NotEqual;
use de\xovatec\financeAnalyzer\Services\FinQuery\Operators\GreaterThan;

class AmountField extends BaseField
{
    /**
     * AmountField constructor.
     */
    public function __construct()
    {
        parent::__construct('amount');
    }

    /**
     * @return string[]
     */
    public function getOperators(): array
    {
        return [
            Equal::class,
            NotEqual::class,
            GreaterThan::class,
            LessThan::class
        ];
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function validate(mixed $value): bool
    {
        return $this->validateByRules($value, [
            'numeric'
        ]);
    }
}
