<?php

namespace de\xovatec\financeAnalyzer\Services\FinQuery\Fields\Transaction;

use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\BaseField;
use de\xovatec\financeAnalyzer\Services\FinQuery\Operators\Equal;

class IdField extends BaseField
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('id');
    }

    /**
     * @return string[]
     */
    public function getOperators(): array
    {
        return [
            Equal::class,
        ];
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function validate(mixed $value): bool
    {
        if (!$this->validateByRules($value, ['numeric'])) {
            return false;
        }

        if (!Transactions::query()->whereKey($value)->exists()) {
            $this->error = __('cli.transaction.base.error.not_found_transaction_id', ['transactionId' => $value]);
            return false;
        }

        return true;
    }
}
