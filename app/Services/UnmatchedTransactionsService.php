<?php

namespace de\xovatec\financeAnalyzer\Services;

use UnexpectedValueException;
use Illuminate\Database\Eloquent\Builder;
use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Enums\TransactionCheckCode;

class UnmatchedTransactionsService
{
    /**
     *
     * @param Builder $transactions
     * @return Builder
     */
    public function getUnmatchedTransactions(Builder $transactions): Builder
    {
        if (!$transactions->getModel() instanceof Transactions) {
            throw new UnexpectedValueException('Unmatched transactions cannot be processed for Transactions model.');
        }
        return $transactions->leftJoin('rule_transaction', 'transactions.id', '=', 'rule_transaction.transaction_id')
            ->whereNull('rule_transaction.transaction_id')
            ->whereRaw(
                'checks_code & ' . TransactionCheckCode::UNCATEGORISABLE->value . ' = ' .
                TransactionCheckCode::NONE->value
            )
            ->orderBy('transactions.transaction_date', 'asc');
    }
}
