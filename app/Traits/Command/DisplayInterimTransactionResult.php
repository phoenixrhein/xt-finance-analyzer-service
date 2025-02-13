<?php

namespace de\xovatec\financeAnalyzer\Traits\Command;

use Illuminate\Database\Eloquent\Collection;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Dto\FinQuery\ConditionList;
use de\xovatec\financeAnalyzer\Services\FinQuery\SqlQueryBuilder;
use de\xovatec\financeAnalyzer\Traits\Command\View\TableConsolePagination;
use de\xovatec\financeAnalyzer\Console\Commands\Transaction\TransactionList;

trait DisplayInterimTransactionResult
{
    use TableConsolePagination;
    
    /**
     * @var int
     */
    private const DISPLAY_LIMIT = 5;

    /**
     * @return SqlQueryBuilder
     */
    abstract private function getSqlQueryBuilder(): SqlQueryBuilder;

    /**
     * @param BankAccount $bankAccount
     * @param ConditionList $conditions
     * @param Collection|null $ignoreIbans
     * @return void
     */
    private function displayInterimResult(
        BankAccount $bankAccount,
        ConditionList $conditions,
        ?Collection $ignoreIbans
    ): void {
        $query = Transactions::where('bank_account_iban', $bankAccount->iban);

        if ($ignoreIbans instanceof Collection && $ignoreIbans->isNotEmpty()) {
            $query->whereNotIn('creditor_iban', $ignoreIbans->toArray());
        }

        $this->getSqlQueryBuilder()->build(
            $query,
            $conditions
        );

        $query->select(array_keys(TransactionList::$compactView))
            ->limit(self::DISPLAY_LIMIT);

        $this->tableConsolePagination(
            $query->get(),
            TransactionList::$compactView,
            null,
            'cli.transaction.base.table.header.'
        );

        if ($query->count() > self::DISPLAY_LIMIT) {
            $this->line(
                ($query->count() - self::DISPLAY_LIMIT) . ' ' . __('cli.view.display_interim_results.more_matches_found')
            );
        }
    }
}
