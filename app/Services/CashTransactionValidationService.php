<?php

namespace de\xovatec\financeAnalyzer\Services;

use de\xovatec\financeAnalyzer\Models\CashTransaction;
use de\xovatec\financeAnalyzer\Models\Transactions;

class CashTransactionValidationService
{
    public function validate(CashTransaction $cashTransaction): ?string
    {
        if ($cashTransaction->transaction_id === null) {
            return null;
        }

        $transaction = Transactions::find($cashTransaction->transaction_id);
        if (!$transaction instanceof Transactions) {
            return __('cli.cash_transaction.upsert.validate_error.transaction_not_found');
        }

        if ((float) $transaction->amount >= 0) {
            return __('cli.cash_transaction.upsert.validate_error.transaction_must_be_expense');
        }

        $amountInCents = $this->toCents($cashTransaction->amount);
        $otherAmountsInCents = $cashTransaction->newQuery()
            ->where('transaction_id', $cashTransaction->transaction_id)
            ->when(
                $cashTransaction->exists,
                fn ($query) => $query->where('id', '!=', $cashTransaction->getKey())
            )
            ->get()
            ->sum(fn (CashTransaction $entry): int => $this->toCents($entry->amount));

        if ($amountInCents + $otherAmountsInCents > $this->toCents(abs($transaction->amount))) {
            return __(
                'cli.cash_transaction.upsert.validate_error.total_amount_exceeded',
                ['rest' => number_format(
                    ($this->toCents(abs($transaction->amount)) - $otherAmountsInCents) / 100,
                    2,
                    ',',
                    '.'
                )]
            );
        }

        return null;
    }

    private function toCents(mixed $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
