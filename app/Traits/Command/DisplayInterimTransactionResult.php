<?php

namespace de\xovatec\financeAnalyzer\Traits\Command;

use Illuminate\Database\Eloquent\Builder;
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
     * @param Builder $transactions
     * @param ConditionList $conditions
     * @return void
     */
    private function displayInterimResult(Builder $transactions, ConditionList $conditions): void
    {
        $this->getSqlQueryBuilder()->build(
            $transactions,
            $conditions
        );
        
        $transactions->select(array_keys(TransactionList::$compactView))
            ->limit(self::DISPLAY_LIMIT);

        $this->tableConsolePagination(
            $transactions->get(),
            TransactionList::$compactView,
            null,
            'cli.transaction.base.table.header.'
        );

        if ($transactions->count() > self::DISPLAY_LIMIT) {
            $this->line(
                ($transactions->count() - self::DISPLAY_LIMIT)
                . ' ' . __('cli.view.display_interim_results.more_matches_found')
            );
        } elseif ($transactions->count() === 0) {
            $this->newLine();
            $this->alert(__('cli.view.display_interim_results.no_matches_found'));
        }
    }
}
