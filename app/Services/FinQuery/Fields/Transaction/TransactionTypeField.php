<?php

namespace de\xovatec\financeAnalyzer\Services\FinQuery\Fields\Transaction;

use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Services\FinQuery\Operators\Equal;
use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\BaseField;
use de\xovatec\financeAnalyzer\Services\FinQuery\Operators\NotEqual;

class TransactionTypeField extends BaseField
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('transaction_type');
    }

    /**
     * @return string[]
     */
    public function getOperators(): array
    {
        return [
            Equal::class,
            NotEqual::class,
        ];
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function validate(mixed $value): bool
    {
        return $this->validateByRules($value, [
            'in:' . implode(',', $this->getTransactionTypes())
        ]);
    }

    /**
     * @return array|null
     */
    public function getSelectableValues(): ?array
    {
        return $this->getTransactionTypes();
    }

    /**
     * @return array
     */
    private function getTransactionTypes(): array
    {
        return Transactions::select('transaction_type')->distinct()->get()->pluck('transaction_type')->toArray();
    }
}
