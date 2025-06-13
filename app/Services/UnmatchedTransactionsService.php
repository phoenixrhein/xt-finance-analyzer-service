<?php

namespace de\xovatec\financeAnalyzer\Services;

use UnexpectedValueException;
use Illuminate\Database\Eloquent\Builder;
use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Enums\TransactionCheckCode;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use Illuminate\Database\Eloquent\Collection;

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

    /**
     *
     * @param BankAccount $bankAccount
     * @param Collection $ignoreIbans
     * @return Builder
     */
    public function getTotalUnmatchedTransactions(BankAccount $bankAccount, Collection $ignoreIbans): Builder
    {
        $unmatchedTransactions = Transactions::where('bank_account_iban', $bankAccount->iban)->orderBy('id');

        if ($ignoreIbans->isNotEmpty()) {
            $unmatchedTransactions = $unmatchedTransactions->whereNotIn('creditor_iban', $ignoreIbans->toArray());
        }

        return $this->getUnmatchedTransactions($unmatchedTransactions);
    }
}
